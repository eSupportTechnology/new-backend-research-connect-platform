<?php

namespace App\Http\Controllers;


use App\Models\Research\Research;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ResearchController extends Controller
{
    /**
     * Store a new research paper
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'abstract' => 'required|string|max:500',
            'document_url' => 'required|url',
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

            $research = Research::create([
                'user_id' => $request->user_id,
                'title' => $request->title,
                'abstract' => $request->abstract,
                'document_url' => $request->document_url,
                'thumbnail' => $request->thumbnail,
                'category' => $request->category,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'tags' => $request->tags,
                'is_paid' => $request->is_paid,
                'price' => $request->is_paid ? $request->price : null,
                'status' => 'pending', // pending, approved, rejected
                'views' => 0,
                'downloads' => 0,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Research uploaded successfully',
                'data' => $research
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create research',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all research papers
     */
    public function index(Request $request)
    {
        $query = Research::query();

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
                    ->orWhere('abstract', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $research = $query->paginate($perPage);

        return response()->json($research, 200);
    }

    /**
     * Get single research paper
     */
    public function show($id)
    {
        $research = Research::find($id);

        if (!$research) {
            return response()->json([
                'error' => 'Research not found'
            ], 404);
        }

        // Increment views
        $research->increment('views');

        return response()->json($research, 200);
    }

    /**
     * Update research paper
     */
    public function update(Request $request, $id)
    {
        $research = Research::find($id);

        if (!$research) {
            return response()->json([
                'error' => 'Research not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'abstract' => 'sometimes|string|max:500',
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
            $research->update($request->only([
                'title', 'abstract', 'category', 'tags', 'status'
            ]));

            return response()->json([
                'message' => 'Research updated successfully',
                'data' => $research
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update research',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete research paper
     */
    public function destroy($id)
    {
        $research = Research::find($id);

        if (!$research) {
            return response()->json([
                'error' => 'Research not found'
            ], 404);
        }

        try {
            // Optionally delete files from S3
            // Storage::disk('s3')->delete($research->document_url);
            // if ($research->thumbnail) {
            //     Storage::disk('s3')->delete($research->thumbnail);
            // }

            $research->delete();

            return response()->json([
                'message' => 'Research deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete research',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Increment downloads counter
     */
    public function incrementDownloads($id)
    {
        $research = Research::find($id);

        if (!$research) {
            return response()->json([
                'error' => 'Research not found'
            ], 404);
        }

        $research->increment('downloads');

        return response()->json([
            'message' => 'Download count updated',
            'downloads' => $research->downloads
        ], 200);
    }
}
