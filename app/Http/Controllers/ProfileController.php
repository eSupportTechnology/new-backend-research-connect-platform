<?php

namespace App\Http\Controllers;

use App\Models\RegisterUsers\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Helper: safely get S3 URL or null
     */
    private function getS3Url(?string $path): ?string
    {
        if ($path === null) return null;
        $cleaned = trim($path);
        if (strlen($cleaned) === 0) return null;

        try {
            return Storage::disk('s3')->url($cleaned);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Helper: safely delete from S3
     */
    private function deleteFromS3(?string $path): void
    {
        if ($path === null) return;
        $cleaned = trim($path);
        if (strlen($cleaned) === 0) return;

        try {
            if (Storage::disk('s3')->exists($cleaned)) {
                Storage::disk('s3')->delete($cleaned);
            }
        } catch (\Exception $e) {
            // Log but don't throw — old file cleanup is non-critical
            \Log::warning('S3 delete failed', ['path' => $cleaned, 'error' => $e->getMessage()]);
        }
    }

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

        $profileData                    = $profile->toArray();
        $profileData['profile_image_url'] = $this->getS3Url($profile->profile_image);
        $profileData['cover_image_url']   = $this->getS3Url($profile->cover_image);

        $userData                   = $user->toArray();
        $userData['membership_tier']= $user->membership_tier ?? 'bronze';
        $userData['tier_badge']     = $user->tier_badge;

        return response()->json([
            'success' => true,
            'data'    => [
                'user'        => $userData,
                'profile'     => $profileData,
                'experiences' => $profile->experiences,
                'educations'  => $profile->educations,
            ]
        ]);
    }
    public function getPublicProfile(string $userId)
    {
        try {
            // Find the user by UUID
            $user = User::findOrFail($userId);

            $profile = $user->profile()->with(['experiences', 'educations'])->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found for this user'
                ], 404);
            }

            $profileData = $profile->toArray();
            $profileData['profile_image_url'] = $this->getS3Url($profile->profile_image);
            $profileData['cover_image_url'] = $this->getS3Url($profile->cover_image);

            // Attach experiences/educations directly into profileData
            $profileData['experiences'] = $profile->experiences;
            $profileData['educations'] = $profile->educations;

            // Get user's innovations
            $innovations = \App\Models\Innovation\Innovation::where('user_id', $user->id)
                ->withCount('innovationViews')
                ->latest()
                ->get()
                ->map(function ($innovation) {
                    // Format URLs for frontend
                    $innovation->video_url = $this->getS3Url($innovation->video_url);
                    $innovation->thumbnail_url = $this->getS3Url($innovation->thumbnail);
                    return $innovation;
                });

            // Get user's research papers
            $researches = \App\Models\Research\Research::where('user_id', $user->id)
                ->withCount('researchViews')
                ->latest()
                ->get()
                ->map(function ($research) {
                    // Format URLs for frontend
                    $research->document_url = $this->getS3Url($research->document_url);
                    $research->thumbnail_url = $this->getS3Url($research->thumbnail);
                    return $research;
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'profile' => $profileData,
                    'experiences' => $profile->experiences,
                    'educations' => $profile->educations,
                    'innovations' => $innovations,
                    'researches' => $researches,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load profile: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Update profile basic data
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'sometimes|string|max:255',
            'email'     => 'sometimes|nullable|email|max:255',
            'telephone' => 'sometimes|nullable|string|max:20',
            'dob'       => 'sometimes|nullable|date',
            'bio'       => 'sometimes|nullable|string|max:2000',
            'skills'    => 'sometimes|array',
            'skills.*'  => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
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
            'name', 'email', 'telephone', 'dob', 'bio', 'skills',
        ]));

        $fresh                        = $profile->fresh()->toArray();
        $fresh['profile_image_url']   = $this->getS3Url($profile->fresh()->profile_image);
        $fresh['cover_image_url']     = $this->getS3Url($profile->fresh()->cover_image);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data'    => $fresh
        ]);
    }

    /**
     * Update profile image — uploads to S3
     */
    public function updateProfileImage(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $profile = Auth::user()->profile;

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        $this->deleteFromS3($profile->profile_image);

        // ✅ Set public visibility
        $path = Storage::disk('s3')->putFile('profiles', $request->file('profile_image'), 'public');

        if (!$path) {
            return response()->json(['success' => false, 'message' => 'Failed to upload image to S3'], 500);
        }

        $profile->update(['profile_image' => $path]);

        return response()->json([
            'success'           => true,
            'message'           => 'Profile image updated successfully',
            'profile_image_url' => $this->getS3Url($path),
        ]);
    }

    public function updateCoverImage(Request $request)
    {
        $request->validate([
            'cover_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $profile = Auth::user()->profile;

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        $this->deleteFromS3($profile->cover_image);

        // ✅ Set public visibility
        $path = Storage::disk('s3')->putFile('covers', $request->file('cover_image'), 'public');

        if (!$path) {
            return response()->json(['success' => false, 'message' => 'Failed to upload cover image to S3'], 500);
        }

        $profile->update(['cover_image' => $path]);

        return response()->json([
            'success'         => true,
            'message'         => 'Cover image updated successfully',
            'cover_image_url' => $this->getS3Url($path),
        ]);
    }

    /**
     * Profile statistics
     */
    public function getStatistics()
    {
        $profile = Auth::user()->profile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'follower_count'   => $profile->follower_count,
                'following_count'  => $profile->following_count,
                'innovation_count' => $profile->innovation_count,
                'research_count'   => $profile->research_count,
                'system_level'     => $profile->system_level,
            ]
        ]);
    }

    /**
     * Check if user is eligible to sell (has bank and address)
     */
    public function getSellingEligibility()
    {
        $userId = Auth::id();
        
        $hasBank = \App\Models\Profile\BankDetail::where('user_id', $userId)->exists();
        $hasAddress = \App\Models\Profile\ShippingAddress::where('user_id', $userId)->exists();
        
        return response()->json([
            'success' => true,
            'data' => [
                'has_bank_details' => $hasBank,
                'has_shipping_address' => $hasAddress,
                'is_eligible' => $hasBank && $hasAddress
            ]
        ]);
    }
}
