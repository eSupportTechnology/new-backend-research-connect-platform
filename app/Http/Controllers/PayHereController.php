<?php

namespace App\Http\Controllers;

use App\Models\Advertisement\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayHereController extends Controller
{
    /**
     * Handle PayHere Notify (Callback)
     */
    public function notify(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $order_id = $request->order_id; // e.g., AD-5-1618584841
        $payhere_amount = $request->payhere_amount;
        $payhere_currency = $request->payhere_currency;
        $status_code = $request->status_code;
        $md5sig = $request->md5sig;

        $merchant_secret = env('PAYHERE_MERCHANT_SECRET');

        // Verify Signature
        $local_md5sig = strtoupper(
            md5(
                $merchant_id . 
                $order_id . 
                $payhere_amount . 
                $payhere_currency . 
                $status_code . 
                strtoupper(md5($merchant_secret))
            )
        );

        if ($local_md5sig === $md5sig) {
            // Extract Ad ID from order_id
            $parts = explode('-', $order_id);
            if (isset($parts[1])) {
                $ad_id = $parts[1];
                $ad = Advertisement::find($ad_id);

                if ($ad) {
                    if ($status_code == 2) { // 2 = Success
                        $ad->update([
                            'payment_status' => 'paid',
                            'payment_id' => $request->payment_id,
                        ]);
                        Log::info("PayHere: Ad payment successful. ID: $ad_id");
                    } else if ($status_code == 0) { // 0 = Pending
                        Log::info("PayHere: Ad payment pending. ID: $ad_id");
                    } else {
                        $ad->update(['payment_status' => 'failed']);
                        Log::warning("PayHere: Ad payment failed. Status: $status_code, ID: $ad_id");
                    }
                }
            }
        } else {
            Log::error("PayHere: Invalid signature received for Order: $order_id");
        }

        return response('OK', 200);
    }
}
