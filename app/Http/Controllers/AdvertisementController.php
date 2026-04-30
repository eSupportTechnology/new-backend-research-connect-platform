<?php

namespace App\Http\Controllers;
use App\Models\Advertisement\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdvertisementController extends Controller
{
    /**
     * Get active advertisements by type
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'all');
        $position = $request->query('position');

        $query = Advertisement::active()->orderBy('order', 'asc');

        if ($type !== 'all') {
            $query->ofType($type);
        }

        if ($position) {
            $query->position($position);
        }

        $ads = $query->get()->map(function ($ad) {
            return [
                'id' => $ad->id,
                'type' => $ad->type,
                'position' => $ad->position,
                'badge' => $ad->badge,
                'title' => $ad->title,
                'subtitle' => $ad->subtitle,
                'desc' => $ad->description,
                'cta' => $ad->cta_text,
                'link' => $ad->cta_link,
                'image' => $ad->image_url,
                'color' => $ad->color,
                'start_date' => $ad->start_date ? $ad->start_date->setTimezone('Asia/Colombo')->format('Y-m-d') : null,
                'end_date' => $ad->end_date ? $ad->end_date->setTimezone('Asia/Colombo')->format('Y-m-d') : null,
                'display_start_time' => $ad->display_start_time,
                'display_end_time' => $ad->display_end_time,
                'order' => $ad->order,
                'is_active' => $ad->is_active,

                'current_impressions' => $ad->current_impressions,
                'clicks' => $ad->clicks,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $ads,
        ]);
    }

    /**
     * Get side ads (for SideAdContainer) - with time slot filtering
     */
    /**
     * Get all advertisements for admin (including inactive and future-dated)
     */
    public function adminIndex()
    {
        $ads = Advertisement::orderBy('created_at', 'desc')
            ->orderBy('order', 'asc')
            ->get()
            ->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'type' => $ad->type,
                    'position' => $ad->position,
                    'badge' => $ad->badge,
                    'title' => $ad->title,
                    'name' => $ad->title, // For compatibility
                    'subtitle' => $ad->subtitle,
                    'description' => $ad->description,
                    'desc' => $ad->description, // For compatibility
                    'cta_text' => $ad->cta_text,
                    'cta' => $ad->cta_text, // For compatibility
                    'cta_link' => $ad->cta_link,
                    'link' => $ad->cta_link, // For compatibility
                    'image_path' => $ad->image_path,
                    'image' => $ad->image_url,
                    'icon' => $ad->image_url, // For compatibility
                    'color' => $ad->color,
                    'order' => $ad->order,
                    'is_active' => $ad->is_active,
                    'start_date' => $ad->start_date ? $ad->start_date->setTimezone('Asia/Colombo')->format('Y-m-d') : null,
                    'end_date' => $ad->end_date ? $ad->end_date->setTimezone('Asia/Colombo')->format('Y-m-d') : null,
                    'display_start_time' => $ad->display_start_time ? $ad->display_start_time->format('H:i') : null,
                    'display_end_time' => $ad->display_end_time ? $ad->display_end_time->format('H:i') : null,
    
                    'current_impressions' => $ad->current_impressions,
                    'clicks' => $ad->clicks,
                    'created_at' => $ad->created_at,
                    'updated_at' => $ad->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $ads,
        ]);
    }
    public function getSideAds(Request $request)
    {
        $position = $request->query('position', 'left');
        $now = Carbon::now('Asia/Colombo');

        $ads = Advertisement::active()
            ->ofType('side')
            ->position($position)
            ->orderBy('order', 'asc')
            ->get()
            ->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'side' => $ad->position,
                    'badge' => $ad->badge,
                    'title' => $ad->title,
                    'subtitle' => $ad->subtitle,
                    'desc' => $ad->description,
                    'cta' => $ad->cta_text,
                    'link' => $ad->cta_link,
                    'image' => $ad->image_url,
                    'display_start_time' => $ad->display_start_time,
                    'display_end_time' => $ad->display_end_time,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $ads,
        ]);
    }

    /**
     * Get carousel/banner ads (for Ads1 component)
     */
    public function getCarouselAds()
    {
        $ads = Advertisement::active()
            ->ofType('carousel')
            ->orderBy('order', 'asc')
            ->get()
            ->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'name' => $ad->title,
                    'title' => $ad->title,
                    'desc' => $ad->description,
                    'description' => $ad->description,
                    'icon' => $ad->image_url,
                    'image' => $ad->image_url,
                    'color' => $ad->color ?? '#e53e3e',
                    'btnText' => $ad->cta_text,
                    'cta_text' => $ad->cta_text,
                    'link' => $ad->cta_link,
                    'cta_link' => $ad->cta_link,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $ads,
        ]);
    }

    /**
     * Get banner ads (for BannerAd component — placed between home page sections)
     */
    public function getBannerAds()
    {
        $ads = Advertisement::active()
            ->ofType('banner')
            ->orderBy('order', 'asc')
            ->get()
            ->map(function ($ad) {
                return [
                    'id'       => $ad->id,
                    'badge'    => $ad->badge,
                    'title'    => $ad->title,
                    'subtitle' => $ad->subtitle,
                    'desc'     => $ad->description,
                    'image'    => $ad->image_url,
                    'color'    => $ad->color ?? '#e53e3e',
                    'cta_text' => $ad->cta_text,
                    'link'     => $ad->cta_link,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $ads,
        ]);
    }

    /**
     * Get popup advertisements
     */
    public function getPopupAds()
    {
        $ads = Advertisement::active()
            ->ofType('popup')
            ->orderBy('order', 'asc')
            ->get()
            ->map(function ($ad) {
                return [
                    'id'       => $ad->id,
                    'badge'    => $ad->badge,
                    'title'    => $ad->title,
                    'subtitle' => $ad->subtitle,
                    'desc'     => $ad->description,
                    'image'    => $ad->image_url,
                    'color'    => $ad->color ?? '#e53e3e',
                    'cta_text' => $ad->cta_text,
                    'link'     => $ad->cta_link,
                ];
            });

        return response()->json(['success' => true, 'data' => $ads]);
    }

    /**
     * Record an impression
     */
    public function recordImpression(Request $request, $id)
    {
        $ad = Advertisement::find($id);

        if (!$ad) {
            return response()->json([
                'success' => false,
                'message' => 'Advertisement not found',
            ], 404);
        }

        $ad->recordImpression();

        return response()->json([
            'success' => true,
            'message' => 'Impression recorded',
        ]);
    }

    /**
     * Record a click
     */
    public function recordClick(Request $request, $id)
    {
        $ad = Advertisement::find($id);

        if (!$ad) {
            return response()->json([
                'success' => false,
                'message' => 'Advertisement not found',
            ], 404);
        }

        $ad->recordClick();

        return response()->json([
            'success' => true,
            'message' => 'Click recorded',
            'link' => $ad->cta_link,
        ]);
    }

    /**
     * Store a new advertisement (Admin)
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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
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

        // Normalize empty strings to null for date/time columns so MySQL doesn't store '00:00:00'
        foreach (['start_date', 'end_date', 'display_start_time', 'display_end_time'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('advertisements', 's3');
            $data['image_path'] = $path;
        }

        // Convert dates correctly with timezone to avoid returning the previous day
        if (!empty($data['start_date'])) {
            $data['start_date'] = Carbon::parse($data['start_date'], 'Asia/Colombo')->startOfDay()->utc();
        }
        if (!empty($data['end_date'])) {
            $data['end_date'] = Carbon::parse($data['end_date'], 'Asia/Colombo')->endOfDay()->utc();
        }

        // Admin-created ads are auto-approved — no payment needed
        $data['status']         = 'active';
        $data['payment_status'] = 'paid';
        $data['is_active']      = true;

        $ad = Advertisement::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement created successfully',
            'data' => $ad,
        ], 201);
    }

    /**
     * Update an advertisement (Admin) - FIXED TYPO
     */
    public function update(Request $request, $id)
    {
        $ad = Advertisement::find($id); // FIXED: was "Advertisemen"

        if (!$ad) {
            return response()->json([
                'success' => false,
                'message' => 'Advertisement not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:side,carousel,banner',
            'position' => 'nullable|in:left,right',
            'badge' => 'nullable|string|max:255',
            'title' => 'sometimes|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'sometimes|string',
            'cta_text' => 'sometimes|string|max:255',
            'cta_link' => 'nullable|url',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
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

        // Normalize empty strings to null for date/time columns
        foreach (['start_date', 'end_date', 'display_start_time', 'display_end_time'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image from S3
            if ($ad->image_path) {
                Storage::disk('s3')->delete($ad->image_path);
            }
            $path = $request->file('image')->store('advertisements', 's3');
            $data['image_path'] = $path;
        }

        // Convert dates correctly with timezone to avoid returning the previous day
        if (!empty($data['start_date'])) {
            $data['start_date'] = Carbon::parse($data['start_date'], 'Asia/Colombo')->startOfDay()->utc();
        }
        if (!empty($data['end_date'])) {
            $data['end_date'] = Carbon::parse($data['end_date'], 'Asia/Colombo')->endOfDay()->utc();
        }

        $ad->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement updated successfully',
            'data' => $ad,
        ]);
    }

    /**
     * Delete an advertisement (Admin)
     */
    public function destroy($id)
    {
        $ad = Advertisement::find($id);

        if (!$ad) {
            return response()->json([
                'success' => false,
                'message' => 'Advertisement not found',
            ], 404);
        }

        // Delete image from S3
        if ($ad->image_path) {
            Storage::disk('s3')->delete($ad->image_path);
        }

        $ad->delete();

        return response()->json([
            'success' => true,
            'message' => 'Advertisement deleted successfully',
        ]);
    }

    /**
     * Get advertisement analytics (Admin)
     */
    public function analytics()
    {
        $ads = Advertisement::all();
        $analytics = $ads->map(function ($ad) {
            $ctr = $ad->current_impressions > 0
                ? ($ad->clicks / $ad->current_impressions) * 100
                : 0;

            return [
                'id' => $ad->id,
                'title' => $ad->title,
                'type' => $ad->type,
                'impressions' => $ad->current_impressions,
                'clicks' => $ad->clicks,
                'ctr' => round($ctr, 2),
                'is_active' => $ad->is_active,
                'start_date' => $ad->start_date ? $ad->start_date->setTimezone('Asia/Colombo')->format('Y-m-d') : null,
                'end_date' => $ad->end_date ? $ad->end_date->setTimezone('Asia/Colombo')->format('Y-m-d') : null,
                'display_start_time' => $ad->display_start_time,
                'display_end_time' => $ad->display_end_time,
                'deleted_at' => $ad->deleted_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Approve an advertisement — ONLY allowed when payment is confirmed.
     */
    public function approve(Request $request, $id)
    {
        $ad = Advertisement::find($id);

        if (!$ad) {
            return response()->json(['success' => false, 'message' => 'Advertisement not found.'], 404);
        }

        if ($ad->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot approve: payment has not been confirmed for this advertisement. The user must complete payment first.',
                'payment_status' => $ad->payment_status,
            ], 422);
        }

        $ad->update(['status' => 'active', 'is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement approved and is now live.',
            'data'    => $ad,
        ]);
    }

    /**
     * Admin emergency override — activates an unpaid ad with explicit acknowledgment.
     * Strictly for exceptional cases only. Logged for audit purposes.
     */
    public function adminOverride(Request $request, $id)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'A reason is required for admin override.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $ad = Advertisement::find($id);

        if (!$ad) {
            return response()->json(['success' => false, 'message' => 'Advertisement not found.'], 404);
        }

        $ad->update([
            'status'         => 'active',
            'is_active'      => true,
            'payment_status' => 'paid',
        ]);

        \Illuminate\Support\Facades\Log::warning('Admin override: Advertisement activated without payment', [
            'ad_id'    => $ad->id,
            'admin_id' => auth()->id(),
            'reason'   => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin override applied. Advertisement is now live. This action has been logged.',
            'data'    => $ad,
        ]);
    }

    /**
     * Reject an advertisement request
     */
    public function reject(Request $request, $id)
    {
        $ad = Advertisement::find($id);

        if (!$ad) {
            return response()->json([
                'success' => false,
                'message' => 'Advertisement not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $ad->update([
            'status' => 'rejected',
            'is_active' => false,
            'rejection_reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement rejected successfully',
            'data' => $ad,
        ]);
    }

    /**
     * Perform bulk actions on advertisements (Admin)
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:advertisements,id',
            'action' => 'required|in:delete,activate,deactivate',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $ids = $request->ids;
        $action = $request->action;

        $ads = Advertisement::whereIn('id', $ids)->get();
        $processedCount = 0;

        foreach ($ads as $ad) {
            switch ($action) {
                case 'delete':
                    if ($ad->image_path) {
                        Storage::disk('s3')->delete($ad->image_path);
                    }
                    $ad->delete();
                    break;
                case 'activate':
                    $ad->update(['is_active' => true]);
                    break;
                case 'deactivate':
                    $ad->update(['is_active' => false]);
                    break;
            }
            $processedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully processed {$processedCount} advertisements.",
        ]);
    }
}
