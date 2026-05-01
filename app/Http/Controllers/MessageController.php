<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\RegisterUsers\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Send a message to a user's profile.
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_user_id' => 'required|uuid|exists:users,id',
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|max:255',
            'message'           => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $authUser = Auth::user();

        if ($authUser && $authUser->id === $request->recipient_user_id) {
            return response()->json([
                'status'  => false,
                'message' => 'You cannot send a message to yourself.',
            ], 422);
        }

        $message = Message::create([
            'sender_user_id'    => $authUser?->id,
            'recipient_user_id' => $request->recipient_user_id,
            'sender_name'       => $request->name,
            'sender_email'      => $request->email,
            'message'           => $request->message,
        ]);

        $recipient = User::find($request->recipient_user_id);
        if ($recipient) {
            $recipient->notify(new NewMessageNotification($message));
        }

        return response()->json([
            'status'  => true,
            'message' => 'Message sent successfully.',
            'data'    => $message,
        ], 201);
    }

    /**
     * List messages received by the authenticated user.
     */
    public function inbox()
    {
        $messages = Message::where('recipient_user_id', Auth::id())
            ->with('sender:id,first_name,last_name')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $messages,
        ]);
    }

    /**
     * Mark a message as read.
     */
    public function markRead($id)
    {
        $message = Message::where('recipient_user_id', Auth::id())
            ->findOrFail($id);

        $message->update(['is_read' => true]);

        return response()->json(['status' => true]);
    }

    /**
     * Permanently delete a message from the recipient's inbox.
     */
    public function destroy($id)
    {
        $message = Message::where('recipient_user_id', Auth::id())
            ->findOrFail($id);

        $message->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Message deleted successfully.',
        ]);
    }
}