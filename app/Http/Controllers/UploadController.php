<?php

namespace App\Http\Controllers;

use App\Http\Resources\InnovationResource;
use App\Models\Innovation\InnovationViews;
use App\Models\Research\Research;
use App\Models\Innovation\Innovation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    /**
     * Upload Research Paper
     */
    public function uploadResearch(Request $request)
    {
        Log::info('Upload Research Started', [
            'has_document' => $request->hasFile('document'),
            'has_thumbnail' => $request->hasFile('thumbnail'),
            'user_id' => auth()->id()
        ]);

        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:pdf|max:51200', // 50MB max
            'title' => 'required|string|max:255',
            'abstract' => 'required|string|max:500',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', // 5MB max
            'category' => 'required|string|max:100',
            'firstName' => 'required|string|max:100',
            'lastName' => 'required|string|max:100',
            'tags' => 'nullable|string',
            'price' => 'required|in:yes,no',
            'priceAmount' => 'required_if:price,yes|nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $uploadedFiles = [];

            // Upload PDF Document
            if ($request->hasFile('document')) {
                $document = $request->file('document');
                Log::info('Uploading document', ['filename' => $document->getClientOriginalName()]);

                $documentPath = $this->uploadFile(
                    $document,
                    'research/documents'
                );
                $uploadedFiles['document'] = $documentPath;
                Log::info('Document uploaded successfully', ['url' => $documentPath]);
            }

            // Upload Thumbnail
            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail');
                Log::info('Uploading thumbnail', ['filename' => $thumbnail->getClientOriginalName()]);

                $thumbnailPath = $this->uploadFile(
                    $thumbnail,
                    'research/thumbnails'
                );
                $uploadedFiles['thumbnail'] = $thumbnailPath;
                Log::info('Thumbnail uploaded successfully', ['url' => $thumbnailPath]);
            }

            // Determine price details
            $isPaid = $request->price === 'yes';
            $priceAmount = $isPaid ? $request->priceAmount : null;

            // Create research record
            $research = Research::create([
                'user_id' => auth()->id() ,
                'document_url' => $uploadedFiles['document'],
                'thumbnail' => $uploadedFiles['thumbnail'] ?? null,
                'title' => $request->title,
                'abstract' => $request->abstract,
                'category' => $request->category,
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'tags' => $request->tags,
                'is_paid' => $isPaid,
                'price' => $priceAmount,
                'status' => 'pending',
                'views' => 0,
                'downloads' => 0,
            ]);

            DB::commit();

            Log::info('Research created successfully', ['research_id' => $research->id]);

            return response()->json([
                'success' => true,
                'message' => 'Research uploaded successfully',
                'data' => [
                    'research' => $research,
                    'files' => $uploadedFiles,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Research upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Delete uploaded files on error
            if (isset($uploadedFiles['document'])) {
                $this->deleteFileByUrl($uploadedFiles['document']);
            }
            if (isset($uploadedFiles['thumbnail'])) {
                $this->deleteFileByUrl($uploadedFiles['thumbnail']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload Innovation Video
     */
    public function uploadInnovation(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:mp4,avi,mov,webm,mkv|max:512000', // 500MB max
            'title' => 'required|string|max:255',
            'abstract' => 'required|string|max:500',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'category' => 'required|string',
            'firstName' => 'required|string|max:100',
            'lastName' => 'required|string|max:100',
            'tags' => 'nullable|string',
            'price' => 'required|in:yes,no',
            'priceAmount' => 'required_if:price,yes|nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $uploadedFiles = [];

            // Upload Video
            if ($request->hasFile('document')) {
                $video = $request->file('document');
                Log::info('Uploading video', ['filename' => $video->getClientOriginalName()]);

                $videoPath = $this->uploadFile(
                    $video,
                    'innovation/videos'
                );
                $uploadedFiles['video'] = $videoPath;
                Log::info('Video uploaded successfully', ['url' => $videoPath]);
            }

            // Upload Thumbnail
            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail');
                Log::info('Uploading thumbnail', ['filename' => $thumbnail->getClientOriginalName()]);

                $thumbnailPath = $this->uploadFile(
                    $thumbnail,
                    'innovation/thumbnails'
                );
                $uploadedFiles['thumbnail'] = $thumbnailPath;
                Log::info('Thumbnail uploaded successfully', ['url' => $thumbnailPath]);
            }

            // Determine price details
            $isPaid = $request->price === 'yes';
            $priceAmount = $isPaid ? $request->priceAmount : null;

            // Create innovation record
            $innovation = Innovation::create([
                'user_id' => auth()->id() ,
                'video_url' => $uploadedFiles['video'],
                'thumbnail' => $uploadedFiles['thumbnail'] ?? null,
                'title' => $request->title,
                'description' => $request->abstract,
                'category' => $request->category,
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'tags' => $request->tags,
                'is_paid' => $isPaid,
                'price' => $priceAmount,
            ]);

            DB::commit();

            Log::info('Innovation created successfully', ['innovation_id' => $innovation->id]);

            return response()->json([
                'success' => true,
                'message' => 'Innovation uploaded successfully',
                'data' => [
                    'innovation' => $innovation,
                    'files' => $uploadedFiles,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Innovation upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Delete uploaded files on error
            if (isset($uploadedFiles['video'])) {
                $this->deleteFileByUrl($uploadedFiles['video']);
            }
            if (isset($uploadedFiles['thumbnail'])) {
                $this->deleteFileByUrl($uploadedFiles['thumbnail']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all research papers with filters
     */
    public function getResearches(Request $request)
    {
        $query = Research::with('userProfile');

        // Apply filters
        if ($request->has('category')) {
            $query->category($request->category);
        }

        // ❌ REMOVE approved-only logic
        // if ($request->has('status')) {
        //     $query->where('status', $request->status);
        // } else {
        //     $query->approved();
        // }

        // ✅ OPTIONAL: if status is provided, filter it
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('free')) {
            $query->free();
        }

        if ($request->has('paid')) {
            $query->paid();
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'latest');
        switch ($sortBy) {
            case 'popular':
                $query->popular();
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $researches = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $researches
        ]);
    }

    /**
     * Get all innovations with filters
     */
    public function getInnovations(Request $request)
    {
        $query = Innovation::with('userProfile'); // ✅ ADD THIS

        // Apply filters
        if ($request->has('category')) {
            $query->category($request->category);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('free')) {
            $query->free();
        }

        if ($request->has('paid')) {
            $query->paid();
        }

        // Sorting
        $query->latest();

        // Pagination
        $perPage = $request->get('per_page', 15);
        $innovations = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $innovations
        ]);
    }
    public function getTopViewedInnovations(Request $request)
    {
        try {
            $topInnovations = Innovation::with('userProfile')
                ->withCount('innovationViews')
                ->orderBy('innovation_views_count', 'desc')
                ->take(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => InnovationResource::collection($topInnovations)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch top innovations: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get single research with details
     */
    public function getResearchDetails($id)
    {
        try {
            $research = Research::with('userProfile')->findOrFail($id);

            // Optional: Increment views (same as getResearch)
            $research->incrementViews();

            return response()->json([
                'success' => true,
                'data' => $research
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Research not found'
            ], 404);
        }
    }

    /**
     * Get single innovation with details
     */
    public function getInnovationDetails($id)
    {
        try {
            $innovation = Innovation::with('userProfile')->findOrFail($id);

            // Only create view if user hasn't viewed this innovation before
            $existingView = InnovationViews::where('innovation_id', $innovation->id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$existingView) {
                InnovationViews::create([
                    'innovation_id' => $innovation->id,
                    'user_id' => auth()->id(),
                    'ip_address' => request()->ip(),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $innovation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Innovation not found'
            ], 404);
        }
    }

    /**
     * Get single research
     */
    public function getResearch($id)
    {
        try {
            $research = Research::findOrFail($id);

            // Increment views
            $research->incrementViews();

            return response()->json([
                'success' => true,
                'data' => $research
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Research not found'
            ], 404);
        }
    }

    /**
     * Get single innovation
     */
    public function getInnovation($id)
    {
        try {
            $innovation = Innovation::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $innovation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Innovation not found'
            ], 404);
        }
    }

    /**
     * Download research document
     */
    public function downloadResearch($id)
    {
        try {
            $research = Research::findOrFail($id);

            // Increment downloads
            $research->incrementDownloads();

            // Generate temporary URL for download
            $url = $this->getPresignedUrlForFile($research->document_url, 30);

            return response()->json([
                'success' => true,
                'download_url' => $url,
                'expires_in' => '30 minutes'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Research not found'
            ], 404);
        }
    }

    /**
     * Update research status (admin only)
     */
    public function updateResearchStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $research = Research::findOrFail($id);
            $research->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Research status updated successfully',
                'data' => $research
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Research not found'
            ], 404);
        }
    }

    /**
     * Helper function to upload file to S3
     */
    private function uploadFile($file, $directory)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = $directory . '/' . $filename;

        try {
            // Use AWS SDK directly to bypass Flysystem SSL issues
            $s3Client = new \Aws\S3\S3Client([
                'region' => config('filesystems.disks.s3.region'),
                'version' => 'latest',
                'credentials' => [
                    'key' => config('filesystems.disks.s3.key'),
                    'secret' => config('filesystems.disks.s3.secret'),
                ],
                'http' => [
                    'verify' => false, // Disable SSL verification for development
                ]
            ]);

            $result = $s3Client->putObject([
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $path,
                'Body' => file_get_contents($file),
                'ContentType' => $file->getMimeType(),
            ]);

            Log::info('File uploaded to S3', [
                'path' => $path,
                'url' => $result['ObjectURL']
            ]);

            // Return the public URL
            return $result['ObjectURL'];

        } catch (\Exception $e) {
            Log::error('S3 Upload Error', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);
            throw new \Exception('Failed to upload file to S3: ' . $e->getMessage());
        }
    }

    /**
     * Helper function to delete file by URL
     */
    private function deleteFileByUrl($url)
    {
        try {
            // Extract path from URL
            $parsedUrl = parse_url($url);
            $path = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '';

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
                    'Bucket' => config('filesystems.disks.s3.bucket'),
                    'Key' => $path,
                ]);

                Log::info('File deleted from S3', ['path' => $path]);
            }
        } catch (\Exception $e) {
            // Log error but don't throw
            Log::error('Failed to delete file', [
                'error' => $e->getMessage(),
                'url' => $url
            ]);
        }
    }

    /**
     * Get presigned URL for file
     */
    private function getPresignedUrlForFile($url, $expirationMinutes = 60)
    {
        try {
            // Extract path from URL
            $parsedUrl = parse_url($url);
            $path = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '';

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

                $cmd = $s3Client->getCommand('GetObject', [
                    'Bucket' => config('filesystems.disks.s3.bucket'),
                    'Key' => $path
                ]);

                $request = $s3Client->createPresignedRequest($cmd, '+' . $expirationMinutes . ' minutes');

                return (string) $request->getUri();
            }

            return $url;
        } catch (\Exception $e) {
            Log::error('Failed to generate presigned URL', [
                'error' => $e->getMessage(),
                'url' => $url
            ]);
            return $url; // Return original URL if presigned fails
        }
    }

    /**
     * Get presigned URL for secure file access (API endpoint)
     */
    public function getPresignedUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'expiration' => 'nullable|integer|min:1|max:10080' // Max 1 week
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePath = $request->file_path;
            $expiration = $request->expiration ?? 60; // Default 60 minutes

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

            $cmd = $s3Client->getCommand('GetObject', [
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $filePath
            ]);

            $request = $s3Client->createPresignedRequest($cmd, '+' . $expiration . ' minutes');
            $presignedUrl = (string) $request->getUri();

            return response()->json([
                'success' => true,
                'url' => $presignedUrl,
                'expires_in' => $expiration . ' minutes'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete file from S3 (API endpoint)
     */
    public function deleteFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePath = $request->file_path;

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

            // Check if file exists
            $exists = $s3Client->doesObjectExist(
                config('filesystems.disks.s3.bucket'),
                $filePath
            );

            if ($exists) {
                $s3Client->deleteObject([
                    'Bucket' => config('filesystems.disks.s3.bucket'),
                    'Key' => $filePath,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
