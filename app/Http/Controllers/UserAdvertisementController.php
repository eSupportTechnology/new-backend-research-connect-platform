<?php

namespace App\Http\Controllers;

use App\Models\Advertisement\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class UserAdvertisementController extends Controller
{
    /**
     * Get advertisements requested by the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $ads = Advertisement::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'type' => $ad->type,
                    'title' => $ad->title,
                    'subtitle' => $ad->subtitle,
                    'status' => $ad->status,
                    'payment_status' => $ad->payment_status,
                    'price' => $ad->price,
                    'image' => $ad->image_url,
                    'created_at' => $ad->created_at->format('Y-m-d H:i'),
                    'rejection_reason' => $ad->rejection_reason,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $ads,
        ]);
    }

    /**
     * Submit a new advertisement request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:side,carousel,banner',
            'position' => 'nullable|in:left,right',
            'badge' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'required|string',
            'cta_text' => 'required|string|max:255',
            'cta_link' => 'nullable|url',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'color' => 'nullable|string|max:7',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'display_start_time' => 'nullable|date_format:H:i',
            'display_end_time' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'pending';
        $data['payment_status'] = 'unpaid';
        $data['is_active'] = false; // Not active until paid and approved

        // Calculate price (Placeholders - user can change later)
        $prices = [
            'carousel' => 5000.00,
            'banner' => 3000.00,
            'side' => 2000.00,
        ];
        $data['price'] = $prices[$data['type']];

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('advertisements', 's3');
            $data['image_path'] = $path;
        }

        // Handle dates
        if (isset($data['start_date'])) {
            $data['start_date'] = Carbon::parse($data['start_date'], 'Asia/Colombo')->startOfDay()->utc();
        }
        if (isset($data['end_date'])) {
            $data['end_date'] = Carbon::parse($data['end_date'], 'Asia/Colombo')->endOfDay()->utc();
        }

        $ad = Advertisement::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement request submitted successfully',
            'data' => [
                'id' => $ad->id,
                'price' => $ad->price,
                'order_id' => 'AD-' . $ad->id . '-' . time(),
            ],
        ], 201);
    }

    /**
     * Get payment parameters for PayHere
     */
    public function getPaymentParams(Request $request, $id)
    {
        $ad = Advertisement::where('user_id', $request->user()->id)->findOrFail($id);

        if ($ad->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Advertisement already paid',
            ], 400);
        }

        $merchant_id = env('PAYHERE_MERCHANT_ID');
        $merchant_secret = env('PAYHERE_MERCHANT_SECRET');
        $order_id = 'AD-' . $ad->id . '-' . time();
        $amount = $ad->price;
        $currency = 'LKR';

        // Generate Hash
        $hash = strtoupper(
            md5(
                $merchant_id . 
                $order_id . 
                number_format($amount, 2, '.', '') . 
                $currency . 
                strtoupper(md5($merchant_secret))
            )
        );

        return response()->json([
            'success' => true,
            'data' => [
                'merchant_id' => $merchant_id,
                'order_id' => $order_id,
                'items' => "Advertisement - " . ucfirst($ad->type),
                'amount' => $amount,
                'currency' => $currency,
                'hash' => $hash,
                'first_name' => $request->user()->first_name ?? $request->user()->name,
                'last_name' => $request->user()->last_name ?? '',
                'email' => $request->user()->email,
                'phone' => $request->user()->phone ?? '',
                'address' => '',
                'city' => '',
                'country' => 'Sri Lanka',
                'delivery_address' => '',
                'delivery_city' => '',
                'delivery_country' => 'Sri Lanka',
                'notify_url' => route('payhere.notify'),
                'return_url' => url('/profile/ads?status=success'),
                'cancel_url' => url('/profile/ads?status=cancel'),
            ],
        ]);
    }
}
