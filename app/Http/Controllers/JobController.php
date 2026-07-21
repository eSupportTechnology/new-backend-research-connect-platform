<?php

namespace App\Http\Controllers;

use App\Http\Resources\JobResource;
use App\Mail\JobApplicationMail;
use App\Mail\JobPostedMail;
use App\Models\Career;
use App\Models\JobApplication;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('location')) {
            $query->where('location', 'LIKE', "%{$request->location}%");
        }

        $jobs = $query->latest()->paginate($request->get('per_page', 10));

        return JobResource::collection($jobs);
    }

    /**
     * Distinct titles and locations across all approved jobs, for the search
     * dropdowns on the careers page. Served separately from index() so the
     * lists are complete rather than limited to the current page of results.
     */
    public function filterOptions()
    {
        $approved = Career::where('status', 'approved');

        return response()->json([
            'success' => true,
            'data'    => [
                'titles'    => (clone $approved)->whereNotNull('title')
                    ->distinct()->orderBy('title')->pluck('title')->values(),
                'locations' => (clone $approved)->whereNotNull('location')
                    ->where('location', '!=', '')
                    ->distinct()->orderBy('location')->pluck('location')->values(),
            ],
        ]);
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
            'apply_link' => 'nullable|url',
            'contact_email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // An apply link pointing back at this platform sends applicants in a
        // circle — they land on the posting they came from. Applying here is
        // what the built-in "Apply with Profile" flow is for.
        if ($request->filled('apply_link') && $this->isInternalUrl($request->apply_link)) {
            return response()->json([
                'success' => false,
                'errors'  => [
                    'apply_link' => [
                        'The application link must point to an external site. Leave it blank to receive applications here instead.',
                    ],
                ],
            ], 422);
        }

        DB::beginTransaction();

        try {
            $logoUrl = null;
            if ($request->hasFile('logo')) {
                $logoUrl = $this->uploadFile($request->file('logo'), 'careers/logos');
            }

            // If user is admin, auto-approve. Otherwise pending.
            $status = (auth()->user()->role === 'admin' || auth()->user()->role === 'super_admin' || auth()->user()->role === 'superadmin') ? 'approved' : 'pending';

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
                'contact_email' => $request->contact_email,
                'status' => $status,
                'is_featured' => $request->boolean('is_featured', false),
            ]);

            DB::commit();

            // ── Send emails ──
            try {
                $poster = auth()->user();
                // Confirmation to poster
                Mail::to($poster->email)->send(new JobPostedMail($job->load('user'), false));
                // Admin notification
                $adminEmail = env('ADMIN_EMAIL', config('mail.from.address'));
                if ($adminEmail) {
                    Mail::to($adminEmail)->send(new JobPostedMail($job, true));
                }
            } catch (\Exception $mailEx) {
                Log::warning('Job post email failed: ' . $mailEx->getMessage());
            }

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
     * AUTH: Apply to a job — sends email to the job poster.
     * POST /api/jobs/{id}/apply
     */
    public function apply(Request $request, $id)
    {
        // The profile link is optional — an applicant may prefer to send only a
        // CV — but an application with neither is not something an employer can act on.
        $validator = Validator::make($request->all(), [
            'profile_url' => 'nullable|url',
            'message'     => 'nullable|string|max:2000',
            'cv'          => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if (!$request->filled('profile_url') && !$request->hasFile('cv')) {
            return response()->json([
                'success' => false,
                'message' => 'Add a profile link or attach a CV so the employer can review your application.',
            ], 422);
        }

        try {
            $job       = Career::with('user')->findOrFail($id);
            $applicant = auth()->user();

            // Determine recipient: use contact_email if set, else poster's email
            $recipient = $job->contact_email ?: $job->user?->email;

            if (!$recipient) {
                return response()->json(['success' => false, 'message' => 'No contact email available for this job.'], 422);
            }

            // Optional CV upload — kept on disk so it can be attached to the
            // email and downloaded later from the poster's dashboard
            $storedCv = null;
            $cvPath   = null;
            if ($request->hasFile('cv')) {
                $storedCv = $request->file('cv')->store('job_applications', 'public');
                $cvPath   = storage_path('app/public/' . $storedCv);
            }

            // Record the application first — an email hiccup must not lose it
            $application = JobApplication::create([
                'career_id'       => $job->id,
                'applicant_id'    => $applicant->id,
                'applicant_name'  => trim($applicant->first_name . ' ' . $applicant->last_name),
                'applicant_email' => $applicant->email,
                'profile_url'     => $request->profile_url ?: null,
                'message'         => $request->message ?: null,
                'cv_path'         => $storedCv,
            ]);

            // Let the poster know in-app as well as by email
            UserNotification::create([
                'user_id' => $job->user_id,
                'type'    => 'job_application_received',
                'title'   => 'New Job Application',
                'message' => "{$application->applicant_name} applied for \"{$job->title}\".",
                'data'    => [
                    'career_id'      => $job->id,
                    'application_id' => $application->id,
                    'job_title'      => $job->title,
                ],
            ]);

            // Send the application to the job's contact email only
            try {
                Mail::to($recipient)->send(new JobApplicationMail(
                    job:              $job,
                    applicant:        $applicant,
                    profileUrl:       $request->profile_url ?: '',
                    applicantMessage: $request->message ?? '',
                    cvPath:           $cvPath
                ));
            } catch (\Exception $mailEx) {
                // The application is already saved and visible to the poster
                Log::error('Job application email failed: ' . $mailEx->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Your application has been sent successfully!',
                'data'    => $application,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Job not found.'], 404);
        } catch (\Exception $e) {
            Log::error('Job application email failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send application: ' . $e->getMessage()], 500);
        }
    }

    /**
     * True when the URL points back at this platform (API or frontend), which
     * would make an "apply" link lead straight back to the job posting.
     */
    private function isInternalUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '') {
            return false;
        }

        $ownHosts = collect([config('app.url'), config('app.frontend_url'), env('FRONTEND_URL')])
            ->filter()
            ->map(fn ($u) => strtolower((string) parse_url($u, PHP_URL_HOST)))
            ->filter()
            ->unique();

        return $ownHosts->contains($host);
    }

    /**
     * Poster: their own job posts, with how many applications each has drawn.
     */
    public function myPostedJobs(Request $request)
    {
        $jobs = Career::where('user_id', auth()->id())
            ->withCount([
                'applications',
                'applications as new_applications_count' => fn ($q) => $q->where('status', 'new'),
            ])
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $jobs]);
    }

    /**
     * Poster: the applications received for one of their job posts.
     */
    public function jobApplications($id)
    {
        $job = Career::where('user_id', auth()->id())->find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job post not found, or it is not yours.',
            ], 404);
        }

        $applications = JobApplication::with('applicant:id,first_name,last_name,email')
            ->where('career_id', $job->id)
            ->latest()
            ->get();

        // Opening the list counts as having seen them
        JobApplication::where('career_id', $job->id)
            ->whereNull('viewed_at')
            ->update(['viewed_at' => now()]);

        return response()->json([
            'success' => true,
            'data'    => [
                'job'          => $job,
                'applications' => $applications,
            ],
        ]);
    }

    /**
     * Poster: move an application through their own shortlist.
     */
    public function updateApplicationStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:new,reviewed,shortlisted,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $application = JobApplication::with('career')->find($id);

        if (!$application || $application->career?->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found, or it is not for one of your job posts.',
            ], 404);
        }

        $application->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Application status updated.',
            'data'    => $application->fresh(),
        ]);
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

        if ($request->filled('status')) {
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
            $userRole = auth()->user()->role;
            if ($userRole !== 'admin' && $userRole !== 'super_admin' && $userRole !== 'superadmin' && auth()->id() !== $job->user_id) {
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
        $path = $directory . '/' . $filename;

        try {
            $s3Client = new \Aws\S3\S3Client([
                'region' => config('filesystems.disks.s3.region'),
                'version' => 'latest',
                'credentials' => [
                    'key' => config('filesystems.disks.s3.key'),
                    'secret' => config('filesystems.disks.s3.secret'),
                ],
                'http' => [
                    'verify' => false,
                ]
            ]);

            $result = $s3Client->putObject([
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $path,
                'Body' => file_get_contents($file),
                'ContentType' => $file->getMimeType(),
            ]);

            return $result['ObjectURL'];

        } catch (\Exception $e) {
            Log::error('Job Logo Upload Error: ' . $e->getMessage());
            throw new \Exception('Failed to upload file to S3: ' . $e->getMessage());
        }
    }

    private function deleteFileByUrl($url)
    {
        try {
            $parsedUrl = parse_url($url);
            $path = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '';
            
            // If the URL contains the bucket name in the path, remove it
            $bucket = config('filesystems.disks.s3.bucket');
            if (str_starts_with($path, $bucket . '/')) {
                $path = substr($path, strlen($bucket) + 1);
            }

            if ($path) {
                $s3Client = new \Aws\S3\S3Client([
                    'region' => config('filesystems.disks.s3.region'),
                    'version' => 'latest',
                    'credentials' => [
                        'key' => config('filesystems.disks.s3.key'),
                        'secret' => config('filesystems.disks.s3.secret'),
                    ],
                    'http' => [
                        'verify' => false,
                    ]
                ]);

                $s3Client->deleteObject([
                    'Bucket' => $bucket,
                    'Key' => $path,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('S3 Delete Error: ' . $e->getMessage());
        }
    }
}
