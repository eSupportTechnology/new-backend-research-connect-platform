<?php

namespace App\Http\Controllers;

use App\Models\Innovation\Follower;
use App\Models\RegisterUsers\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FollowerController extends Controller
{
    /**
     * Follow a user
     */
    public function follow(Request $request, $userId)
    {
        try {
            $currentUser = auth()->user();

            if ($currentUser->id === $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot follow yourself',
                ], 400);
            }

            $userToFollow = User::find($userId);
            if (!$userToFollow) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $existingFollow = Follower::where('follower_id', $currentUser->id)
                ->where('following_id', $userId)
                ->first();

            if ($existingFollow) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already following this user',
                ], 409);
            }

            Follower::create([
                'follower_id' => $currentUser->id,
                'following_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully followed user',
                'is_following' => true,
                'follower_count' => $userToFollow->followers()->count(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to follow user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unfollow a user
     */
    public function unfollow($userId)
    {
        try {
            $currentUser = auth()->user();

            $follow = Follower::where('follower_id', $currentUser->id)
                ->where('following_id', $userId)
                ->first();

            if (!$follow) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not following this user',
                ], 404);
            }

            $follow->delete();

            $userToUnfollow = User::find($userId);

            return response()->json([
                'success' => true,
                'message' => 'Successfully unfollowed user',
                'is_following' => false,
                'follower_count' => $userToUnfollow ? $userToUnfollow->followers()->count() : 0,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unfollow user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle follow/unfollow
     */
    public function toggleFollow($userId)
    {
        try {
            $currentUser = auth()->user();

            if ($currentUser->id === $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot follow yourself',
                ], 400);
            }

            $targetUser = User::find($userId);
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $follow = Follower::where('follower_id', $currentUser->id)
                ->where('following_id', $userId)
                ->first();

            if ($follow) {
                $follow->delete();
                $isFollowing = false;
                $message = 'Successfully unfollowed user';
            } else {
                Follower::create([
                    'follower_id' => $currentUser->id,
                    'following_id' => $userId,
                ]);
                $isFollowing = true;
                $message = 'Successfully followed user';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'is_following' => $isFollowing,
                'follower_count' => $targetUser->followers()->count(),
                'following_count' => $targetUser->following()->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle follow status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's followers
     */
    public function followers(Request $request, $userId)
    {
        try {
            $perPage = $request->get('per_page', 20);

            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $followers = $user->followers()
                ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
                ->paginate($perPage);

            $followers->getCollection()->transform(function ($follower) {
                $follower->load('profile');
                $follower->display_name = trim($follower->first_name . ' ' . $follower->last_name);
                $follower->profile_image_url = $follower->profile?->profile_image_url ?? null;
                $follower->is_following = auth()->check()
                    ? auth()->user()->isFollowing($follower->id)
                    : false;
                $follower->is_followed_by_me = auth()->check()
                    ? $follower->isFollowing(auth()->id())
                    : false;
                return $follower;
            });

            return response()->json([
                'success' => true,
                'data' => $followers,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch followers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get users that a user is following
     */
    public function following(Request $request, $userId)
    {
        try {
            $perPage = $request->get('per_page', 20);

            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $following = $user->following()
                ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
                ->paginate($perPage);

            $following->getCollection()->transform(function ($followedUser) {
                $followedUser->load('profile');
                $followedUser->display_name = trim($followedUser->first_name . ' ' . $followedUser->last_name);
                $followedUser->profile_image_url = $followedUser->profile?->profile_image_url ?? null;
                $followedUser->is_following = auth()->check()
                    ? auth()->user()->isFollowing($followedUser->id)
                    : false;
                $followedUser->is_followed_by_me = true;
                return $followedUser;
            });

            return response()->json([
                'success' => true,
                'data' => $following,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch following list',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if current user is following a specific user
     */
    public function checkFollowStatus($userId)
    {
        try {
            if (!auth()->check()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'is_following' => false,
                    ],
                ], 200);
            }

            $isFollowing = auth()->user()->isFollowing($userId);

            $targetUser = User::find($userId);
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'is_following' => $isFollowing,
                    'follower_count' => $targetUser->followers()->count(),
                    'following_count' => $targetUser->following()->count(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check follow status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get follow statistics for a user
     */
    public function stats($userId)
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $followerCount = $user->followers()->count();
            $followingCount = $user->following()->count();
            $isFollowing = auth()->check() ? auth()->user()->isFollowing($userId) : false;

            return response()->json([
                'success' => true,
                'data' => [
                    'follower_count' => $followerCount,
                    'following_count' => $followingCount,
                    'is_following' => $isFollowing,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get suggested users to follow
     */
    public function suggestions(Request $request)
    {
        try {
            $currentUser = auth()->user();
            $limit = $request->get('limit', 10);

            $suggestedUsers = User::whereNotIn('id', function ($query) use ($currentUser) {
                $query->select('following_id')
                    ->from('followers')
                    ->where('follower_id', $currentUser->id);
            })
                ->where('id', '!=', $currentUser->id)
                ->select('id', 'first_name', 'last_name', 'email')
                ->inRandomOrder()
                ->limit($limit)
                ->get();

            $suggestedUsers->each(function ($user) {
                $user->load('profile');
                $user->display_name = trim($user->first_name . ' ' . $user->last_name);
                $user->profile_image_url = $user->profile?->profile_image_url ?? null;
                $user->follower_count = $user->followers()->count();
                $user->following_count = $user->following()->count();
            });

            return response()->json([
                'success' => true,
                'data' => $suggestedUsers,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch suggestions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Remove a follower
     */
    public function removeFollower($followerId)
    {
        try {
            $currentUser = auth()->user();

            $follow = Follower::where('follower_id', $followerId)
                ->where('following_id', $currentUser->id)
                ->first();

            if (!$follow) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user is not following you',
                ], 404);
            }

            $follow->delete();

            return response()->json([
                'success' => true,
                'message' => 'Follower removed successfully',
                'data' => [
                    'follower_count' => $currentUser->followers()->count(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove follower',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
