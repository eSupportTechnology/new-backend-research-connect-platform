<?php

namespace App\Http\Controllers;


use App\Models\Innovation\Innovation;
use App\Models\Innovation\InnovationComment;
use App\Models\Innovation\InnovationCommentLike;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class InnovationCommentController extends Controller
{
    /**
     * Get all comments for an innovation
     */
    public function index(Request $request, $innovationId)
    {
        try {
            $perPage = $request->get('per_page', 10);

            $comments = InnovationComment::with(['user:id,first_name,last_name', 'user.profile:id,user_id,profile_image'])
                ->where('innovation_id', $innovationId)
                ->withCount(['likes', 'dislikes'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $comments->getCollection()->transform(function ($comment) {
                return [
                    'id' => $comment->id,
                    'user_id' => $comment->user_id,
                    'text' => $comment->text,
                    'rating' => $comment->rating,
                    'likes' => $comment->likes_count,
                    'dislikes' => $comment->dislikes_count,
                    'date' => $comment->created_at->format('M d, Y'),
                    'author' => $comment->user ? $comment->user->full_name : 'Unknown User',
                    'author_image' => $comment->user && $comment->user->profile ? $comment->user->profile->profile_image_url : null,
                    'user_has_liked' => $comment->user_has_liked,
                    'user_has_disliked' => $comment->user_has_disliked,
                    'created_at' => $comment->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $comments,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new comment
     */
    public function store(Request $request, $innovationId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'text' => 'required|string|max:1000',
                'rating' => 'required|integer|min:1|max:5',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check if innovation exists
            $innovation = Innovation::find($innovationId);
            if (!$innovation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Innovation not found',
                ], 404);
            }

            // Check if user already commented on this innovation
            $existingComment = InnovationComment::where('user_id', auth()->id())
                ->where('innovation_id', $innovationId)
                ->first();

            if ($existingComment) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already commented on this innovation',
                ], 409);
            }

            $comment = InnovationComment::create([
                'user_id' => auth()->id(),
                'innovation_id' => $innovationId,
                'text' => $request->text,
                'rating' => $request->rating,
            ]);

            $comment->load(['user:id,first_name,last_name', 'user.profile:id,user_id,profile_image']);

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => [
                    'id' => $comment->id,
                    'text' => $comment->text,
                    'rating' => $comment->rating,
                    'likes' => 0,
                    'dislikes' => 0,
                    'date' => $comment->created_at->format('M d, Y'),
                    'author' => $comment->user ? $comment->user->full_name : 'Unknown User',
                    'author_image' => $comment->user && $comment->user->profile ? $comment->user->profile->profile_image_url : null,
                    'user_has_liked' => false,
                    'user_has_disliked' => false,
                    'created_at' => $comment->created_at,
                ],
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a comment
     */
    public function update(Request $request, $innovationId, $commentId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'text' => 'sometimes|string|max:1000',
                'rating' => 'sometimes|integer|min:1|max:5',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $comment = InnovationComment::where('id', $commentId)
                ->where('innovation_id', $innovationId)
                ->where('user_id', auth()->id())
                ->first();

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found or you do not have permission to update it',
                ], 404);
            }

            $comment->update($request->only(['text', 'rating']));
            $comment->load(['user:id,first_name,last_name', 'user.profile:id,user_id,profile_image']);

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully',
                'data' => $comment,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a comment — allowed by comment author OR innovation owner
     */
    public function destroy($innovationId, $commentId)
    {
        try {
            $comment = InnovationComment::where('id', $commentId)
                ->where('innovation_id', $innovationId)
                ->first();

            if (!$comment) {
                return response()->json(['success' => false, 'message' => 'Comment not found'], 404);
            }

            $userId     = auth()->id();
            $innovation = Innovation::find($innovationId);
            $isOwner    = $innovation && $innovation->user_id === $userId;

            if ($comment->user_id !== $userId && !$isOwner) {
                return response()->json(['success' => false, 'message' => 'Not authorized to delete this comment'], 403);
            }

            $comment->delete();

            return response()->json(['success' => true, 'message' => 'Comment deleted successfully'], 200);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete comment', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Like or dislike a comment
     */
    public function toggleLike(Request $request, $innovationId, $commentId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'is_like' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $comment = InnovationComment::where('id', $commentId)
                ->where('innovation_id', $innovationId)
                ->first();

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                ], 404);
            }

            DB::beginTransaction();

            $existingLike = InnovationCommentLike::where('user_id', auth()->id())
                ->where('comment_id', $commentId)
                ->first();

            if ($existingLike) {
                // If clicking the same button, remove the like/dislike
                if ($existingLike->is_like == $request->is_like) {
                    $existingLike->delete();
                } else {
                    // If switching from like to dislike or vice versa
                    $existingLike->update(['is_like' => $request->is_like]);
                }
            } else {
                // Create new like/dislike
                InnovationCommentLike::create([
                    'user_id' => auth()->id(),
                    'comment_id' => $commentId,
                    'is_like' => $request->is_like,
                ]);
            }

            DB::commit();

            // Refresh counts
            $likesCount = InnovationCommentLike::where('comment_id', $commentId)
                ->where('is_like', true)
                ->count();

            $dislikesCount = InnovationCommentLike::where('comment_id', $commentId)
                ->where('is_like', false)
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Action completed successfully',
                'data' => [
                    'likes_count' => $likesCount,
                    'dislikes_count' => $dislikesCount,
                    'user_has_liked' => InnovationCommentLike::where('user_id', auth()->id())
                        ->where('comment_id', $commentId)
                        ->where('is_like', true)
                        ->exists(),
                    'user_has_disliked' => InnovationCommentLike::where('user_id', auth()->id())
                        ->where('comment_id', $commentId)
                        ->where('is_like', false)
                        ->exists(),
                ],
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process like/dislike',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get average rating for an innovation
     */
    public function getAverageRating($innovationId)
    {
        try {
            $stats = InnovationComment::where('innovation_id', $innovationId)
                ->selectRaw('
                    AVG(rating) as average_rating,
                    COUNT(*) as total_ratings,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                ')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'average_rating' => round($stats->average_rating ?? 0, 1),
                    'total_ratings' => $stats->total_ratings ?? 0,
                    'rating_breakdown' => [
                        5 => $stats->five_star ?? 0,
                        4 => $stats->four_star ?? 0,
                        3 => $stats->three_star ?? 0,
                        2 => $stats->two_star ?? 0,
                        1 => $stats->one_star ?? 0,
                    ],
                ],
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch rating statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all innovation comments (admin only)
     */
    public function adminIndex(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search');

            $query = InnovationComment::with([
                'user:id,first_name,last_name,email', 
                'user.profile:id,user_id,profile_image',
                'innovation:id,title'
            ]);

            if ($search) {
                $query->where('text', 'like', "%{$search}%")
                    ->orWhereHas('user', function($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('innovation', function($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%");
                    });
            }

            $comments = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $comments->getCollection()->transform(function ($comment) {
                return [
                    'id' => $comment->id,
                    'text' => $comment->text,
                    'rating' => $comment->rating,
                    'date' => $comment->created_at->format('M d, Y'),
                    'author' => $comment->user ? ($comment->user->first_name . ' ' . $comment->user->last_name) : 'Unknown User',
                    'author_email' => $comment->user ? $comment->user->email : null,
                    'author_image' => $comment->user && $comment->user->profile ? $comment->user->profile->profile_image_url : null,
                    'innovation_title' => $comment->innovation ? $comment->innovation->title : 'Deleted Innovation',
                    'innovation_id' => $comment->innovation_id,
                    'created_at' => $comment->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $comments,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin delete comment
     */
    public function adminDestroy($id)
    {
        try {
            $comment = InnovationComment::findOrFail($id);
            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comment removed by administrator',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
