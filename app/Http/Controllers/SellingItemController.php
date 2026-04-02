<?php
// app/Http/Controllers/SellingItemController.php

namespace App\Http\Controllers;

use App\Models\Innovation\Innovation;
use App\Models\Innovation\SellingItem;
use App\Models\Research\Research;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;

class SellingItemController extends Controller
{
    /**
     * Add item to selling table with all details
     */
    public function addToSelling(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sellable_type' => 'required|in:innovation,research',
            'sellable_id' => 'required|integer',
            'is_paid' => 'required|boolean',
            'price' => 'required_if:is_paid,true|nullable|numeric|min:0',
            'additionalDetails' => 'required|array',
            'additionalDetails.stock_quantity' => 'nullable|integer|min:1',
            'additionalDetails.sku' => 'nullable|string|max:100',
            'additionalDetails.condition' => 'nullable|string|in:new,like_new,excellent,good,fair',
            'additionalDetails.delivery_time' => 'nullable|integer|min:1',
            'additionalDetails.warranty_period' => 'nullable|integer|min:0',
            'additionalDetails.return_policy' => 'nullable|integer|min:0',
            'additionalDetails.shipping_cost' => 'nullable|numeric|min:0',
            'additionalDetails.whats_included' => 'nullable|string',
            'additionalDetails.specifications' => 'nullable|array',
            'additionalDetails.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'additionalDetails.is_featured' => 'nullable|boolean',
            'additionalImages' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get the original item
            $modelClass = $request->sellable_type === 'innovation' ? Innovation::class : Research::class;
            $item = $modelClass::findOrFail($request->sellable_id);

            // Check if user owns this item
            if ($item->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not own this item'
                ], 403);
            }

            // Update original item's pricing
            $item->is_paid = $request->is_paid;
            $item->price = $request->is_paid ? $request->price : null;
            $item->save();

            // Calculate discounted price
            $discountPercentage = $request->additionalDetails['discount_percentage'] ?? 0;
            $discountedPrice = null;
            if ($discountPercentage > 0 && $request->is_paid && $request->price) {
                $discountedPrice = $request->price - ($request->price * $discountPercentage / 100);
            }

            // Prepare data for selling table
            $data = [
                'user_id' => auth()->id(),
                'sellable_type' => $modelClass,
                'sellable_id' => $request->sellable_id,
                'title' => $item->title,
                'description' => $request->sellable_type === 'innovation' ? $item->description : $item->abstract,
                'thumbnail' => $item->thumbnail,
                'additional_images' => $request->additionalImages ?? [],
                'category' => $item->category,
                'tags' => $item->tags_array ?? [],
                'is_paid' => $request->is_paid,
                'price' => $request->is_paid ? $request->price : null,
                'discount_percentage' => $discountPercentage,
                'discounted_price' => $discountedPrice,
                'stock_quantity' => $request->additionalDetails['stock_quantity'] ?? 1,
                'sku' => $request->additionalDetails['sku'] ?? Str::upper(Str::random(8)),
                'condition' => $request->additionalDetails['condition'] ?? 'new',
                'delivery_time' => $request->additionalDetails['delivery_time'] ?? 5,
                'warranty_period' => $request->additionalDetails['warranty_period'] ?? 6,
                'return_policy' => $request->additionalDetails['return_policy'] ?? 30,
                'shipping_cost' => $request->additionalDetails['shipping_cost'] ?? 0,
                'whats_included' => $request->additionalDetails['whats_included'] ?? null,
                'specifications' => $request->additionalDetails['specifications'] ?? [],
                'is_featured' => $request->additionalDetails['is_featured'] ?? false,
                'status' => 'active',
                'listed_at' => now()
            ];

            // Check if already exists
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

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $sellingItem
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to selling list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload additional images for selling item
     */
    public function uploadSellingImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'type' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = 'selling_items/' . $request->type . '/' . $filename;

            // Upload to S3
            $s3Client = new S3Client([
                'region' => config('filesystems.disks.s3.region'),
                'version' => 'latest',
                'credentials' => [
                    'key' => config('filesystems.disks.s3.key'),
                    'secret' => config('filesystems.disks.s3.secret'),
                ],
                'http' => ['verify' => false]
            ]);

            $result = $s3Client->putObject([
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $path,
                'Body' => fopen($file->getRealPath(), 'rb'),
                'ContentType' => $file->getMimeType(),
            ]);

            return response()->json([
                'success' => true,
                'url' => $result['ObjectURL'],
                'message' => 'Image uploaded successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Image upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all selling items for the authenticated user
     */
    public function getMySellingItems(Request $request)
    {
        try {
            $query = SellingItem::where('user_id', auth()->id())
                ->with('sellable');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('is_featured')) {
                $query->where('is_featured', $request->boolean('is_featured'));
            }

            if ($request->has('condition')) {
                $query->where('condition', $request->condition);
            }

            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            if ($request->has('in_stock')) {
                $query->where('stock_quantity', '>', 0);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'latest');
            switch ($sortBy) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'popular':
                    $query->orderBy('total_purchases', 'desc');
                    break;
                case 'latest':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            $items = $query->paginate($request->get('per_page', 10));

            // Add additional statistics
            $stats = [
                'total_items' => SellingItem::where('user_id', auth()->id())->count(),
                'total_paid_items' => SellingItem::where('user_id', auth()->id())->where('is_paid', true)->count(),
                'total_free_items' => SellingItem::where('user_id', auth()->id())->where('is_paid', false)->count(),
                'total_revenue' => SellingItem::where('user_id', auth()->id())->sum('total_revenue'),
                'total_purchases' => SellingItem::where('user_id', auth()->id())->sum('total_purchases'),
                'total_views' => SellingItem::where('user_id', auth()->id())->sum('total_views'),
                'total_stock' => SellingItem::where('user_id', auth()->id())->sum('stock_quantity'),
                'featured_count' => SellingItem::where('user_id', auth()->id())->where('is_featured', true)->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $items,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch selling items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single selling item details
     */
    public function getSellingItem($id)
    {
        try {
            $item = SellingItem::with('sellable', 'user')
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $item
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Selling item not found'
            ], 404);
        }
    }

    /**
     * Update selling item
     */
    public function updateSellingItem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'is_paid' => 'sometimes|boolean',
            'price' => 'required_if:is_paid,true|nullable|numeric|min:0',
            'status' => 'sometimes|in:active,sold_out,inactive',
            'stock_quantity' => 'sometimes|integer|min:0',
            'discount_percentage' => 'sometimes|numeric|min:0|max:100',
            'is_featured' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $sellingItem = SellingItem::where('user_id', auth()->id())->findOrFail($id);

            if ($request->has('is_paid')) {
                $sellingItem->is_paid = $request->is_paid;
                $sellingItem->price = $request->is_paid ? $request->price : null;

                // Update discounted price if discount exists
                if ($sellingItem->discount_percentage > 0 && $request->price) {
                    $sellingItem->discounted_price = $request->price - ($request->price * $sellingItem->discount_percentage / 100);
                }

                // Also update the original item
                $originalItem = $sellingItem->sellable;
                if ($originalItem) {
                    $originalItem->is_paid = $request->is_paid;
                    $originalItem->price = $request->is_paid ? $request->price : null;
                    $originalItem->save();
                }
            }

            if ($request->has('stock_quantity')) {
                $sellingItem->stock_quantity = $request->stock_quantity;
            }

            if ($request->has('discount_percentage')) {
                $sellingItem->discount_percentage = $request->discount_percentage;
                if ($sellingItem->price) {
                    $sellingItem->discounted_price = $sellingItem->price - ($sellingItem->price * $request->discount_percentage / 100);
                }
            }

            if ($request->has('status')) {
                $sellingItem->status = $request->status;
            }

            if ($request->has('is_featured')) {
                $sellingItem->is_featured = $request->is_featured;
            }

            $sellingItem->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Selling item updated successfully',
                'data' => $sellingItem
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update selling item: ' . $e->getMessage()
            ], 500);
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

            // Update original item
            $originalItem = $sellingItem->sellable;
            if ($originalItem) {
                $originalItem->is_paid = false;
                $originalItem->price = null;
                $originalItem->save();
            }

            $sellingItem->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item removed from selling list successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track purchase (to be called when someone buys an item)
     */
    public function trackPurchase(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $sellingItem = SellingItem::findOrFail($id);

            // Check stock
            if (!$sellingItem->decreaseStock($request->quantity)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock'
                ], 400);
            }

            $sellingItem->total_purchases += $request->quantity;
            $sellingItem->total_revenue += $request->amount;
            $sellingItem->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase tracked successfully',
                'data' => $sellingItem
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to track purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track view
     */
    public function trackView($id)
    {
        try {
            $sellingItem = SellingItem::findOrFail($id);
            $sellingItem->total_views += 1;
            $sellingItem->save();

            return response()->json([
                'success' => true,
                'message' => 'View tracked successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track view'
            ], 500);
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
                'total_listings' => SellingItem::where('user_id', $userId)->count(),
                'active_listings' => SellingItem::where('user_id', $userId)->where('status', 'active')->count(),
                'paid_listings' => SellingItem::where('user_id', $userId)->where('is_paid', true)->count(),
                'free_listings' => SellingItem::where('user_id', $userId)->where('is_paid', false)->count(),
                'total_revenue' => SellingItem::where('user_id', $userId)->sum('total_revenue'),
                'total_purchases' => SellingItem::where('user_id', $userId)->sum('total_purchases'),
                'total_views' => SellingItem::where('user_id', $userId)->sum('total_views'),
                'total_stock' => SellingItem::where('user_id', $userId)->sum('stock_quantity'),
                'average_price' => SellingItem::where('user_id', $userId)
                        ->where('is_paid', true)
                        ->avg('price') ?? 0,
                'featured_count' => SellingItem::where('user_id', $userId)
                    ->where('is_featured', true)
                    ->count(),
                'low_stock_items' => SellingItem::where('user_id', $userId)
                    ->where('stock_quantity', '<=', 5)
                    ->where('stock_quantity', '>', 0)
                    ->count(),
                'out_of_stock' => SellingItem::where('user_id', $userId)
                    ->where('stock_quantity', 0)
                    ->count(),
                'recent_listings' => SellingItem::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(['id', 'title', 'price', 'status', 'created_at'])
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
