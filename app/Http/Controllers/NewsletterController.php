<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterWelcomeMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    /**
     * Subscribe an email to the newsletter (re-activates if previously unsubscribed).
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid email address.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $email = strtolower($request->email);

        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if ($subscriber && $subscriber->is_active) {
            return response()->json([
                'success' => true,
                'message' => 'You are already subscribed to our newsletter.',
            ]);
        }

        if ($subscriber) {
            $subscriber->update(['is_active' => true, 'unsubscribed_at' => null]);
        } else {
            $subscriber = NewsletterSubscriber::create(['email' => $email]);
        }

        try {
            Mail::to($email)->send(new NewsletterWelcomeMail($email));
        } catch (\Exception $mailEx) {
            Log::warning('Newsletter welcome email failed: ' . $mailEx->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for subscribing! Please check your inbox.',
        ], 201);
    }

    /**
     * Unsubscribe an email from the newsletter.
     */
    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid email address.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $subscriber = NewsletterSubscriber::where('email', strtolower($request->email))->first();

        if (!$subscriber || !$subscriber->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This email is not subscribed to our newsletter.',
            ], 404);
        }

        $subscriber->update(['is_active' => false, 'unsubscribed_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'You have been unsubscribed from our newsletter.',
        ]);
    }
}