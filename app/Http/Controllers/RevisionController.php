<?php

namespace App\Http\Controllers;

use App\Models\Innovation\Innovation;
use App\Models\Research\Research;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RevisionController extends Controller
{
    const MAX_REVISIONS = 2;

    // ── Admin: Request Revision ───────────────────────────────────────────────

    public function requestInnovationRevision(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
            'block'  => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $innovation = Innovation::findOrFail($id);

        if ($innovation->revision_count >= self::MAX_REVISIONS) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum revisions reached. Use permanently-reject instead.',
            ], 400);
        }

        $innovation->update([
            'status'          => 'revision_requested',
            'revision_count'  => $innovation->revision_count + 1,
            'revision_reason' => $request->reason,
            'is_blocked'      => $request->boolean('block', false),
        ]);

        return response()->json(['success' => true, 'data' => $innovation]);
    }

    public function requestResearchRevision(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
            'block'  => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $research = Research::findOrFail($id);

        if ($research->revision_count >= self::MAX_REVISIONS) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum revisions reached. Use permanently-reject instead.',
            ], 400);
        }

        $research->update([
            'status'          => 'revision_requested',
            'revision_count'  => $research->revision_count + 1,
            'revision_reason' => $request->reason,
            'is_blocked'      => $request->boolean('block', false),
        ]);

        return response()->json(['success' => true, 'data' => $research]);
    }

    // ── Admin: Permanently Reject ─────────────────────────────────────────────

    public function permanentlyRejectInnovation(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $innovation = Innovation::findOrFail($id);
        $innovation->update([
            'status'          => 'permanently_rejected',
            'revision_reason' => $request->reason,
            'is_blocked'      => true,
        ]);

        return response()->json(['success' => true, 'data' => $innovation]);
    }

    public function permanentlyRejectResearch(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $research = Research::findOrFail($id);
        $research->update([
            'status'          => 'permanently_rejected',
            'revision_reason' => $request->reason,
            'is_blocked'      => true,
        ]);

        return response()->json(['success' => true, 'data' => $research]);
    }

    // ── User: Resubmit ────────────────────────────────────────────────────────

    public function resubmitInnovation(Request $request, $id)
    {
        $innovation = Innovation::where('user_id', auth()->id())->findOrFail($id);

        if ($innovation->status !== 'revision_requested') {
            return response()->json([
                'success' => false,
                'message' => 'This submission is not awaiting revision.',
            ], 400);
        }

        // Return to inactive so admin re-reviews before making it active
        $innovation->update([
            'status'     => 'inactive',
            'is_blocked' => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Resubmitted successfully. Your content is under review.']);
    }

    public function resubmitResearch(Request $request, $id)
    {
        $research = Research::where('user_id', auth()->id())->findOrFail($id);

        if ($research->status !== 'revision_requested') {
            return response()->json([
                'success' => false,
                'message' => 'This submission is not awaiting revision.',
            ], 400);
        }

        $research->update([
            'status'     => 'pending',
            'is_blocked' => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Resubmitted successfully. Your research is under review.']);
    }

    // ── User: My Submissions ──────────────────────────────────────────────────

    public function mySubmissions(Request $request)
    {
        $userId = auth()->id();

        $innovations = Innovation::where('user_id', $userId)
            ->select('id', 'title', 'thumbnail', 'category', 'status', 'revision_count', 'revision_reason', 'is_blocked', 'created_at')
            ->latest()
            ->get()
            ->map(fn($i) => array_merge($i->toArray(), ['type' => 'innovation']));

        $research = Research::where('user_id', $userId)
            ->select('id', 'title', 'thumbnail', 'category', 'status', 'revision_count', 'revision_reason', 'is_blocked', 'created_at')
            ->latest()
            ->get()
            ->map(fn($r) => array_merge($r->toArray(), ['type' => 'research']));

        return response()->json([
            'success' => true,
            'data' => [
                'innovations' => $innovations,
                'research'    => $research,
            ],
        ]);
    }
}