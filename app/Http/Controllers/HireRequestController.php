<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\HireRequest;
use App\Models\RegisterUsers\User;
use App\Notifications\HireRequestResponseNotification;
use App\Notifications\NewHireRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HireRequestController extends Controller
{
    /** Send a hire request to a provider. */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider_user_id' => 'required|uuid|exists:users,id',
            'title'            => 'required|string|max:255',
            'description'      => 'required|string|max:3000',
            'budget'           => 'nullable|numeric|min:0',
            'start_date'       => 'nullable|date|after_or_equal:today',
            'deadline'         => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $requesterId = Auth::id();

        if ($requesterId === $request->provider_user_id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot send a hire request to yourself.',
            ], 422);
        }

        $existing = HireRequest::where('requester_user_id', $requesterId)
            ->where('provider_user_id', $request->provider_user_id)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active hire request with this provider.',
            ], 422);
        }

        $hireRequest = HireRequest::create([
            'requester_user_id' => $requesterId,
            'provider_user_id'  => $request->provider_user_id,
            'title'             => $request->title,
            'description'       => $request->description,
            'budget'            => $request->budget,
            'start_date'        => $request->start_date,
            'deadline'          => $request->deadline,
        ]);

        $hireRequest->load('requester');
        $provider = User::find($request->provider_user_id);
        if ($provider) {
            $provider->notify(new NewHireRequestNotification($hireRequest));
        }

        $requester = Auth::user();
        $providerName = $provider ? "{$provider->first_name} {$provider->last_name}" : 'Unknown';
        AdminNotification::notify(
            'new_hire_request',
            'New Hire Request Submitted',
            "{$requester->first_name} {$requester->last_name} sent a hire request to {$providerName}: \"{$hireRequest->title}\".",
            ['hire_request_id' => $hireRequest->id, 'requester_email' => $requester->email]
        );

        return response()->json([
            'success' => true,
            'message' => 'Hire request sent successfully.',
            'data'    => $hireRequest,
        ], 201);
    }

    /** Incoming hire requests for the authenticated user (as provider). */
    public function incoming()
    {
        $requests = HireRequest::where('provider_user_id', Auth::id())
            ->with(['requester:id,first_name,last_name,email'])
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $requests]);
    }

    /** Outgoing hire requests sent by the authenticated user (as requester). */
    public function outgoing()
    {
        $requests = HireRequest::where('requester_user_id', Auth::id())
            ->with(['provider:id,first_name,last_name,email'])
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $requests]);
    }

    /** Accept a hire request (provider only). */
    public function accept($id)
    {
        $hireRequest = HireRequest::where('provider_user_id', Auth::id())
            ->where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        $hireRequest->update(['status' => 'accepted']);
        $hireRequest->load('provider');

        $requester = User::find($hireRequest->requester_user_id);
        if ($requester) {
            $requester->notify(new HireRequestResponseNotification($hireRequest));
        }

        return response()->json([
            'success' => true,
            'message' => 'Hire request accepted.',
            'data'    => $hireRequest,
        ]);
    }

    /** Decline a hire request (provider only). */
    public function decline($id)
    {
        $hireRequest = HireRequest::where('provider_user_id', Auth::id())
            ->where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        $hireRequest->update(['status' => 'declined']);
        $hireRequest->load('provider');

        $requester = User::find($hireRequest->requester_user_id);
        if ($requester) {
            $requester->notify(new HireRequestResponseNotification($hireRequest));
        }

        return response()->json([
            'success' => true,
            'message' => 'Hire request declined.',
            'data'    => $hireRequest,
        ]);
    }

    /** Mark a hire request as completed (provider only). */
    public function complete($id)
    {
        $hireRequest = HireRequest::where('provider_user_id', Auth::id())
            ->where('id', $id)
            ->where('status', 'accepted')
            ->firstOrFail();

        $hireRequest->update(['status' => 'completed']);

        return response()->json([
            'success' => true,
            'message' => 'Hire request marked as completed.',
            'data'    => $hireRequest,
        ]);
    }
}