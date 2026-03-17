<?php

namespace App\Http\Controllers;

use App\Models\Innovation\Innovation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class InnovationController extends Controller
{
    /**
     * Store a new innovation
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'video_url' => 'required|url',
            'thumbnail' => 'nullable|url',
            'category' => 'required|string|in:Computer Science,Engineering,Medicine,Biology,Physics',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'tags' => 'nullable|string',
            'is_paid' => 'required|boolean',
            'price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $innovation = Innovation::create([
                'user_id' => $request->user_id,
                'title' => $request->title,
                'description' => $request->description,
                'video_url' => $request->video_url,
                'thumbnail' => $request->thumbnail,
                'category' => $request->category,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'tags' => $request->tags,
                'is_paid' => $request->is_paid,
                'price' => $request->is_paid ? $request->price : null,
                'status' => 'pending', // pending, approved, rejected
                'views' => 0,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Innovation uploaded successfully',
                'data' => $innovation
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create innovation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all innovations
     */
    public function index(Request $request)
    {
        $query = Innovation::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by title or tags
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('tags', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $innovations = $query->paginate($perPage);

        return response()->json($innovations, 200);
    }

    /**
     * Get single innovation
     */
    public function show($id)
    {
        $innovation = Innovation::find($id);

        if (!$innovation) {
            return response()->json([
                'error' => 'Innovation not found'
            ], 404);
        }

        // Increment views
        $innovation->increment('views');

        return response()->json($innovation, 200);
    }

    /**
     * Update innovation
     */
    public function update(Request $request, $id)
    {
        $innovation = Innovation::find($id);

        if (!$innovation) {
            return response()->json([
                'error' => 'Innovation not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:500',
            'category' => 'sometimes|string|in:Computer Science,Engineering,Medicine,Biology,Physics',
            'tags' => 'nullable|string',
            'status' => 'sometimes|in:pending,approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $innovation->update($request->only([
                'title', 'description', 'category', 'tags', 'status'
            ]));

            return response()->json([
                'message' => 'Innovation updated successfully',
                'data' => $innovation
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update innovation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete innovation
     */
    public function destroy($id)
    {
        $innovation = Innovation::find($id);

        if (!$innovation) {
            return response()->json([
                'error' => 'Innovation not found'
            ], 404);
        }

        try {
            // Optionally delete files from S3
            // Storage::disk('s3')->delete($innovation->video_url);
            // if ($innovation->thumbnail) {
            //     Storage::disk('s3')->delete($innovation->thumbnail);
            // }

            $innovation->delete();

            return response()->json([
                'message' => 'Innovation deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete innovation',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
