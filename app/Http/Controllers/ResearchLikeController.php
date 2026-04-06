<?php

namespace App\Http\Controllers;

use App\Models\Research\Research;
use App\Models\Research\ResearchLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class ResearchLikeController extends Controller
{
    /**
     * Like or dislike a research
     */
    public function toggleLike(Request $request, $researchId)
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

            $research = Research::find($researchId);

            if (!$research) {
                return response()->json([
                    'success' => false,
                    'message' => 'Research not found',
                ], 404);
            }

            DB::beginTransaction();

            $existingLike = ResearchLike::where('user_id', auth()->id())
                ->where('research_id', $researchId)
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
                ResearchLike::create([
                    'user_id' => auth()->id(),
                    'research_id' => $researchId,
                    'is_like' => $request->is_like,
                ]);
            }

            DB::commit();

            // Refresh counts
            $likesCount = ResearchLike::where('research_id', $researchId)
                ->where('is_like', true)
                ->count();

            $dislikesCount = ResearchLike::where('research_id', $researchId)
                ->where('is_like', false)
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Action completed successfully',
                'data' => [
                    'likes_count' => $likesCount,
                    'dislikes_count' => $dislikesCount,
                    'user_has_liked' => ResearchLike::where('user_id', auth()->id())
                        ->where('research_id', $researchId)
                        ->where('is_like', true)
                        ->exists(),
                    'user_has_disliked' => ResearchLike::where('user_id', auth()->id())
                        ->where('research_id', $researchId)
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
