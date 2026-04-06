<?php

namespace App\Http\Controllers;

use App\Models\Innovation\Innovation;
use App\Models\Innovation\InnovationLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class InnovationLikeController extends Controller
{
    /**
     * Like or dislike an innovation
     */
    public function toggleLike(Request $request, $innovationId)
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

            $innovation = Innovation::find($innovationId);

            if (!$innovation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Innovation not found',
                ], 404);
            }

            DB::beginTransaction();

            $existingLike = InnovationLike::where('user_id', auth()->id())
                ->where('innovation_id', $innovationId)
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
                InnovationLike::create([
                    'user_id' => auth()->id(),
                    'innovation_id' => $innovationId,
                    'is_like' => $request->is_like,
                ]);
            }

            DB::commit();

            // Refresh counts
            $likesCount = InnovationLike::where('innovation_id', $innovationId)
                ->where('is_like', true)
                ->count();

            $dislikesCount = InnovationLike::where('innovation_id', $innovationId)
                ->where('is_like', false)
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Action completed successfully',
                'data' => [
                    'likes_count' => $likesCount,
                    'dislikes_count' => $dislikesCount,
                    'user_has_liked' => InnovationLike::where('user_id', auth()->id())
                        ->where('innovation_id', $innovationId)
                        ->where('is_like', true)
                        ->exists(),
                    'user_has_disliked' => InnovationLike::where('user_id', auth()->id())
                        ->where('innovation_id', $innovationId)
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
}
