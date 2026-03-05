<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Innovation\Innovation;
use Illuminate\Http\Request;

class InnovationController extends Controller
{
    public function store(Request $request)
    {
        try {

            // validation
            $validated = $request->validate([
                'user_id' => 'required|uuid|exists:users,id',
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'video_url' => 'required|url',
                'thumbnail' => 'nullable|string',
                'category' => 'required|string|max:100',
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'tags' => 'nullable|string',
                'is_paid' => 'boolean',
                'price' => 'nullable|numeric'
            ]);

            // create innovation
            $innovation = Innovation::create([
                'user_id' => $validated['user_id'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'video_url' => $validated['video_url'],
                'thumbnail' => $validated['thumbnail'] ?? null,
                'category' => $validated['category'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'tags' => $validated['tags'] ?? null,
                'is_paid' => $validated['is_paid'] ?? false,
                'price' => $validated['price'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Innovation created successfully',
                'data' => $innovation
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Innovation creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
