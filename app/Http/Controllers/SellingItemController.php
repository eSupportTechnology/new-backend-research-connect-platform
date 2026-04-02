<?php
// app/Http/Controllers/SellingItemController.php

namespace App\Http\Controllers;


use App\Models\Innovation\Innovation;
use App\Models\Innovation\SellingItem;
use App\Models\Research\Research;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SellingItemController extends Controller
{
    /**
     * Add item to selling table
     */
    public function addToSelling(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sellable_type' => 'required|in:innovation,research',
            'sellable_id' => 'required|integer',
            'is_paid' => 'required|boolean',
            'price' => 'required_if:is_paid,true|nullable|numeric|min:0'
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
            $item = null;
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

            // Check if already in selling table
            $sellingItem = SellingItem::where('sellable_type', $modelClass)
                ->where('sellable_id', $request->sellable_id)
                ->first();

            $data = [
                'user_id' => auth()->id(),
                'sellable_type' => $modelClass,
                'sellable_id' => $request->sellable_id,
                'title' => $item->title,
                'description' => $request->sellable_type === 'innovation' ? $item->description : $item->abstract,
                'thumbnail' => $item->thumbnail,
                'category' => $item->category,
                'tags' => $item->tags_array ?? [],
                'is_paid' => $request->is_paid,
                'price' => $request->is_paid ? $request->price : null,
                'status' => 'active',
                'listed_at' => now()
            ];

            if ($sellingItem) {
                // Update existing record
                $sellingItem->update($data);
                $message = 'Selling item updated successfully';
            } else {
                // Create new record
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
     * Get all selling items for the authenticated user
     */
    public function getMySellingItems(Request $request)
    {
        try {
            $items = SellingItem::where('user_id', auth()->id())
                ->with('sellable')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            // Add additional statistics
            $stats = [
                'total_items' => SellingItem::where('user_id', auth()->id())->count(),
                'total_paid_items' => SellingItem::where('user_id', auth()->id())->where('is_paid', true)->count(),
                'total_revenue' => SellingItem::where('user_id', auth()->id())->sum('total_revenue'),
                'total_purchases' => SellingItem::where('user_id', auth()->id())->sum('total_purchases'),
                'total_views' => SellingItem::where('user_id', auth()->id())->sum('total_views')
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
            'status' => 'sometimes|in:active,sold_out,inactive'
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

                // Also update the original item
                $originalItem = $sellingItem->sellable;
                if ($originalItem) {
                    $originalItem->is_paid = $request->is_paid;
                    $originalItem->price = $request->is_paid ? $request->price : null;
                    $originalItem->save();
                }
            }

            if ($request->has('status')) {
                $sellingItem->status = $request->status;
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
            'amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sellingItem = SellingItem::findOrFail($id);

            $sellingItem->total_purchases += 1;
            $sellingItem->total_revenue += $request->amount;
            $sellingItem->save();

            return response()->json([
                'success' => true,
                'message' => 'Purchase tracked successfully',
                'data' => $sellingItem
            ]);

        } catch (\Exception $e) {
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
                'average_price' => SellingItem::where('user_id', $userId)
                        ->where('is_paid', true)
                        ->avg('price') ?? 0,
                'recent_listings' => SellingItem::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
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
