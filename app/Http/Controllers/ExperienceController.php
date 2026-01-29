<?php

namespace App\Http\Controllers;


use App\Models\Portfolio\Experience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ExperienceController extends Controller
{
    /**
     * Get all experiences for authenticated user
     */
    public function index()
    {
        $experiences = Experience::where('user_id', Auth::id())
            ->orderBy('start_year', 'desc')
            ->orderByRaw("FIELD(start_month, 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec')")
            ->get();

        return response()->json([
            'success' => true,
            'data' => $experiences
        ]);
    }

    /**
     * Store a new experience
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'organization' => 'required|string|max:255',
            'is_currently_working' => 'sometimes|boolean',
            'start_month' => 'required|string|max:20',
            'start_year' => 'required|integer|min:1900|max:' . date('Y'),
            'end_month' => 'required_if:is_currently_working,false|string|max:20|nullable',
            'end_year' => 'required_if:is_currently_working,false|integer|min:1900|max:' . date('Y') . '|nullable',
            'description' => 'sometimes|string|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $experience = Experience::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'organization' => $request->organization,
            'is_currently_working' => $request->is_currently_working ?? false,
            'start_month' => $request->start_month,
            'start_year' => $request->start_year,
            'end_month' => $request->is_currently_working ? null : $request->end_month,
            'end_year' => $request->is_currently_working ? null : $request->end_year,
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Experience created successfully',
            'data' => $experience
        ], 201);
    }

    /**
     * Update an experience
     */
    public function update(Request $request, $id)
    {
        $experience = Experience::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'organization' => 'sometimes|string|max:255',
            'is_currently_working' => 'sometimes|boolean',
            'start_month' => 'sometimes|string|max:20',
            'start_year' => 'sometimes|integer|min:1900|max:' . date('Y'),
            'end_month' => 'required_if:is_currently_working,false|string|max:20|nullable',
            'end_year' => 'required_if:is_currently_working,false|integer|min:1900|max:' . date('Y') . '|nullable',
            'description' => 'sometimes|string|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $experience->update([
            'title' => $request->title ?? $experience->title,
            'organization' => $request->organization ?? $experience->organization,
            'is_currently_working' => $request->is_currently_working ?? $experience->is_currently_working,
            'start_month' => $request->start_month ?? $experience->start_month,
            'start_year' => $request->start_year ?? $experience->start_year,
            'end_month' => $request->is_currently_working ? null : ($request->end_month ?? $experience->end_month),
            'end_year' => $request->is_currently_working ? null : ($request->end_year ?? $experience->end_year),
            'description' => $request->description ?? $experience->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Experience updated successfully',
            'data' => $experience
        ]);
    }

    /**
     * Delete an experience
     */
    public function destroy($id)
    {
        $experience = Experience::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $experience->delete();

        return response()->json([
            'success' => true,
            'message' => 'Experience deleted successfully'
        ]);
    }
}
