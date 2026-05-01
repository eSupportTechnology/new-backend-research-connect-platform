<?php
// app/Http/Controllers/SellingItemController.php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\Profile\BankDetail;
use App\Models\Profile\ShippingAddress;
use App\Models\Innovation\Innovation;
use App\Models\Innovation\SellingItem;
use App\Models\Research\Research;
use App\Models\RegisterUsers\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;

class SellingItemController extends Controller
{
    /**
     * Get all selling items for public browsing with filters and pagination
     */
    public function index(Request $request)
    {
        try {
            $query = SellingItem::with(['user.profile', 'sellable'])
                ->where('status', 'active')
                ->where(function($q) {
                    $q->where('stock_quantity', '>', 0)
                        ->orWhereNull('stock_quantity');
                });

            // Filter by sellable type
            if ($request->has('sellable_type') && in_array($request->sellable_type, ['innovation', 'research'])) {
                $modelClass = $request->sellable_type === 'innovation'
                    ? Innovation::class
                    : Research::class;
                $query->where('sellable_type', $modelClass);
            }

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Filter by tags
            if ($request->has('tags')) {
                $tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
                foreach ($tags as $tag) {
                    $query->whereJsonContains('tags', trim($tag));
                }
            }

            // Price range filter
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Filter by payment type
            if ($request->has('is_paid')) {
                $query->where('is_paid', $request->boolean('is_paid'));
            } else {
                $query->where(function($q) {
                    $q->where('is_paid', true)->orWhere('is_paid', false);
                });
            }

            if ($request->boolean('free_only')) {
                $query->where('is_paid', false);
            }

            if ($request->has('condition')) {
                $query->where('condition', $request->condition);
            }

            if ($request->boolean('featured_only')) {
                $query->where('is_featured', true);
            }

            if ($request->boolean('in_stock_only')) {
                $query->where('stock_quantity', '>', 0);
            }

            // Search by title or description
            if ($request->has('search')) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Filter by user/seller
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'latest');
            switch ($sortBy) {
                case 'price_asc':
                    $query->orderByRaw('CASE WHEN is_paid = true THEN price ELSE 0 END ASC');
                    break;
                case 'price_desc':
                    $query->orderByRaw('CASE WHEN is_paid = true THEN price ELSE 0 END DESC');
                    break;
                case 'popular':
                    $query->orderBy('total_purchases', 'desc');
                    break;
                case 'most_viewed':
                    $query->orderBy('total_views', 'desc');
                    break;
                case 'discounted':
                    $query->where('discount_percentage', '>', 0)
                        ->orderBy('discount_percentage', 'desc');
                    break;
                case 'latest':
                default:
                    $query->orderBy('listed_at', 'desc');
                    break;
            }

            $perPage = $request->get('per_page', 12);
            $items = $query->paginate($perPage);

            $items->getCollection()->transform(function ($item) {
                if ($item->is_paid && $item->discount_percentage > 0) {
                    $item->final_price = $item->discounted_price;
                    $item->saved_amount = $item->price - $item->discounted_price;
                } else {
                    $item->final_price = $item->price;
                    $item->saved_amount = 0;
                }

                if ($item->user) {
                    $profile = $item->user->profile;
                    $item->seller_name = ($profile && $profile->business_name)
                        ? $profile->business_name
                        : $item->user->first_name . ' ' . $item->user->last_name;
                    $item->seller_avatar = $profile ? $profile->profile_image_url : null;
                    $item->seller_id = $item->user->id;
                    $item->seller_business_name = $profile->business_name ?? null;
                }

                return $item;
            });

            $filters = [
                'categories' => SellingItem::where('status', 'active')
                    ->select('category')
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy('category')
                    ->get(),
                'conditions' => SellingItem::where('status', 'active')
                    ->select('condition')
                    ->selectRaw('COUNT(*) as count')
                    ->whereNotNull('condition')
                    ->groupBy('condition')
                    ->get(),
                'price_range' => [
                    'min' => SellingItem::where('status', 'active')->where('is_paid', true)->min('price'),
                    'max' => SellingItem::where('status', 'active')->where('is_paid', true)->max('price'),
                ],
                'total_count'    => SellingItem::where('status', 'active')->count(),
                'free_count'     => SellingItem::where('status', 'active')->where('is_paid', false)->count(),
                'paid_count'     => SellingItem::where('status', 'active')->where('is_paid', true)->count(),
                'featured_count' => SellingItem::where('status', 'active')->where('is_featured', true)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $items,
                'filters' => $filters,
                'message' => 'Selling items retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch selling items: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch selling items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active selling items for a specific seller (public)
     */
    public function getSellerProducts(Request $request, $userId)
    {
        try {
            $seller = User::with('profile')->findOrFail($userId);

            $items = SellingItem::where('user_id', $userId)
                ->where('status', 'active')
                ->where(function($q) {
                    $q->where('stock_quantity', '>', 0)->orWhereNull('stock_quantity');
                })
                ->orderBy('listed_at', 'desc')
                ->paginate($request->get('per_page', 12));

            $profile = $seller->profile;

            return response()->json([
                'success' => true,
                'data' => $items,
                'seller' => [
                    'id'             => $seller->id,
                    'first_name'     => $seller->first_name,
                    'last_name'      => $seller->last_name,
                    'business_name'  => $profile->business_name ?? null,
                    'display_name'   => ($profile && $profile->business_name)
                                            ? $profile->business_name
                                            : $seller->first_name . ' ' . $seller->last_name,
                    'avatar'         => $profile ? $profile->profile_image_url : null,
                    'bio'            => $profile->bio ?? null,
                    'total_listings' => SellingItem::where('user_id', $userId)->where('status', 'active')->count(),
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Seller not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch seller products: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Add item to selling table with all details
     */
    public function addToSelling(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sellable_type'                          => 'required|in:innovation,research',
            'sellable_id'                            => 'required|integer',
            'type'                                   => 'nullable|in:product,innovation',
            'is_paid'                                => 'required|boolean',
            'price'                                  => 'required_if:is_paid,true|nullable|numeric|min:0',
            'additionalDetails'                      => 'required|array',
            'additionalDetails.stock_quantity'       => 'nullable|integer|min:1',
            'additionalDetails.sku'                  => 'nullable|string|max:100',
            'additionalDetails.condition'            => 'nullable|string|in:new,like_new,excellent,good,fair',
            'additionalDetails.delivery_time'        => 'nullable|integer|min:1',
            'additionalDetails.warranty_period'      => 'nullable|integer|min:0',
            'additionalDetails.return_policy'        => 'nullable|integer|min:0',
            'additionalDetails.shipping_cost'        => 'nullable|numeric|min:0',
            'additionalDetails.whats_included'       => 'nullable|string',
            'additionalDetails.specifications'       => 'nullable|array',
            'additionalDetails.discount_percentage'  => 'nullable|numeric|min:0|max:100',
            'additionalDetails.is_featured'          => 'nullable|boolean',
            'additionalImages'                       => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if ($request->is_paid) {
            $hasBank    = BankDetail::where('user_id', auth()->id())->exists();
            $hasAddress = ShippingAddress::where('user_id', auth()->id())->exists();

            if (!$hasBank || !$hasAddress) {
                return response()->json([
                    'success'      => false,
                    'code'         => 'REQUIREMENT_MISSING',
                    'message'      => 'You must configure your bank details and shipping address in your profile before listing items for sale.',
                    'requirements' => ['bank_details' => $hasBank, 'shipping_address' => $hasAddress]
                ], 400);
            }
        }

        try {
            DB::beginTransaction();

            $modelClass = $request->sellable_type === 'innovation' ? Innovation::class : Research::class;
            $item = $modelClass::findOrFail($request->sellable_id);

            if ($item->user_id !== auth()->id()) {
                return response()->json(['success' => false, 'message' => 'You do not own this item'], 403);
            }

            $item->is_paid = $request->is_paid;
            $item->price   = $request->is_paid ? $request->price : null;
            $item->save();

            $discountPercentage = $request->additionalDetails['discount_percentage'] ?? 0;
            $discountedPrice    = null;
            if ($discountPercentage > 0 && $request->is_paid && $request->price) {
                $discountedPrice = $request->price - ($request->price * $discountPercentage / 100);
            }

            $data = [
                'user_id'          => auth()->id(),
                'sellable_type'    => $modelClass,
                'sellable_id'      => $request->sellable_id,
                'title'            => $item->title,
                'description'      => $request->sellable_type === 'innovation' ? $item->description : $item->abstract,
                'thumbnail'        => $item->thumbnail,
                'additional_images'=> $request->additionalImages ?? [],
                'category'         => $item->category,
                'tags'             => $item->tags_array ?? [],
                'is_paid'          => $request->is_paid,
                'price'            => $request->is_paid ? $request->price : null,
                'discount_percentage' => $discountPercentage,
                'discounted_price' => $discountedPrice,
                'stock_quantity'   => $request->additionalDetails['stock_quantity'] ?? 1,
                'sku'              => $request->additionalDetails['sku'] ?? Str::upper(Str::random(8)),
                'condition'        => $request->additionalDetails['condition'] ?? 'new',
                'delivery_time'    => $request->additionalDetails['delivery_time'] ?? 5,
                'warranty_period'  => $request->additionalDetails['warranty_period'] ?? 6,
                'return_policy'    => $request->additionalDetails['return_policy'] ?? 30,
                'shipping_cost'    => $request->additionalDetails['shipping_cost'] ?? 0,
                'whats_included'   => $request->additionalDetails['whats_included'] ?? null,
                'specifications'   => $request->additionalDetails['specifications'] ?? [],
                'is_featured'      => $request->additionalDetails['is_featured'] ?? false,
                'type'             => $request->type ?? 'innovation',
                'status'           => 'active',
                'listed_at'        => now(),
            ];

            $sellingItem = SellingItem::where('sellable_type', $modelClass)
                ->where('sellable_id', $request->sellable_id)
                ->first();

            if ($sellingItem) {
                $sellingItem->update($data);
                $message = 'Selling item updated successfully';
            } else {
                $sellingItem = SellingItem::create($data);
                $message = 'Item added to selling list successfully';
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => $message, 'data' => $sellingItem]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to add item to selling list: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload additional images for selling item
     */
    public function uploadSellingImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $file     = $request->file('file');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path     = 'selling_items/' . $request->type . '/' . $filename;

            $s3Client = new S3Client([
                'region'      => config('filesystems.disks.s3.region'),
                'version'     => 'latest',
                'credentials' => [
                    'key'    => config('filesystems.disks.s3.key'),
                    'secret' => config('filesystems.disks.s3.secret'),
                ],
                'http' => ['verify' => false],
            ]);

            $result = $s3Client->putObject([
                'Bucket'      => config('filesystems.disks.s3.bucket'),
                'Key'         => $path,
                'Body'        => fopen($file->getRealPath(), 'rb'),
                'ContentType' => $file->getMimeType(),
            ]);

            return response()->json(['success' => true, 'url' => $result['ObjectURL'], 'message' => 'Image uploaded successfully']);

        } catch (\Exception $e) {
            Log::error('Image upload failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get all selling items for the authenticated user
     */
    public function getMySellingItems(Request $request)
    {
        try {
            $query = SellingItem::where('user_id', auth()->id())->with('sellable');

            if ($request->has('status')) $query->where('status', $request->status);
            if ($request->has('is_featured')) $query->where('is_featured', $request->boolean('is_featured'));
            if ($request->has('condition')) $query->where('condition', $request->condition);
            if ($request->has('min_price')) $query->where('price', '>=', $request->min_price);
            if ($request->has('max_price')) $query->where('price', '<=', $request->max_price);
            if ($request->has('in_stock')) $query->where('stock_quantity', '>', 0);

            $sortBy = $request->get('sort_by', 'latest');
            match ($sortBy) {
                'price_asc'  => $query->orderBy('price', 'asc'),
                'price_desc' => $query->orderBy('price', 'desc'),
                'popular'    => $query->orderBy('total_purchases', 'desc'),
                default      => $query->orderBy('created_at', 'desc'),
            };

            $items = $query->paginate($request->get('per_page', 10));

            $stats = [
                'total_items'      => SellingItem::where('user_id', auth()->id())->count(),
                'total_paid_items' => SellingItem::where('user_id', auth()->id())->where('is_paid', true)->count(),
                'total_free_items' => SellingItem::where('user_id', auth()->id())->where('is_paid', false)->count(),
                'total_revenue'    => SellingItem::where('user_id', auth()->id())->sum('total_revenue'),
                'total_purchases'  => SellingItem::where('user_id', auth()->id())->sum('total_purchases'),
                'total_views'      => SellingItem::where('user_id', auth()->id())->sum('total_views'),
                'total_stock'      => SellingItem::where('user_id', auth()->id())->sum('stock_quantity'),
                'featured_count'   => SellingItem::where('user_id', auth()->id())->where('is_featured', true)->count(),
            ];

            return response()->json(['success' => true, 'data' => $items, 'stats' => $stats]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch selling items: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get single selling item details for public view
     */
    public function show($id)
    {
        try {
            $item = SellingItem::with(['user.profile', 'sellable'])
                ->where('status', 'active')
                ->findOrFail($id);

            // Attach seller display info
            if ($item->user) {
                $profile = $item->user->profile;
                $item->seller_business_name = $profile->business_name ?? null;
                $item->seller_avatar        = $profile ? $profile->profile_image_url : null;
                $item->seller_display_name  = ($profile && $profile->business_name)
                    ? $profile->business_name
                    : $item->user->first_name . ' ' . $item->user->last_name;
            }

            // Never expose the digital file URL in the public product view
            $item->makeHidden('digital_file_url');

            $stats = ['average_rating' => 0, 'total_ratings' => 0, 'rating_breakdown' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]];

            if ($item->sellable_type && $item->sellable_id) {
                $isInnovation = str_contains($item->sellable_type, 'Innovation');
                $commentModel = $isInnovation
                    ? \App\Models\Innovation\InnovationComment::class
                    : \App\Models\Research\ResearchComment::class;
                $foreignKey   = $isInnovation ? 'innovation_id' : 'research_id';

                $rawStats = $commentModel::where($foreignKey, $item->sellable_id)
                    ->selectRaw('
                        AVG(rating) as average_rating,
                        COUNT(*) as total_ratings,
                        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                    ')->first();

                if ($rawStats) {
                    $stats = [
                        'average_rating'   => round($rawStats->average_rating ?? 0, 1),
                        'total_ratings'    => (int)($rawStats->total_ratings ?? 0),
                        'rating_breakdown' => [
                            5 => (int)($rawStats->five_star ?? 0),
                            4 => (int)($rawStats->four_star ?? 0),
                            3 => (int)($rawStats->three_star ?? 0),
                            2 => (int)($rawStats->two_star ?? 0),
                            1 => (int)($rawStats->one_star ?? 0),
                        ],
                    ];
                }
            }

            return response()->json(['success' => true, 'data' => $item, 'stats' => $stats]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Product not found: ' . $e->getMessage()], 404);
        }
    }

    /**
     * Get single selling item details for the owner
     */
    public function getSellingItem($id)
    {
        try {
            $item = SellingItem::with('sellable', 'user')->where('user_id', auth()->id())->findOrFail($id);
            return response()->json(['success' => true, 'data' => $item]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Selling item not found'], 404);
        }
    }

    /**
     * Update selling item
     */
    public function updateSellingItem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'is_paid'             => 'sometimes|boolean',
            'price'               => 'required_if:is_paid,true|nullable|numeric|min:0',
            'status'              => 'sometimes|in:active,sold_out,inactive',
            'stock_quantity'      => 'sometimes|integer|min:0',
            'discount_percentage' => 'sometimes|numeric|min:0|max:100',
            'is_featured'         => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $sellingItem = SellingItem::where('user_id', auth()->id())->findOrFail($id);

            if ($request->has('is_paid')) {
                $sellingItem->is_paid = $request->is_paid;
                $sellingItem->price   = $request->is_paid ? $request->price : null;

                if ($sellingItem->discount_percentage > 0 && $request->price) {
                    $sellingItem->discounted_price = $request->price - ($request->price * $sellingItem->discount_percentage / 100);
                }

                $originalItem = $sellingItem->sellable;
                if ($originalItem) {
                    $originalItem->is_paid = $request->is_paid;
                    $originalItem->price   = $request->is_paid ? $request->price : null;
                    $originalItem->save();
                }
            }

            if ($request->has('stock_quantity'))      $sellingItem->stock_quantity = $request->stock_quantity;
            if ($request->has('discount_percentage')) {
                $sellingItem->discount_percentage = $request->discount_percentage;
                if ($sellingItem->price) {
                    $sellingItem->discounted_price = $sellingItem->price - ($sellingItem->price * $request->discount_percentage / 100);
                }
            }
            if ($request->has('status'))      $sellingItem->status = $request->status;
            if ($request->has('is_featured')) $sellingItem->is_featured = $request->is_featured;

            $sellingItem->save();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Selling item updated successfully', 'data' => $sellingItem]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update selling item: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove from selling list
     */
    public function removeFromSelling($id)
    {
        try {
            DB::beginTransaction();

            $sellingItem = SellingItem::where('user_id', auth()->id())->findOrFail($id);

            $originalItem = $sellingItem->sellable;
            if ($originalItem) {
                $originalItem->is_paid = false;
                $originalItem->price   = null;
                $originalItem->save();
            }

            $sellingItem->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Item removed from selling list successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to remove item: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Track purchase (called after successful payment)
     */
    public function trackPurchase(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount'   => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $sellingItem = SellingItem::findOrFail($id);

            if (!$sellingItem->decreaseStock($request->quantity)) {
                return response()->json(['success' => false, 'message' => 'Insufficient stock'], 400);
            }

            $sellingItem->total_purchases += $request->quantity;
            $sellingItem->total_revenue   += $request->amount;
            $sellingItem->save();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Purchase tracked successfully', 'data' => $sellingItem]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to track purchase: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Initiate Purchase — returns PayHere form params
     */
    public function initiatePurchase(Request $request, $id)
    {
        $validator = Validator::make($request->all(), ['quantity' => 'required|integer|min:1']);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $item  = SellingItem::with('user')->findOrFail($id);
            $buyer = $request->user();

            if ($item->user_id === $buyer->id) {
                return response()->json(['success' => false, 'message' => 'You cannot purchase your own product.'], 400);
            }

            if ($item->stock_quantity < $request->quantity) {
                return response()->json(['success' => false, 'message' => 'Insufficient stock'], 400);
            }

            $sellerBank = BankDetail::where('user_id', $item->user_id)->orderBy('is_default', 'desc')->first();
            if (!$sellerBank) {
                return response()->json(['success' => false, 'code' => 'REQUIREMENT_MISSING_BANK', 'message' => 'The seller has not configured any bank details. Payment cannot be initiated.'], 400);
            }

            $buyerAddress = ShippingAddress::where('user_id', $buyer->id)->orderBy('is_default', 'desc')->first();
            if (!$buyerAddress) {
                return response()->json(['success' => false, 'code' => 'REQUIREMENT_MISSING_SHIPPING', 'message' => 'You need to add a shipping address to your profile before you can purchase items.'], 400);
            }

            $totalAmount     = ($item->discounted_price ?? $item->price) * $request->quantity;
            $formattedAmount = number_format($totalAmount, 2, '.', '');

            $sellerProfile   = $item->user->profile;
            $sellerBusiness  = $sellerProfile ? ($sellerProfile->business_name ?? ($item->user->first_name . ' ' . $item->user->last_name)) : null;

            $deadlineDays     = ($item->type === 'prototype') ? 4 : 14;
            $deliveryDeadline = now()->addDays($deadlineDays);

            $order = Order::create([
                'order_id_string'    => 'PENDING',
                'buyer_id'           => $buyer->id,
                'seller_id'          => $item->user_id,
                'selling_item_id'    => $item->id,
                'bank_detail_id'     => $sellerBank->id,
                'shipping_address_id'=> $buyerAddress->id,
                'quantity'           => $request->quantity,
                'amount'             => $totalAmount,
                'status'             => 'pending',
                'payment_method'     => 'payhere',
                'delivery_status'    => 'pending',
                'business_name'      => $sellerBusiness,
                'delivery_deadline'  => $deliveryDeadline,
            ]);

            $merchant_id     = config('services.payhere.merchant_id');
            $merchant_secret = config('services.payhere.merchant_secret');
            $order_id_string = 'ORD' . $order->id . 'T' . time();
            $currency        = 'LKR';

            $order->update(['order_id_string' => $order_id_string]);

            $hash = strtoupper(md5($merchant_id . $order_id_string . $formattedAmount . $currency . strtoupper(md5($merchant_secret))));

            return response()->json([
                'success' => true,
                'data'    => [
                    'sandbox'     => config('services.payhere.sandbox', true),
                    'merchant_id' => $merchant_id,
                    'order_id'    => $order_id_string,
                    'items'       => $item->title . ' (x' . $request->quantity . ')',
                    'amount'      => $formattedAmount,
                    'currency'    => $currency,
                    'hash'        => $hash,
                    'first_name'  => $buyer->first_name ?? 'Buyer',
                    'last_name'   => $buyer->last_name ?? 'User',
                    'email'       => $buyer->email,
                    'phone'       => $buyer->phone ?? '0771234567',
                    'address'     => 'No 1, Galle Road',
                    'city'        => 'Colombo',
                    'country'     => 'Sri Lanka',
                    'notify_url'  => url('/api/advertisements/payhere/notify'),
                    'return_url'  => env('FRONTEND_URL', 'http://localhost:5173') . '/profile/orders?status=success',
                    'cancel_url'  => env('FRONTEND_URL', 'http://localhost:5173') . '/profile/orders?status=cancel',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to initiate purchase: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Place a Cash on Delivery order
     */
    public function placeCodOrder(Request $request, $id)
    {
        $validator = Validator::make($request->all(), ['quantity' => 'required|integer|min:1']);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $item  = SellingItem::with('user')->findOrFail($id);
            $buyer = $request->user();

            if ($item->user_id === $buyer->id) {
                return response()->json(['success' => false, 'message' => 'You cannot purchase your own product.'], 400);
            }

            if ($item->stock_quantity < $request->quantity) {
                return response()->json(['success' => false, 'message' => 'Insufficient stock'], 400);
            }

            $buyerAddress = ShippingAddress::where('user_id', $buyer->id)->orderBy('is_default', 'desc')->first();
            if (!$buyerAddress) {
                return response()->json(['success' => false, 'code' => 'REQUIREMENT_MISSING_SHIPPING', 'message' => 'You need to add a shipping address before placing a COD order.'], 400);
            }

            $totalAmount     = ($item->discounted_price ?? $item->price) * $request->quantity;
            $order_id_string = 'COD' . time() . Str::upper(Str::random(4));

            $sellerProfile  = $item->user->profile ?? null;
            $sellerBusiness = $sellerProfile ? ($sellerProfile->business_name ?? ($item->user->first_name . ' ' . $item->user->last_name)) : null;

            $deadlineDays     = ($item->type === 'prototype') ? 4 : 14;
            $deliveryDeadline = now()->addDays($deadlineDays);

            $order = Order::create([
                'order_id_string'    => $order_id_string,
                'buyer_id'           => $buyer->id,
                'seller_id'          => $item->user_id,
                'selling_item_id'    => $item->id,
                'shipping_address_id'=> $buyerAddress->id,
                'quantity'           => $request->quantity,
                'amount'             => $totalAmount,
                'status'             => 'cod_pending',
                'payment_method'     => 'cod',
                'delivery_status'    => 'pending',
                'business_name'      => $sellerBusiness,
                'delivery_deadline'  => $deliveryDeadline,
            ]);

            // Reserve stock immediately for COD
            $item->stock_quantity = max(0, $item->stock_quantity - $request->quantity);
            $item->save();

            AdminNotification::notify(
                'new_order',
                'New COD Order Placed',
                "{$buyer->first_name} {$buyer->last_name} placed a Cash on Delivery order for "{$item->title}" — LKR " . number_format($totalAmount, 2) . ".",
                ['order_id' => $order->id, 'order_ref' => $order->order_id_string, 'amount' => $totalAmount]
            );

            return response()->json([
                'success' => true,
                'message' => 'COD order placed successfully',
                'data'    => $order->load(['sellingItem', 'shippingAddress']),
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to place COD order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Seller: update courier details for an order
     */
    public function updateCourierDetails(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'courier_name'    => 'required|string|max:255',
            'tracking_number' => 'required|string|max:255',
            'courier_phone'   => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $order = Order::where('seller_id', auth()->id())->findOrFail($id);

            $order->update([
                'courier_name'    => $request->courier_name,
                'tracking_number' => $request->tracking_number,
                'courier_phone'   => $request->courier_phone,
                'delivery_status' => 'dispatched',
            ]);

            return response()->json(['success' => true, 'message' => 'Courier details updated', 'data' => $order]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update courier details'], 500);
        }
    }

    /**
     * Seller: update delivery status for an order
     */
    public function updateDeliveryStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'delivery_status' => 'required|in:pending,dispatched,in_transit,delivered',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $order = Order::where('seller_id', auth()->id())->findOrFail($id);

            $updateData = ['delivery_status' => $request->delivery_status];

            if ($request->delivery_status === 'delivered') {
                $updateData['delivered_at']   = now();
                // COD orders become payout-eligible once delivery is confirmed
                if ($order->payment_method === 'cod') {
                    $updateData['payout_status'] = 'pending';
                }
            }

            $order->update($updateData);

            return response()->json(['success' => true, 'message' => 'Delivery status updated', 'data' => $order]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update delivery status'], 500);
        }
    }

    /**
     * Get orders bought by current user.
     * Exposes digital_file_url only for confirmed (paid / cod_pending) orders.
     */
    public function getMyPurchases(Request $request)
    {
        $orders = Order::where('buyer_id', auth()->id())
            ->with(['sellingItem', 'seller.profile', 'shippingAddress'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        $confirmed = ['paid', 'cod_pending'];

        $orders->getCollection()->transform(function ($order) use ($confirmed) {
            $item = $order->sellingItem;
            if ($item && $item->delivery_type === 'digital') {
                // Only expose the download URL after the order is confirmed
                $order->digital_download_url = in_array($order->status, $confirmed)
                    ? $item->digital_file_url
                    : null;
                $order->is_digital = true;
            } else {
                $order->is_digital = false;
                $order->digital_download_url = null;
            }
            return $order;
        });

        return response()->json(['success' => true, 'data' => $orders]);
    }

    /**
     * Get orders sold by current user
     */
    public function getMySales(Request $request)
    {
        $orders = Order::where('seller_id', auth()->id())
            ->with(['sellingItem', 'buyer.profile', 'bankDetail', 'shippingAddress'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json(['success' => true, 'data' => $orders]);
    }

    /**
     * Track view
     */
    public function trackView($id)
    {
        try {
            $sellingItem = SellingItem::findOrFail($id);
            $sellingItem->increment('total_views');
            return response()->json(['success' => true, 'message' => 'View tracked successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to track view'], 500);
        }
    }

    /**
     * Get selling statistics for dashboard
     */
    public function getSellingStats()
    {
        try {
            $userId = auth()->id();

            $stats = [
                'total_listings'   => SellingItem::where('user_id', $userId)->count(),
                'active_listings'  => SellingItem::where('user_id', $userId)->where('status', 'active')->count(),
                'paid_listings'    => SellingItem::where('user_id', $userId)->where('is_paid', true)->count(),
                'free_listings'    => SellingItem::where('user_id', $userId)->where('is_paid', false)->count(),
                'total_revenue'    => SellingItem::where('user_id', $userId)->sum('total_revenue'),
                'total_purchases'  => SellingItem::where('user_id', $userId)->sum('total_purchases'),
                'total_views'      => SellingItem::where('user_id', $userId)->sum('total_views'),
                'total_stock'      => SellingItem::where('user_id', $userId)->sum('stock_quantity'),
                'average_price'    => SellingItem::where('user_id', $userId)->where('is_paid', true)->avg('price') ?? 0,
                'featured_count'   => SellingItem::where('user_id', $userId)->where('is_featured', true)->count(),
                'low_stock_items'  => SellingItem::where('user_id', $userId)->where('stock_quantity', '<=', 5)->where('stock_quantity', '>', 0)->count(),
                'out_of_stock'     => SellingItem::where('user_id', $userId)->where('stock_quantity', 0)->count(),
                'recent_listings'  => SellingItem::where('user_id', $userId)->orderBy('created_at', 'desc')->limit(5)->get(['id', 'title', 'price', 'status', 'created_at']),
            ];

            return response()->json(['success' => true, 'data' => $stats]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch statistics: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancel/Delete a pending or cod_pending order
     */
    public function cancelOrder($id)
    {
        try {
            $order = Order::where('buyer_id', auth()->id())->where('id', $id)->firstOrFail();

            if (!in_array($order->status, ['pending', 'cod_pending'])) {
                return response()->json(['success' => false, 'message' => 'Only pending orders can be cancelled.'], 400);
            }

            // Restore stock for COD orders
            if ($order->status === 'cod_pending') {
                $item = SellingItem::find($order->selling_item_id);
                if ($item) {
                    $item->stock_quantity += $order->quantity;
                    $item->save();
                }
            }

            $order->delete();

            return response()->json(['success' => true, 'message' => 'Order cancelled successfully']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to cancel order'], 500);
        }
    }

    /**
     * Add a standalone product directly (no innovation/research parent required)
     * Accepts multipart form data with thumbnail file upload
     */
    public function addDirectProduct(Request $request)
    {
        // FormData sends booleans as "1"/"0" strings — cast before validation
        $input           = $request->all();
        $input['is_paid']    = filter_var($input['is_paid']    ?? false, FILTER_VALIDATE_BOOLEAN);
        $input['is_featured']= filter_var($input['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $validator = Validator::make($input, [
            'title'                => 'required|string|max:255',
            'description'          => 'nullable|string',
            'category'             => 'required|string|max:100',
            'listing_type'         => 'required|in:idea,prototype,ready_for_sale,service,educational,technology,innovation',
            'delivery_type'        => 'nullable|in:physical,digital',
            'digital_file'         => 'nullable|file|max:102400', // 100MB max, any file type
            'is_paid'              => 'required|boolean',
            'price'                => 'nullable|numeric|min:0',
            'thumbnail'            => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'stock_quantity'       => 'nullable|integer|min:1',
            'condition'            => 'nullable|in:new,like_new,excellent,good,fair',
            'discount_percentage'  => 'nullable|numeric|min:0|max:100',
            'delivery_time'        => 'nullable|integer|min:1',
            'warranty_period'      => 'nullable|integer|min:0',
            'return_policy'        => 'nullable|integer|min:0',
            'shipping_cost'        => 'nullable|numeric|min:0',
            'whats_included'       => 'nullable|string',
            'is_featured'          => 'nullable|boolean',
            'tags'                 => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Use the cast values for logic below
        $request->merge(['is_paid' => $input['is_paid'], 'is_featured' => $input['is_featured']]);

        if ($request->is_paid) {
            $hasBank    = BankDetail::where('user_id', auth()->id())->exists();
            $hasAddress = ShippingAddress::where('user_id', auth()->id())->exists();

            if (!$hasBank || !$hasAddress) {
                return response()->json([
                    'success'      => false,
                    'code'         => 'REQUIREMENT_MISSING',
                    'message'      => 'Configure your bank details and shipping address before listing paid items.',
                    'requirements' => ['bank_details' => $hasBank, 'shipping_address' => $hasAddress],
                ], 400);
            }
        }

        try {
            // Upload thumbnail to S3
            $file     = $request->file('thumbnail');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $s3Path   = 'selling_items/products/' . $filename;

            $s3Client = new S3Client([
                'region'      => config('filesystems.disks.s3.region'),
                'version'     => 'latest',
                'credentials' => ['key' => config('filesystems.disks.s3.key'), 'secret' => config('filesystems.disks.s3.secret')],
                'http'        => ['verify' => false],
            ]);

            $result       = $s3Client->putObject([
                'Bucket'      => config('filesystems.disks.s3.bucket'),
                'Key'         => $s3Path,
                'Body'        => fopen($file->getRealPath(), 'rb'),
                'ContentType' => $file->getMimeType(),
            ]);
            $thumbnailUrl = $result['ObjectURL'];

            // Upload digital file if provided
            $digitalFileUrl = null;
            if ($request->hasFile('digital_file')) {
                $digitalFile     = $request->file('digital_file');
                $digitalFilename = Str::uuid() . '_' . $digitalFile->getClientOriginalName();
                $digitalPath     = 'selling_items/digital/' . $digitalFilename;

                $s3Client->putObject([
                    'Bucket'             => config('filesystems.disks.s3.bucket'),
                    'Key'                => $digitalPath,
                    'Body'               => fopen($digitalFile->getRealPath(), 'rb'),
                    'ContentType'        => $digitalFile->getMimeType(),
                    'ContentDisposition' => 'attachment; filename="' . $digitalFile->getClientOriginalName() . '"',
                ]);

                $digitalFileUrl = 'https://' . config('filesystems.disks.s3.bucket') . '.s3.' . config('filesystems.disks.s3.region') . '.amazonaws.com/' . $digitalPath;
            }

            $discountPercentage = (float)($request->discount_percentage ?? 0);
            $price              = $request->is_paid ? (float)$request->price : null;
            $discountedPrice    = ($discountPercentage > 0 && $price)
                ? $price - ($price * $discountPercentage / 100)
                : null;

            $tags = $request->tags
                ? array_filter(array_map('trim', explode(',', $request->tags)))
                : [];

            $sellingItem = SellingItem::create([
                'user_id'             => auth()->id(),
                'sellable_type'       => null,
                'sellable_id'         => null,
                'title'               => $request->title,
                'description'         => $request->description,
                'thumbnail'           => $thumbnailUrl,
                'additional_images'   => [],
                'category'            => $request->category,
                'tags'                => $tags,
                'type'                => $request->listing_type,
                'is_paid'             => $request->is_paid,
                'price'               => $price,
                'discount_percentage' => $discountPercentage,
                'discounted_price'    => $discountedPrice,
                'stock_quantity'      => $request->stock_quantity ?? 1,
                'sku'                 => Str::upper(Str::random(8)),
                'condition'           => $request->condition ?? 'new',
                'delivery_time'       => $request->delivery_time ?? 5,
                'warranty_period'     => $request->warranty_period ?? 0,
                'return_policy'       => $request->return_policy ?? 0,
                'shipping_cost'       => $request->shipping_cost ?? 0,
                'whats_included'      => $request->whats_included,
                'specifications'      => [],
                'is_featured'         => $request->boolean('is_featured', false),
                'delivery_type'       => $request->delivery_type ?? 'physical',
                'digital_file_url'    => $digitalFileUrl,
                'status'              => 'active',
                'listed_at'           => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Product listed successfully', 'data' => $sellingItem]);

        } catch (\Exception $e) {
            Log::error('addDirectProduct failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add product: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get the authenticated user's directly-added standalone products
     */
    public function getMyProducts(Request $request)
    {
        $items = SellingItem::where('user_id', auth()->id())
            ->whereNull('sellable_type')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json(['success' => true, 'data' => $items]);
    }
}