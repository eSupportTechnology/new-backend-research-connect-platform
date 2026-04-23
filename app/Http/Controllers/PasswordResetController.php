<?php

namespace App\Http\Controllers;

use App\Models\RegisterUsers\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => match($status) {
                Password::INVALID_USER    => 'No account found with that email address.',
                Password::RESET_THROTTLED => 'Please wait before requesting another reset link.',
                default                   => 'Unable to send reset link. Please try again.',
            },
        ], 422);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete(); // invalidate existing sessions
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully. You can now log in.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => match($status) {
                Password::INVALID_TOKEN => 'This reset link is invalid or has expired.',
                Password::INVALID_USER  => 'No account found with that email address.',
                default                 => 'Unable to reset password. Please try again.',
            },
        ], 422);
    }
}