<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Innovation\SellingItem;
use App\Models\Order;

/**
 * Ratings on items that are listed in the store are reviews of a product, so
 * only people who actually bought it may leave one. Items that were never
 * listed keep the open community commenting behaviour.
 */
trait VerifiesStorePurchase
{
    /**
     * Orders that count as a completed purchase. Mirrors the confirmed-order
     * set used when exposing digital downloads in SellingItemController.
     */
    private array $purchasedOrderStatuses = ['paid', 'cod_pending'];

    /**
     * Returns an error response when the current user may not review this
     * item, or null when they may.
     */
    private function denyIfNotPurchaser(string $sellableType, $sellableId)
    {
        $listings = SellingItem::where('sellable_type', $sellableType)
            ->where('sellable_id', $sellableId)
            ->get();

        // Never listed in the store — this is an ordinary community comment.
        if ($listings->isEmpty()) {
            return null;
        }

        $userId = auth()->id();

        if ($listings->contains(fn ($item) => $item->user_id === $userId)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot review your own product.',
            ], 403);
        }

        $hasPurchased = Order::where('buyer_id', $userId)
            ->whereIn('selling_item_id', $listings->pluck('id'))
            ->whereIn('status', $this->purchasedOrderStatuses)
            ->exists();

        if (!$hasPurchased) {
            return response()->json([
                'success' => false,
                'message' => 'Only verified buyers can review this product.',
            ], 403);
        }

        return null;
    }
}