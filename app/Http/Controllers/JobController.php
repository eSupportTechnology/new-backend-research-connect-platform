<?php

namespace App\Http\Controllers;

use App\Http\Resources\JobResource;
use App\Models\Career;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class JobController extends Controller
{
    /**
     * Get all approved job posts with optional filters.
     */
    public function index(Request $request)
    {
        $query = Career::where('status', 'approved');

        if ($request->has('category') && $request->category !== 'All') {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $jobs = $query->latest()->paginate($request->get('per_page', 10));

        return JobResource::collection($jobs);
    }

    /**
     * Store a new job post.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'category' => 'required|string',
            'location' => 'required|string',
            'job_type' => 'required|string',
            'salary_range' => 'nullable|string',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'apply_link' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $logoUrl = null;
            if ($request->hasFile('logo')) {
                $logoUrl = $this->uploadFile($request->file('logo'), 'careers/logos');
            }

            // If user is admin, auto-approve. Otherwise pending.
            $status = (auth()->user()->role === 'admin' || auth()->user()->role === 'super_admin') ? 'approved' : 'pending';

            $job = Career::create([
                'user_id' => auth()->id(),
                'title' => $request->title,
                'company_name' => $request->company_name,
                'logo_url' => $logoUrl,
                'category' => $request->category,
                'location' => $request->location,
                'job_type' => $request->job_type,
                'salary_range' => $request->salary_range,
                'description' => $request->description,
                'requirements' => $request->requirements,
                'apply_link' => $request->apply_link,
                'status' => $status,
                'is_featured' => $request->boolean('is_featured', false),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Job post submitted successfully',
                'data' => new JobResource($job)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Job creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin: Update job status.
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,rejected',
            'is_featured' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $job = Career::findOrFail($id);
            $job->update($request->only(['status', 'is_featured']));

            return response()->json([
                'success' => true,
                'message' => 'Job status updated successfully',
                'data' => new JobResource($job)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found'
            ], 404);
        }
    }

    /**
     * Admin: Get all jobs for management.
     */
    public function getAdminJobs(Request $request)
    {
        $query = Career::with('user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->latest()->paginate($request->get('per_page', 20));

        return JobResource::collection($jobs);
    }

    /**
     * Delete a job post.
     */
    public function destroy($id)
    {
        try {
            $job = Career::findOrFail($id);

            // Access check: Admin or the owner
            if (auth()->user()->role !== 'admin' && auth()->user()->role !== 'super_admin' && auth()->id() !== $job->user_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            if ($job->logo_url) {
                $this->deleteFileByUrl($job->logo_url);
            }

            $job->delete();

            return response()->json(['success' => true, 'message' => 'Job deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Job not found'], 404);
        }
    }

    /**
     * S3 Upload Helper using standard Storage disk.
     */
    private function uploadFile($file, $directory)
    {
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = "{$directory}/{$filename}";

        Storage::disk('s3')->put($path, fopen($file->getRealPath(), 'r'), 'public');

        return Storage::disk('s3')->url($path);
    }

    private function deleteFileByUrl($url)
    {
        try {
            $parsedUrl = parse_url($url);
            $path = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '';
            
            // If the URL contains the bucket name in the path (path-style), remove it
            $bucket = config('filesystems.disks.s3.bucket');
            if (str_starts_with($path, $bucket . '/')) {
                $path = substr($path, strlen($bucket) + 1);
            }

            if ($path) {
                Storage::disk('s3')->delete($path);
            }
        } catch (\Exception $e) {
            Log::error('S3 Delete Error: ' . $e->getMessage());
        }
    }
}
