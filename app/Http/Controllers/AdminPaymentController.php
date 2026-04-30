<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Advertisement\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPaymentController extends Controller
{
    public function getOverview(Request $request)
    {
        $range = $request->get('range', '30d');
        $startDate = $this->resolveStartDate($range);

        $adRevenue = Advertisement::where('payment_status', 'paid')->sum('price');
        $storeRevenue = Order::where('status', 'paid')->sum('amount');

        $overview = [
            'total_revenue'     => round($adRevenue + $storeRevenue, 2),
            'ad_revenue'        => round($adRevenue, 2),
            'store_revenue'     => round($storeRevenue, 2),
            'total_orders'      => Order::count(),
            'paid_orders'       => Order::where('status', 'paid')->count(),
            'pending_orders'    => Order::where('status', 'pending')->count(),
            'cod_orders'        => Order::where('status', 'cod_pending')->count(),
            'failed_orders'     => Order::where('status', 'failed')->count(),
            'total_ads_paid'    => Advertisement::where('payment_status', 'paid')->count(),
            'total_ads_pending' => Advertisement::where('payment_status', 'unpaid')->count(),
            'revenue_trend'     => $this->getRevenueTrend(),
        ];

        return response()->json(['success' => true, 'data' => $overview]);
    }

    public function getOrders(Request $request)
    {
        $query = Order::with([
            'buyer:id,first_name,last_name,email',
            'seller:id,first_name,last_name,email',
            'sellingItem:id,title,price',
            'shippingAddress',
        ])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_id_string', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%")
                  ->orWhereHas('buyer', fn($u) => $u->where('email', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%"))
                  ->orWhereHas('seller', fn($u) => $u->where('email', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $orders = $query->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function getAdPayments(Request $request)
    {
        $query = Advertisement::with(['user:id,first_name,last_name,email'])->latest();

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('email', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $ads = $query->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $ads]);
    }

    public function exportPayments(Request $request)
    {
        $type = $request->get('type', 'orders'); // orders | ads

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payments_export_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($type) {
            $out = fopen('php://output', 'w');

            if ($type === 'orders') {
                fputcsv($out, ['Order ID', 'Buyer', 'Seller', 'Business Name', 'Item', 'Qty', 'Amount (LKR)', 'Payment Method', 'Payment Status', 'Delivery Status', 'Courier', 'Tracking No.', 'Date']);
                Order::with(['buyer', 'seller', 'sellingItem'])
                    ->orderByDesc('created_at')
                    ->chunk(200, function ($orders) use ($out) {
                        foreach ($orders as $o) {
                            fputcsv($out, [
                                $o->order_id_string,
                                $o->buyer ? "{$o->buyer->first_name} {$o->buyer->last_name} ({$o->buyer->email})" : '-',
                                $o->seller ? "{$o->seller->first_name} {$o->seller->last_name} ({$o->seller->email})" : '-',
                                $o->business_name ?? '-',
                                $o->sellingItem->title ?? '-',
                                $o->quantity,
                                number_format($o->amount, 2),
                                $o->payment_method === 'cod' ? 'Cash on Delivery' : 'PayHere',
                                $o->status,
                                $o->delivery_status ?? 'pending',
                                $o->courier_name ?? '-',
                                $o->tracking_number ?? '-',
                                $o->created_at->format('Y-m-d H:i'),
                            ]);
                        }
                    });
            } else {
                fputcsv($out, ['Ad ID', 'Type', 'Title', 'Advertiser', 'Amount (LKR)', 'Payment Status', 'Payment ID', 'Date']);
                Advertisement::with('user')
                    ->where('payment_status', 'paid')
                    ->orderByDesc('created_at')
                    ->chunk(200, function ($ads) use ($out) {
                        foreach ($ads as $ad) {
                            fputcsv($out, [
                                $ad->id,
                                ucfirst($ad->type),
                                $ad->title,
                                $ad->user ? "{$ad->user->first_name} {$ad->user->last_name} ({$ad->user->email})" : '-',
                                number_format($ad->price, 2),
                                $ad->payment_status,
                                $ad->payment_id ?? '-',
                                $ad->created_at->format('Y-m-d H:i'),
                            ]);
                        }
                    });
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * List all paid orders pending payout to sellers
     */
    public function getPendingPayouts(Request $request)
    {
        $query = Order::with([
                'seller:id,first_name,last_name,email',
                'buyer:id,first_name,last_name,email',
                'sellingItem:id,title',
                'bankDetail',
            ])
            ->where('status', 'paid')
            ->orderBy('created_at', 'asc'); // oldest first — pay in order

        if ($request->filled('payout_status')) {
            $query->where('payout_status', $request->payout_status);
        } else {
            $query->where('payout_status', 'pending'); // default: show unpaid
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_id_string', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%")
                  ->orWhereHas('seller', fn($u) => $u->where('email', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->paginate($request->get('per_page', 15));

        // Summary totals
        $pendingTotal  = Order::where('status', 'paid')->where('payout_status', 'pending')->sum('amount');
        $paidOutTotal  = Order::where('status', 'paid')->where('payout_status', 'paid_out')->sum('amount');
        $pendingCount  = Order::where('status', 'paid')->where('payout_status', 'pending')->count();
        $paidOutCount  = Order::where('status', 'paid')->where('payout_status', 'paid_out')->count();

        return response()->json([
            'success' => true,
            'data'    => $orders,
            'summary' => [
                'pending_amount'  => round($pendingTotal, 2),
                'paid_out_amount' => round($paidOutTotal, 2),
                'pending_count'   => $pendingCount,
                'paid_out_count'  => $paidOutCount,
            ],
        ]);
    }

    /**
     * Mark an order as paid out to the seller
     */
    public function markPayout(Request $request, $id)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'payout_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $order = Order::where('status', 'paid')->findOrFail($id);

            if ($order->payout_status === 'paid_out') {
                return response()->json(['success' => false, 'message' => 'This order has already been paid out.'], 400);
            }

            $order->update([
                'payout_status' => 'paid_out',
                'payout_notes'  => $request->payout_notes,
                'paid_out_at'   => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payout marked successfully',
                'data'    => $order->load(['seller:id,first_name,last_name,email', 'bankDetail']),
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Order not found or not eligible'], 404);
        }
    }

    /**
     * Bulk mark multiple orders as paid out
     */
    public function bulkMarkPayout(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'order_ids'    => 'required|array|min:1',
            'order_ids.*'  => 'integer',
            'payout_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $updated = Order::where('status', 'paid')
            ->where('payout_status', 'pending')
            ->whereIn('id', $request->order_ids)
            ->update([
                'payout_status' => 'paid_out',
                'payout_notes'  => $request->payout_notes,
                'paid_out_at'   => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$updated} order(s) marked as paid out",
            'updated' => $updated,
        ]);
    }

    public function getTransactionAnalytics(Request $request)
    {
        // Summary counts
        $stats = [
            'total_orders'    => Order::count(),
            'total_revenue'   => round(Order::where('status', 'paid')->sum('amount'), 2),
            'cod_orders'      => Order::where('payment_method', 'cod')->count(),
            'payhere_orders'  => Order::where('payment_method', 'payhere')->count(),
            'paid'            => Order::where('status', 'paid')->count(),
            'cod_pending'     => Order::where('status', 'cod_pending')->count(),
            'pending'         => Order::where('status', 'pending')->count(),
            'failed'          => Order::where('status', 'failed')->count(),
            'cancelled'       => Order::where('status', 'cancelled')->count(),

            // Delivery funnel
            'delivery_pending'    => Order::where('delivery_status', 'pending')->count(),
            'delivery_dispatched' => Order::where('delivery_status', 'dispatched')->count(),
            'delivery_in_transit' => Order::where('delivery_status', 'in_transit')->count(),
            'delivery_delivered'  => Order::where('delivery_status', 'delivered')->count(),
        ];

        // Top buyers
        $topBuyers = Order::select(
                'buyer_id',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(amount) as total_spent')
            )
            ->with('buyer:id,first_name,last_name,email')
            ->groupBy('buyer_id')
            ->orderByDesc('total_orders')
            ->limit(8)
            ->get()
            ->map(fn($r) => [
                'user'         => $r->buyer,
                'total_orders' => $r->total_orders,
                'total_spent'  => round($r->total_spent, 2),
            ]);

        // Top sellers
        $topSellers = Order::select(
                'seller_id',
                DB::raw('COUNT(*) as total_sales'),
                DB::raw('SUM(amount) as total_revenue'),
                'business_name'
            )
            ->with('seller:id,first_name,last_name,email')
            ->groupBy('seller_id', 'business_name')
            ->orderByDesc('total_sales')
            ->limit(8)
            ->get()
            ->map(fn($r) => [
                'user'          => $r->seller,
                'business_name' => $r->business_name,
                'total_sales'   => $r->total_sales,
                'total_revenue' => round($r->total_revenue, 2),
            ]);

        // Recent orders (last 20 across all statuses)
        $recentOrders = Order::with([
                'buyer:id,first_name,last_name,email',
                'seller:id,first_name,last_name,email',
                'sellingItem:id,title,delivery_type',
                'shippingAddress',
            ])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => compact('stats', 'topBuyers', 'topSellers', 'recentOrders'),
        ]);
    }

    private function getRevenueTrend(): array
    {
        $months = collect(range(5, 0))->map(function ($i) {
            return now()->subMonths($i)->format('Y-m');
        });

        $adByMonth = Advertisement::where('payment_status', 'paid')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(price) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $storeByMonth = Order::where('status', 'paid')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        return $months->map(function ($month) use ($adByMonth, $storeByMonth) {
            return [
                'month'         => date('M', strtotime($month . '-01')),
                'ad_revenue'    => round((float) ($adByMonth[$month] ?? 0), 2),
                'store_revenue' => round((float) ($storeByMonth[$month] ?? 0), 2),
            ];
        })->values()->toArray();
    }

    private function resolveStartDate(string $range): string
    {
        return match ($range) {
            '24h'  => now()->subDay()->toDateString(),
            '7d'   => now()->subDays(7)->toDateString(),
            '90d'  => now()->subDays(90)->toDateString(),
            default => now()->subDays(30)->toDateString(),
        };
    }
}