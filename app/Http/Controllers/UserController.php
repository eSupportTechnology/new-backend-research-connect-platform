<?php

namespace App\Http\Controllers;

use App\Models\RegisterUsers\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Retention window (days) a soft-deleted account must sit in the deleted
     * portal before it becomes eligible for permanent deletion.
     */
    const RETENTION_DAYS = 30;

    /**
     * Only a super admin may restore or permanently delete accounts.
     */
    private function requireSuperAdmin()
    {
        $role = strtolower((string) auth()->user()?->role);
        return in_array($role, ['superadmin', 'super_admin'], true);
    }

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
                'role'       => 'required|string|in:admin,manager,superadmin,marketing',
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
                'role'       => 'sometimes|required|string|in:admin,manager,superadmin,marketing',
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

            // Soft delete only — the account moves to the "Deleted Users" portal
            // and can be restored. Permanent deletion is a separate, time-gated action.
            $user->deleted_by = auth()->id();
            $user->save();
            $user->tokens()->delete();   // revoke sessions so the account is logged out immediately
            $user->delete();

            AuditLog::logAction('USER_SOFT_DELETE', "Moved user to deleted portal: {$user->email}");

            return response()->json([
                'message' => 'User moved to the deleted portal. It can be restored within the retention period.'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 500);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | Deleted Users Portal (recycle bin)
    |--------------------------------------------------------------------------
    */

    // GET /users/deleted — list soft-deleted accounts
    public function deleted(Request $request)
    {
        if (!$this->requireSuperAdmin()) {
            return response()->json(['message' => 'Only a super admin can access the deleted portal.'], 403);
        }

        $users = User::onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->get()
            ->map(function ($u) {
                $deletedAt  = $u->deleted_at;
                $eligibleAt = $deletedAt ? $deletedAt->copy()->addDays(self::RETENTION_DAYS) : null;
                $canDelete  = $eligibleAt ? now()->greaterThanOrEqualTo($eligibleAt) : false;
                $daysLeft   = (!$canDelete && $eligibleAt && now()->lessThan($eligibleAt))
                    ? (int) ceil(now()->diffInDays($eligibleAt)) : 0;

                return [
                    'id'                     => $u->id,
                    'first_name'             => $u->first_name,
                    'last_name'              => $u->last_name,
                    'email'                  => $u->email,
                    'role'                   => $u->role,
                    'deleted_at'             => $deletedAt,
                    'deleted_by'             => $u->deleted_by,
                    'eligible_at'            => $eligibleAt,
                    'days_remaining'         => $daysLeft,
                    'can_permanently_delete' => $canDelete,
                ];
            });

        return response()->json([
            'success'        => true,
            'data'           => $users,
            'retention_days' => self::RETENTION_DAYS,
        ]);
    }

    // POST /users/{id}/restore — bring an account back from the deleted portal
    public function restore($id)
    {
        if (!$this->requireSuperAdmin()) {
            return response()->json(['message' => 'Only a super admin can restore accounts.'], 403);
        }

        try {
            $user = User::onlyTrashed()->findOrFail($id);
            $user->restore();
            $user->deleted_by = null;
            $user->save();

            AuditLog::logAction('USER_RESTORE', "Restored user from deleted portal: {$user->email}");

            return response()->json(['success' => true, 'message' => 'User restored successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // DELETE /users/{id}/force — permanent deletion (time-gated + typed confirmation)
    public function forceDelete(Request $request, $id)
    {
        if (!$this->requireSuperAdmin()) {
            return response()->json(['message' => 'Only a super admin can permanently delete accounts.'], 403);
        }

        try {
            $user = User::onlyTrashed()->findOrFail($id);

            // Typed confirmation guard
            if ($request->input('confirmation') !== 'DELETE') {
                return response()->json(['message' => 'Type DELETE to confirm permanent deletion.'], 422);
            }

            // Time-gate: the retention window must have fully elapsed
            $eligibleAt = $user->deleted_at?->copy()->addDays(self::RETENTION_DAYS);
            if (!$eligibleAt || now()->lessThan($eligibleAt)) {
                $daysLeft = $eligibleAt ? (int) ceil(now()->diffInDays($eligibleAt)) : self::RETENTION_DAYS;
                return response()->json([
                    'message' => "This account can only be permanently deleted after the {$daysLeft}-day retention period.",
                ], 403);
            }

            $email = $user->email;
            $user->forceDelete();

            AuditLog::logAction('USER_FORCE_DELETE', "Permanently deleted user: {$email}");

            return response()->json(['success' => true, 'message' => 'User permanently deleted.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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

            AuditLog::logAction('USER_STATUS_TOGGLE', "Changed status of {$user->email} to {$user->status}");

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

                // Soft delete — record who deleted, then move to the deleted portal
                User::whereIn('id', $request->ids)->update(['deleted_by' => auth()->id()]);
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

            AuditLog::logAction('BULK_USER_ACTION', "Performed {$request->action} on " . count($request->ids) . " users text-center");

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
