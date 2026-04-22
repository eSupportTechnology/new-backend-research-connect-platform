<?php

namespace App\Http\Controllers;

use App\Models\MembershipPayment;
use App\Models\RegisterUsers\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MembershipController extends Controller
{
    const UPGRADE_PRICES = [
        'bronze_to_silver' => 2500.00,
        'silver_to_gold'   => 4500.00,
        'bronze_to_gold'   => 6000.00,
    ];

    const TIER_ORDER = ['bronze' => 1, 'silver' => 2, 'gold' => 3];

    public function info(Request $request)
    {
        $user         = $request->user();
        $current      = $user->membership_tier ?? 'bronze';
        $uploadCount  = $user->approvedUploadCount();

        $thresholds   = User::TIER_THRESHOLDS;
        $progress     = null;

        if ($current === 'bronze') {
            $progress = [
                'next_tier'   => 'silver',
                'current'     => $uploadCount,
                'required'    => $thresholds['bronze_to_silver'],
                'percentage'  => min(100, round(($uploadCount / $thresholds['bronze_to_silver']) * 100)),
            ];
        } elseif ($current === 'silver') {
            $progress = [
                'next_tier'   => 'gold',
                'current'     => $uploadCount,
                'required'    => $thresholds['silver_to_gold'],
                'percentage'  => min(100, round(($uploadCount / $thresholds['silver_to_gold']) * 100)),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'current_tier'    => $current,
                'tier_badge'      => $user->tier_badge,
                'upload_count'    => $uploadCount,
                'tier_upgraded_at'=> $user->tier_upgraded_at,
                'upgrade_source'  => $user->tier_upgrade_source,
                'progress'        => $progress,
                'upgrade_plans'   => $this->getUpgradePlans($current),
            ]
        ]);
    }

    public function getPaymentParams(Request $request)
    {
        $request->validate([
            'target_tier' => 'required|in:silver,gold',
        ]);

        $user       = $request->user();
        $current    = $user->membership_tier ?? 'bronze';
        $targetTier = $request->target_tier;

        if (self::TIER_ORDER[$targetTier] <= self::TIER_ORDER[$current]) {
            return response()->json([
                'success' => false,
                'message' => 'You already have this tier or higher.',
            ], 400);
        }

        $priceKey = $current . '_to_' . $targetTier;
        $amount   = self::UPGRADE_PRICES[$priceKey] ?? null;

        if (!$amount) {
            return response()->json(['success' => false, 'message' => 'Invalid upgrade path.'], 400);
        }

        $orderId = 'TIER' . $user->id . 'TO' . strtoupper($targetTier) . 'T' . time();

        $payment = MembershipPayment::create([
            'user_id'         => $user->id,
            'from_tier'       => $current,
            'to_tier'         => $targetTier,
            'amount'          => $amount,
            'order_id_string' => $orderId,
            'status'          => 'pending',
        ]);

        $merchantId     = env('PAYHERE_MERCHANT_ID');
        $merchantSecret = env('PAYHERE_MERCHANT_SECRET');
        $hash           = strtoupper(md5(
            $merchantId .
            $orderId .
            number_format($amount, 2, '.', '') .
            'LKR' .
            strtoupper(md5($merchantSecret))
        ));

        return response()->json([
            'success' => true,
            'data'    => [
                'merchant_id'  => $merchantId,
                'return_url'   => env('APP_FRONTEND_URL', 'http://localhost:5173') . '/membership?status=success',
                'cancel_url'   => env('APP_FRONTEND_URL', 'http://localhost:5173') . '/membership?status=cancelled',
                'notify_url'   => env('APP_URL', 'http://localhost:8000') . '/api/payhere/notify',
                'order_id'     => $orderId,
                'items'        => ucfirst($current) . ' → ' . ucfirst($targetTier) . ' Membership Upgrade',
                'currency'     => 'LKR',
                'amount'       => number_format($amount, 2, '.', ''),
                'first_name'   => $user->first_name,
                'last_name'    => $user->last_name,
                'email'        => $user->email,
                'phone'        => '0000000000',
                'address'      => 'N/A',
                'city'         => 'N/A',
                'country'      => 'Sri Lanka',
                'hash'         => $hash,
                'sandbox'      => env('PAYHERE_SANDBOX', true),
            ]
        ]);
    }

    private function getUpgradePlans(string $current): array
    {
        $plans = [];

        if ($current === 'bronze') {
            $plans[] = [
                'from'     => 'bronze',
                'to'       => 'silver',
                'price'    => self::UPGRADE_PRICES['bronze_to_silver'],
                'currency' => 'LKR',
                'features' => ['Full research & innovation access', 'Full marketplace access', 'Silver badge'],
            ];
            $plans[] = [
                'from'     => 'bronze',
                'to'       => 'gold',
                'price'    => self::UPGRADE_PRICES['bronze_to_gold'],
                'currency' => 'LKR',
                'features' => ['VIP Gold badge', 'All Silver features', 'Investor Zone VIP', 'Download research PDFs'],
            ];
        } elseif ($current === 'silver') {
            $plans[] = [
                'from'     => 'silver',
                'to'       => 'gold',
                'price'    => self::UPGRADE_PRICES['silver_to_gold'],
                'currency' => 'LKR',
                'features' => ['VIP Gold badge', 'Investor Zone VIP', 'Download research PDFs'],
            ];
        }

        return $plans;
    }
}