<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\Advertisement\Advertisement;
use App\Models\MembershipPayment;
use App\Models\Order;
use App\Models\VideoUploadPayment;
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
        $order_id = $request->order_id; // e.g., AD-5-1618584841 or ORD123T1618584841
        $payhere_amount = $request->payhere_amount;
        $payhere_currency = $request->payhere_currency;
        $status_code = $request->status_code;
        $md5sig = $request->md5sig;

        $merchant_secret = config('services.payhere.merchant_secret');

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
            // Case 1: Advertisement Payment
            if (preg_match('/AD(\d+)T/', $order_id, $matches)) {
                $ad_id = $matches[1];
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
            // Case 2: Store Order Payment
            else if (preg_match('/ORD(\d+)T/', $order_id, $matches)) {
                $internal_order_id = $matches[1];
                $order = Order::find($internal_order_id);

                if ($order) {
                    if ($status_code == 2) { // 2 = Success
                        $order->update([
                            'status' => 'paid',
                            'payhere_payment_id' => $request->payment_id,
                            'payhere_method' => $request->method,
                        ]);

                        // Decrease stock and track purchase in selling item
                        $item = $order->sellingItem;
                        if ($item) {
                            $item->decreaseStock($order->quantity);
                            $item->total_purchases += $order->quantity;
                            $item->total_revenue += $order->amount;
                            $item->save();
                        }

                        $buyer      = $order->buyer;
                        $buyerName  = $buyer ? "{$buyer->first_name} {$buyer->last_name}" : 'A buyer';
                        $itemTitle  = $item?->title ?? 'an item';
                        AdminNotification::notify(
                            'new_order',
                            'New Order Paid',
                            "{$buyerName} paid LKR " . number_format($order->amount, 2) . " for "{$itemTitle}".",
                            ['order_id' => $order->id, 'order_ref' => $order->order_id_string, 'amount' => $order->amount]
                        );

                        Log::info("PayHere: Order payment successful. Internal ID: $internal_order_id");
                    } else if ($status_code == 0) { // 0 = Pending
                        $order->update(['status' => 'pending']);
                        Log::info("PayHere: Order payment pending. Internal ID: $internal_order_id");
                    } else {
                        $order->update(['status' => 'failed']);
                        Log::warning("PayHere: Order payment failed. Status: $status_code, Internal ID: $internal_order_id");
                    }
                }
            }
            // Case 3: Membership Tier Upgrade Payment
            else if (preg_match('/TIER(.+)TO(SILVER|GOLD)T(\d+)/', $order_id, $matches)) {
                $payment = MembershipPayment::where('order_id_string', $order_id)->first();

                if ($payment) {
                    if ($status_code == 2) {
                        $payment->update([
                            'status'             => 'paid',
                            'payhere_payment_id' => $request->payment_id,
                        ]);

                        $user = $payment->user;
                        if ($user) {
                            $user->update([
                                'membership_tier'     => $payment->to_tier,
                                'tier_upgraded_at'    => now(),
                                'tier_upgrade_source' => 'paid',
                            ]);
                            Log::info("PayHere: Membership upgraded to {$payment->to_tier} for user {$user->id}");
                        }
                    } elseif ($status_code == 0) {
                        Log::info("PayHere: Membership payment pending. Order: $order_id");
                    } else {
                        $payment->update(['status' => 'failed']);
                        Log::warning("PayHere: Membership payment failed. Status: $status_code, Order: $order_id");
                    }
                }
            }
            // Case 4: Video Upload Payment
            else if (preg_match('/VID(\d+)T/', $order_id, $matches)) {
                $payment = VideoUploadPayment::find($matches[1]);

                if ($payment) {
                    if ($status_code == 2) {
                        $token = $payment->generateUploadToken();
                        $payment->update(['payhere_payment_id' => $request->payment_id]);

                        $uploader = $payment->user;
                        $uploaderName = $uploader ? "{$uploader->first_name} {$uploader->last_name}" : 'A user';
                        AdminNotification::notify(
                            'new_video_upload',
                            'Video Upload Payment Received',
                            "{$uploaderName} paid LKR " . number_format($payment->amount, 2) . " for a video upload ({$payment->file_size_mb} MB). Pending validation.",
                            ['payment_id' => $payment->id, 'user_id' => $payment->user_id, 'file_size_mb' => $payment->file_size_mb]
                        );

                        Log::info("PayHere: Video upload payment successful. ID: {$payment->id}, token issued.");
                    } elseif ($status_code == 0) {
                        Log::info("PayHere: Video upload payment pending. ID: {$payment->id}");
                    } else {
                        $payment->update(['status' => 'failed']);
                        Log::warning("PayHere: Video upload payment failed. Status: $status_code, ID: {$payment->id}");
                    }
                }
            }
        } else {
            Log::error("PayHere: Invalid signature received for Order: $order_id");
        }

        return response('OK', 200);
    }
}
