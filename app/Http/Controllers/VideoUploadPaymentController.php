<?php

namespace App\Http\Controllers;

use App\Models\VideoUploadFee;
use App\Models\VideoUploadPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VideoUploadPaymentController extends Controller
{
    /** Public — return current fee config */
    public function config()
    {
        $fee = VideoUploadFee::config();
        return response()->json(['success' => true, 'data' => $fee]);
    }

    /** Authenticated — calculate cost for a given file size */
    public function calculate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_size_bytes' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $mb  = (int) ceil($request->file_size_bytes / (1024 * 1024));
        $fee = VideoUploadFee::config();

        return response()->json(['success' => true, 'data' => $fee->calculate($mb)]);
    }

    /** Authenticated — initiate a payment record and return PayHere params */
    public function initiate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_size_bytes' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $mb   = (int) ceil($request->file_size_bytes / (1024 * 1024));
        $fee  = VideoUploadFee::config();
        $calc = $fee->calculate($mb);

        if (!$calc['requires_payment']) {
            return response()->json(['success' => false, 'message' => 'No payment required for this file size.'], 422);
        }

        $payment = VideoUploadPayment::create([
            'user_id'        => $user->id,
            'order_id'       => 'TMP-' . uniqid(),
            'file_size_bytes' => $request->file_size_bytes,
            'file_size_mb'   => $mb,
            'excess_mb'      => $calc['excess_mb'],
            'amount'         => $calc['amount'],
        ]);

        $orderId = VideoUploadPayment::generateOrderId($payment->id);
        $payment->update(['order_id' => $orderId]);

        $merchantId     = config('services.payhere.merchant_id');
        $merchantSecret = config('services.payhere.merchant_secret');
        $amountFormatted = number_format($calc['amount'], 2, '.', '');
        $hash = strtoupper(md5(
            $merchantId .
            $orderId .
            $amountFormatted .
            'LKR' .
            strtoupper(md5($merchantSecret))
        ));

        return response()->json([
            'success' => true,
            'data' => [
                'payment_id'   => $payment->id,
                'amount'       => $calc['amount'],
                'excess_mb'    => $calc['excess_mb'],
                'order_id'     => $orderId,
                'payhere' => [
                    'merchant_id'   => $merchantId,
                    'return_url'    => env('FRONTEND_URL') . '/upload-innovation?payment=success',
                    'cancel_url'    => env('FRONTEND_URL') . '/upload-innovation?payment=cancelled',
                    'notify_url'    => env('APP_URL') . '/api/payhere/notify',
                    'order_id'      => $orderId,
                    'items'         => 'Video Upload Fee (' . $calc['excess_mb'] . ' MB excess)',
                    'amount'        => $amountFormatted,
                    'currency'      => 'LKR',
                    'first_name'    => $user->first_name,
                    'last_name'     => $user->last_name,
                    'email'         => $user->email,
                    'phone'         => '0000000000',
                    'address'       => 'N/A',
                    'city'          => 'Colombo',
                    'country'       => 'Sri Lanka',
                    'hash'          => $hash,
                ],
            ],
        ]);
    }

    /** Authenticated — check payment status by payment ID */
    public function status($id)
    {
        $payment = VideoUploadPayment::where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'status'       => $payment->status,
                'upload_token' => $payment->status === 'paid' ? $payment->upload_token : null,
            ],
        ]);
    }

    /** Admin — list all video upload payments */
    public function adminIndex(Request $request)
    {
        $payments = VideoUploadPayment::with(['user:id,first_name,last_name,email'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $payments]);
    }

    /** Admin — get current fee config */
    public function getFee()
    {
        return response()->json(['success' => true, 'data' => VideoUploadFee::config()]);
    }

    /** Admin — update fee config */
    public function updateFee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'free_limit_mb'   => 'sometimes|integer|min:100|max:10240',
            'price_per_100mb' => 'sometimes|integer|min:1|max:10000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $fee = VideoUploadFee::config();
        $fee->update($request->only(['free_limit_mb', 'price_per_100mb']));

        return response()->json(['success' => true, 'data' => $fee]);
    }
}