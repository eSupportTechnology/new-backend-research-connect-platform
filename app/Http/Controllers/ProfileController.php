<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get logged-in user profile
     */
    public function index()
    {
        $user = Auth::user();

        $profile = $user->profile()->with(['experiences', 'educations'])->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'profile' => $profile,
                'experiences' => $profile->experiences,
                'educations' => $profile->educations,
            ]
        ]);
    }

    /**
     * Update profile basic data
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:2000',
            'skills' => 'sometimes|array',
            'skills.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = Auth::user()->profile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $profile->update($request->only([
            'name',
            'bio',
            'skills'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $profile->fresh()
        ]);
    }

    /**
     * Update profile image
     */
    public function updateProfileImage(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $profile = Auth::user()->profile;

        // Delete old profile image if exists
        if ($profile->profile_image && Storage::disk('public')->exists($profile->profile_image)) {
            Storage::disk('public')->delete($profile->profile_image);
        }

        $path = $request->file('profile_image')->store('profiles', 'public');
        $profile->update(['profile_image' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Profile image updated successfully',
            'profile_image_url' => asset('storage/' . $path)
        ]);
    }

    /**
     * Update cover image
     */
    public function updateCoverImage(Request $request)
    {
        $request->validate([
            'cover_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $profile = Auth::user()->profile;

        // Delete old cover image if exists
        if ($profile->cover_image && Storage::disk('public')->exists($profile->cover_image)) {
            Storage::disk('public')->delete($profile->cover_image);
        }

        $path = $request->file('cover_image')->store('covers', 'public');
        $profile->update(['cover_image' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Cover image updated successfully',
            'cover_image_url' => asset('storage/' . $path)
        ]);
    }

    /**
     * Profile statistics
     */
    public function getStatistics()
    {
        $profile = Auth::user()->profile;

        return response()->json([
            'success' => true,
            'data' => [
                'follower_count' => $profile->follower_count,
                'following_count' => $profile->following_count,
                'innovation_count' => $profile->innovation_count,
                'research_count' => $profile->research_count,
                'system_level' => $profile->system_level,
            ]
        ]);
    }
}
