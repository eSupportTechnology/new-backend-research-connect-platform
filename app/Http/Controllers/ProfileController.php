<?php

namespace App\Http\Controllers;
use App\Models\Portfolio\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get user profile with related data
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
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
                'user_type' => $user->user_type,
                'profile' => $profile,
                'experiences' => $profile->experiences,
                'educations' => $profile->educations,
            ]
        ]);
    }

    /**
     * Update user profile
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'title' => 'sometimes|string|max:255',
            'headline' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:2000',
            'phone' => 'sometimes|string|max:20',
            'skills' => 'sometimes|array',
            'skills.*' => 'string',
            'website' => 'sometimes|url|nullable',
            'location' => 'sometimes|string|max:255|nullable',
            'github_url' => 'sometimes|url|nullable',
            'linkedin_url' => 'sometimes|url|nullable',
            'twitter_url' => 'sometimes|url|nullable',
            'facebook_url' => 'sometimes|url|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Update user basic info
        if ($request->has('first_name') || $request->has('last_name')) {
            $user->update([
                'first_name' => $request->first_name ?? $user->first_name,
                'last_name' => $request->last_name ?? $user->last_name,
            ]);
        }

        // Update profile
        $profile = $user->profile;

        $profileData = $request->only([
            'name', 'title', 'headline', 'bio', 'phone',
            'website', 'location', 'github_url', 'linkedin_url',
            'twitter_url', 'facebook_url'
        ]);

        if ($request->has('skills')) {
            $profileData['skills'] = $request->skills;
        }

        // If name is not provided but first_name/last_name are, update name
        if (!$request->has('name') && ($request->has('first_name') || $request->has('last_name'))) {
            $profileData['name'] = $user->first_name . ' ' . $user->last_name;
        }

        $profile->update($profileData);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user,
                'profile' => $profile->fresh()
            ]
        ]);
    }

    /**
     * Update profile image
     */
    public function updateProfileImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $profile = $user->profile;

        // Delete old image if exists
        if ($profile->profile_image && Storage::disk('public')->exists($profile->profile_image)) {
            Storage::disk('public')->delete($profile->profile_image);
        }

        // Store new image
        $imagePath = $request->file('profile_image')->store('profiles', 'public');

        $profile->update(['profile_image' => $imagePath]);

        return response()->json([
            'success' => true,
            'message' => 'Profile image updated successfully',
            'data' => [
                'profile_image_url' => asset('storage/' . $imagePath)
            ]
        ]);
    }

    /**
     * Update cover image
     */
    public function updateCoverImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cover_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $profile = $user->profile;

        // Delete old image if exists
        if ($profile->cover_image && Storage::disk('public')->exists($profile->cover_image)) {
            Storage::disk('public')->delete($profile->cover_image);
        }

        // Store new image
        $imagePath = $request->file('cover_image')->store('covers', 'public');

        $profile->update(['cover_image' => $imagePath]);

        return response()->json([
            'success' => true,
            'message' => 'Cover image updated successfully',
            'data' => [
                'cover_image_url' => asset('storage/' . $imagePath)
            ]
        ]);
    }

    /**
     * Get profile statistics
     */
    public function getStatistics()
    {
        $user = Auth::user();
        $profile = $user->profile;

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
