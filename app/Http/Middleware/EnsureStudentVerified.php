<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureStudentVerified
{
    // Write operations that create / modify content
    private const WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next)
    {
        if (!in_array($request->method(), self::WRITE_METHODS)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user) {
            $student = $user->student;

            if ($student && $student->verification_status !== 'approved') {
                return response()->json([
                    'success'             => false,
                    'message'             => 'Your student verification is ' . $student->verification_status . '. You cannot perform this action until your birth certificate is approved.',
                    'verification_status' => $student->verification_status,
                ], 403);
            }
        }

        return $next($request);
    }
}