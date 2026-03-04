<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegisterUsers\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // GET all users
    public function index(Request $request)
    {
        try {
            $query = User::query();

            // Search
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('first_name', 'like', "%{$request->search}%")
                        ->orWhere('last_name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            }

            // Role filter
            if ($request->role && $request->role !== 'all') {
                $query->where('role', $request->role);
            }

            $users = $query->latest()->get();

            // Add status field if it doesn't exist in database
            $users = $users->map(function($user) {
                $user->status = 'Active'; // Default status
                return $user;
            });

            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Store user
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name'  => 'required|string|max:255',
                'email'      => 'required|email|unique:users,email',
                'password'   => 'required|min:6',
                'role'       => 'required|string|in:Admin,Manager,SuperAdmin',
                'user_type'  => 'sometimes|string|in:regular,admin'
            ]);

            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'user_type' => $validated['user_type'] ?? 'regular'
            ]);

            return response()->json($user, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Update user
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'first_name' => 'sometimes|required|string|max:255',
                'last_name'  => 'sometimes|required|string|max:255',
                'email'      => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
                'password'   => 'sometimes|min:6',
                'role'       => 'sometimes|required|string|in:Admin,Manager,SuperAdmin',
                'user_type'  => 'sometimes|string|in:regular,admin'
            ]);

            $updateData = [
                'first_name' => $validated['first_name'] ?? $user->first_name,
                'last_name' => $validated['last_name'] ?? $user->last_name,
                'email' => $validated['email'] ?? $user->email,
                'role' => $validated['role'] ?? $user->role,
                'user_type' => $validated['user_type'] ?? $user->user_type,
            ];

            if (isset($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            return response()->json($user);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Delete user
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return response()->json(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Bulk Action
    public function bulkAction(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:users,id',
                'action' => 'required|string|in:delete,activate'
            ]);

            if ($request->action === 'delete') {
                User::whereIn('id', $request->ids)->delete();
            } elseif ($request->action === 'activate') {
                // If you have a status field
                // User::whereIn('id', $request->ids)->update(['status' => 'Active']);
            }

            return response()->json(['message' => 'Bulk action completed successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
