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
                'max_impressions' => $ad->max_impressions,
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
                    'max_impressions' => $ad->max_impressions,
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
        $now = Carbon::now();

        $ads = Advertisement::active()
            ->ofType('carousel')
            ->orderBy('order', 'asc')
            ->get()
            ->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'name' => $ad->title,
                    'desc' => $ad->description,
                    'icon' => $ad->image_url,
                    'color' => $ad->color ?? '#e53e3e',
                    'btnText' => $ad->cta_text,
                    'link' => $ad->cta_link,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $ads,
        ]);
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
            'max_impressions' => 'nullable|integer|min:0',
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

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('advertisements', 's3');
            $data['image_path'] = $path;
        }

        // Convert dates correctly with timezone to avoid returning the previous day
        if (isset($data['start_date'])) {
            $data['start_date'] = Carbon::parse($data['start_date'], 'Asia/Colombo')->startOfDay()->utc();
        }
        if (isset($data['end_date'])) {
            $data['end_date'] = Carbon::parse($data['end_date'], 'Asia/Colombo')->endOfDay()->utc();
        }

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
            'max_impressions' => 'nullable|integer|min:0',
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
        if (isset($data['start_date'])) {
            $data['start_date'] = Carbon::parse($data['start_date'], 'Asia/Colombo')->startOfDay()->utc();
        }
        if (isset($data['end_date'])) {
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
}
