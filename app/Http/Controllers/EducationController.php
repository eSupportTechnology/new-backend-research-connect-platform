<?php

namespace App\Http\Controllers;


use App\Models\Portfolio\Education;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EducationController extends Controller
{
    /**
     * Get all educations for authenticated user
     */
    public function index()
    {
        $educations = Education::where('user_id', Auth::id())
            ->orderBy('start_year', 'desc')
            ->orderByRaw("FIELD(start_month, 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec')")
            ->get();

        return response()->json([
            'success' => true,
            'data' => $educations
        ]);
    }

    /**
     * Store a new education
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school' => 'required|string|max:255',
            'degree' => 'sometimes|string|max:255',
            'field_of_study' => 'sometimes|string|max:255',
            'start_month' => 'required|string|max:20',
            'start_year' => 'required|integer|min:1900|max:' . date('Y'),
            'end_month' => 'required|string|max:20',
            'end_year' => 'required|integer|min:1900|max:' . date('Y'),
            'description' => 'sometimes|string|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $education = Education::create([
            'user_id' => Auth::id(),
            'school' => $request->school,
            'degree' => $request->degree,
            'field_of_study' => $request->field_of_study,
            'start_month' => $request->start_month,
            'start_year' => $request->start_year,
            'end_month' => $request->end_month,
            'end_year' => $request->end_year,
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Education created successfully',
            'data' => $education
        ], 201);
    }

    /**
     * Update an education
     */
    public function update(Request $request, $id)
    {
        $education = Education::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'school' => 'sometimes|string|max:255',
            'degree' => 'sometimes|string|max:255',
            'field_of_study' => 'sometimes|string|max:255',
            'start_month' => 'sometimes|string|max:20',
            'start_year' => 'sometimes|integer|min:1900|max:' . date('Y'),
            'end_month' => 'sometimes|string|max:20',
            'end_year' => 'sometimes|integer|min:1900|max:' . date('Y'),
            'description' => 'sometimes|string|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $education->update([
            'school' => $request->school ?? $education->school,
            'degree' => $request->degree ?? $education->degree,
            'field_of_study' => $request->field_of_study ?? $education->field_of_study,
            'start_month' => $request->start_month ?? $education->start_month,
            'start_year' => $request->start_year ?? $education->start_year,
            'end_month' => $request->end_month ?? $education->end_month,
            'end_year' => $request->end_year ?? $education->end_year,
            'description' => $request->description ?? $education->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Education updated successfully',
            'data' => $education
        ]);
    }

    /**
     * Delete an education
     */
    public function destroy($id)
    {
        $education = Education::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $education->delete();

        return response()->json([
            'success' => true,
            'message' => 'Education deleted successfully'
        ]);
    }
}
