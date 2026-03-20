<?php

namespace App\Http\Controllers;

use App\Models\Portfolio\Experience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ExperienceController extends Controller
{
    /**
     * Safely get S3 URL or null — never passes empty string to AWS
     */
    private function getS3Url(?string $path): ?string
    {
        if ($path === null) return null;
        $cleaned = trim($path);
        if (strlen($cleaned) === 0) return null;

        try {
            return Storage::disk('s3')->url($cleaned);
        } catch (\Exception $e) {
            \Log::warning('S3 URL generation failed', ['path' => $cleaned, 'error' => $e->getMessage()]);
            return null;
        }
    }

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
            \Log::warning('S3 delete failed', ['path' => $cleaned, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Append S3 URL to experience object
     */
    private function withLogoUrl(Experience $experience): Experience
    {
        $experience->company_logo_url = $this->getS3Url($experience->company_logo);
        return $experience;
    }

    /**
     * Get all experiences for authenticated user
     */
    public function index()
    {
        $experiences = Experience::where('user_id', Auth::id())
            ->orderBy('start_year', 'desc')
            ->orderByRaw("FIELD(start_month, 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec')")
            ->get()
            ->map(fn($experience) => $this->withLogoUrl($experience));

        return response()->json([
            'success' => true,
            'data'    => $experiences,
        ]);
    }

    /**
     * Store a new experience
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'                => 'required|string|max:255',
            'organization'         => 'required|string|max:255',
            'company_logo'         => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'is_currently_working' => 'sometimes|boolean',
            'start_month'          => 'required|string|max:20',
            'start_year'           => 'required|integer|min:1900|max:' . date('Y'),
            'end_month'            => 'required_if:is_currently_working,false|nullable|string|max:20',
            'end_year'             => 'required_if:is_currently_working,false|nullable|integer|min:1900|max:' . date('Y'),
            'description'          => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Upload logo to S3
        $logoPath = null;
        if ($request->hasFile('company_logo')) {
            $logoPath = Storage::disk('s3')->putFile(
                'company-logos',
                $request->file('company_logo')
            );
        }

        $isCurrentlyWorking = $request->boolean('is_currently_working', false);

        $experience = Experience::create([
            'user_id'              => Auth::id(),
            'title'                => $request->title,
            'organization'         => $request->organization,
            'company_logo'         => $logoPath,
            'is_currently_working' => $isCurrentlyWorking,
            'start_month'          => $request->start_month,
            'start_year'           => $request->start_year,
            'end_month'            => $isCurrentlyWorking ? null : $request->end_month,
            'end_year'             => $isCurrentlyWorking ? null : $request->end_year,
            'description'          => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Experience created successfully',
            'data'    => $this->withLogoUrl($experience),
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
            'title'                => 'sometimes|string|max:255',
            'organization'         => 'sometimes|string|max:255',
            'company_logo'         => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'is_currently_working' => 'sometimes|boolean',
            'start_month'          => 'sometimes|string|max:20',
            'start_year'           => 'sometimes|integer|min:1900|max:' . date('Y'),
            'end_month'            => 'required_if:is_currently_working,false|nullable|string|max:20',
            'end_year'             => 'required_if:is_currently_working,false|nullable|integer|min:1900|max:' . date('Y'),
            'description'          => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Replace logo in S3 if a new one is uploaded
        if ($request->hasFile('company_logo')) {
            // Delete old logo from S3
            $this->deleteFromS3($experience->company_logo);

            // Upload new logo to S3
            $experience->company_logo = Storage::disk('s3')->putFile(
                'company-logos',
                $request->file('company_logo')
            );
        }

        $isCurrentlyWorking = $request->has('is_currently_working')
            ? $request->boolean('is_currently_working')
            : $experience->is_currently_working;

        $experience->update([
            'title'                => $request->title         ?? $experience->title,
            'organization'         => $request->organization  ?? $experience->organization,
            'is_currently_working' => $isCurrentlyWorking,
            'start_month'          => $request->start_month   ?? $experience->start_month,
            'start_year'           => $request->start_year    ?? $experience->start_year,
            'end_month'            => $isCurrentlyWorking ? null : ($request->end_month ?? $experience->end_month),
            'end_year'             => $isCurrentlyWorking ? null : ($request->end_year  ?? $experience->end_year),
            'description'          => $request->description   ?? $experience->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Experience updated successfully',
            'data'    => $this->withLogoUrl($experience->fresh()),
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

        // Delete logo from S3
        $this->deleteFromS3($experience->company_logo);

        $experience->delete();

        return response()->json([
            'success' => true,
            'message' => 'Experience deleted successfully',
        ]);
    }
}
