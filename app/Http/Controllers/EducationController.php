<?php

namespace App\Http\Controllers;

use App\Models\Portfolio\Education;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class EducationController extends Controller
{
    /**
     * Safely delete from S3 — never passes empty string to AWS
     */
    private function deleteFromS3(?string $path): void
    {
        if ($path === null) return;
        $cleaned = trim($path);
        if (strlen($cleaned) === 0) return;

        try {
            if (Storage::disk('s3')->exists($cleaned)) {
                Storage::disk('s3')->delete($cleaned);
            }
        } catch (\Exception $e) {
            \Log::warning('S3 delete failed', [
                'path'  => $cleaned,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all educations for authenticated user
     */
    public function index()
    {
        $educations = Education::where('user_id', Auth::id())
            ->orderBy('start_year', 'desc')
            ->orderByRaw("FIELD(start_month, 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec')")
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $educations,
        ]);
    }

    /**
     * Store a new education
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school'         => 'required|string|max:255',
            'institute_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'degree'         => 'nullable|string|max:255',
            'field_of_study' => 'nullable|string|max:255',
            'grade'          => 'nullable|string|max:50',
            'activities'     => 'nullable|string',
            'description'    => 'nullable|string',
            'start_month'    => 'required|string|max:20',
            'start_year'     => 'required|integer|min:1900|max:' . date('Y'),
            'end_month'      => 'required|string|max:20',
            'end_year'       => 'required|integer|min:1900|max:' . date('Y'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // ✅ Upload logo to S3
        $logoPath = null;
        if ($request->hasFile('institute_logo')) {
            $logoPath = Storage::disk('s3')->putFile(
                'education-logos',
                $request->file('institute_logo')
            );
        }

        $education = Education::create([
            'user_id'        => Auth::id(),
            'school'         => $request->school,
            'institute_logo' => $logoPath,
            'degree'         => $request->degree         ?: null,
            'field_of_study' => $request->field_of_study ?: null,
            'grade'          => $request->grade          ?: null,
            'activities'     => $request->activities     ?: null,
            'description'    => $request->description    ?: null,
            'start_month'    => $request->start_month,
            'start_year'     => $request->start_year,
            'end_month'      => $request->end_month,
            'end_year'       => $request->end_year,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Education created successfully',
            'data'    => $education,
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
            'school'         => 'sometimes|string|max:255',
            'institute_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'degree'         => 'nullable|string|max:255',
            'field_of_study' => 'nullable|string|max:255',
            'grade'          => 'nullable|string|max:50',
            'activities'     => 'nullable|string',
            'description'    => 'nullable|string',
            'start_month'    => 'sometimes|string|max:20',
            'start_year'     => 'sometimes|integer|min:1900|max:' . date('Y'),
            'end_month'      => 'sometimes|string|max:20',
            'end_year'       => 'sometimes|integer|min:1900|max:' . date('Y'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // ✅ Replace logo in S3 if a new one is uploaded
        if ($request->hasFile('institute_logo')) {
            // Delete old logo from S3
            $this->deleteFromS3($education->institute_logo);

            // Upload new logo to S3
            $education->institute_logo = Storage::disk('s3')->putFile(
                'education-logos',
                $request->file('institute_logo')
            );
        }

        $education->update([
            'school'         => $request->school          ?? $education->school,
            'degree'         => $request->degree          ?? $education->degree,
            'field_of_study' => $request->field_of_study  ?? $education->field_of_study,
            'grade'          => $request->grade            ?? $education->grade,
            'activities'     => $request->activities       ?? $education->activities,
            'description'    => $request->description      ?? $education->description,
            'start_month'    => $request->start_month      ?? $education->start_month,
            'start_year'     => $request->start_year       ?? $education->start_year,
            'end_month'      => $request->end_month        ?? $education->end_month,
            'end_year'       => $request->end_year         ?? $education->end_year,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Education updated successfully',
            'data'    => $education->fresh(),
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

        // ✅ Delete logo from S3
        $this->deleteFromS3($education->institute_logo);

        $education->delete();

        return response()->json([
            'success' => true,
            'message' => 'Education deleted successfully',
        ]);
    }
}
