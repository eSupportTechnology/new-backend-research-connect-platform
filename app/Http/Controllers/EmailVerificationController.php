<?php

namespace App\Http\Controllers;

use App\Models\RegisterUsers\User;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            return response()->json(['message' => 'Invalid verification link.'], 400);
        }

        if (!$request->hasValidSignature()) {
            return response()->json(['message' => 'Verification link has expired.'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.', 'email_verified' => true]);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email verified successfully.', 'email_verified' => true]);
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.', 'email_verified' => true]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent.']);
    }
}