<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormMail;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Handle a contact form submission: store it and email the admin.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $contactMessage = ContactMessage::create($validator->validated());

        try {
            $adminEmail = env('ADMIN_EMAIL', config('mail.from.address'));
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new ContactFormMail($contactMessage));
            }
        } catch (\Exception $mailEx) {
            Log::warning('Contact form email failed: ' . $mailEx->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Your message was received but we could not send the notification email. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for reaching out! We will get back to you soon.',
        ], 201);
    }
}