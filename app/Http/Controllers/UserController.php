<?php

namespace App\Http\Controllers;

use App\Models\RegisterUsers\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Get Users
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        try {

            $query = User::query();

            // Search
            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('first_name', 'like', "%{$request->search}%")
                        ->orWhere('last_name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            }

            // Role filter
            if ($request->role && $request->role !== 'all') {
                $query->where('role', $request->role);
            }

            // Status filter
            if ($request->status) {
                $query->where('status', $request->status);
            }

            $users = $query->latest()->get();

            return response()->json($users);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 500);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create User
    |--------------------------------------------------------------------------
    */

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
                'last_name'  => $validated['last_name'],
                'email'      => $validated['email'],
                'password'   => Hash::make($validated['password']),
                'role'       => $validated['role'],
                'user_type'  => $validated['user_type'] ?? 'regular',
                'status'     => 'Active'
            ]);

            return response()->json($user, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 500);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update User
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        try {

            $user = User::findOrFail($id);

            $validated = $request->validate([
                'first_name' => 'sometimes|required|string|max:255',
                'last_name'  => 'sometimes|required|string|max:255',
                'email'      => [
                    'sometimes',
                    'required',
                    'email',
                    Rule::unique('users')->ignore($user->id)
                ],
                'password'   => 'sometimes|min:6',
                'role'       => 'sometimes|required|string|in:Admin,Manager,SuperAdmin',
                'user_type'  => 'sometimes|string|in:regular,admin',
                'status'     => 'sometimes|in:Active,Inactive'
            ]);

            $updateData = [
                'first_name' => $validated['first_name'] ?? $user->first_name,
                'last_name'  => $validated['last_name'] ?? $user->last_name,
                'email'      => $validated['email'] ?? $user->email,
                'role'       => $validated['role'] ?? $user->role,
                'user_type'  => $validated['user_type'] ?? $user->user_type,
                'status'     => $validated['status'] ?? $user->status
            ];

            if (isset($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            return response()->json($user);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 500);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete User
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        try {

            $user = User::findOrFail($id);
            $user->delete();

            return response()->json([
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 500);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle User Status (Activate / Deactivate)
    |--------------------------------------------------------------------------
    */

    public function toggleStatus($id)
    {
        try {

            $user = User::findOrFail($id);

            $user->status = $user->status === 'Active' ? 'Inactive' : 'Active';
            $user->save();

            return response()->json([
                'message' => 'User status updated',
                'status'  => $user->status
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 500);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Action
    |--------------------------------------------------------------------------
    */

    public function bulkAction(Request $request)
    {
        try {

            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'string|exists:users,id',
                'action' => 'required|string|in:delete,activate,deactivate'
            ]);

            if ($request->action === 'delete') {

                User::whereIn('id', $request->ids)->delete();

            } elseif ($request->action === 'activate') {

                User::whereIn('id', $request->ids)->update([
                    'status' => 'Active'
                ]);

            } elseif ($request->action === 'deactivate') {

                User::whereIn('id', $request->ids)->update([
                    'status' => 'Inactive'
                ]);
            }

            return response()->json([
                'message' => 'Bulk action completed successfully'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 500);

        }
    }
}
