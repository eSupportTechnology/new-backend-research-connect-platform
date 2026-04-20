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
        ])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_id_string', 'like', "%{$search}%")
                  ->orWhereHas('buyer', fn($u) => $u->where('email', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%"))
                  ->orWhereHas('seller', fn($u) => $u->where('email', 'like', "%{$search}%"));
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
                fputcsv($out, ['Order ID', 'Buyer', 'Seller', 'Item', 'Qty', 'Amount (LKR)', 'Method', 'Status', 'Date']);
                Order::with(['buyer', 'seller', 'sellingItem'])
                    ->where('status', 'paid')
                    ->orderByDesc('created_at')
                    ->chunk(200, function ($orders) use ($out) {
                        foreach ($orders as $o) {
                            fputcsv($out, [
                                $o->order_id_string,
                                $o->buyer ? "{$o->buyer->first_name} {$o->buyer->last_name} ({$o->buyer->email})" : '-',
                                $o->seller ? "{$o->seller->first_name} {$o->seller->last_name} ({$o->seller->email})" : '-',
                                $o->sellingItem->title ?? '-',
                                $o->quantity,
                                number_format($o->amount, 2),
                                $o->payhere_method ?? '-',
                                $o->status,
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