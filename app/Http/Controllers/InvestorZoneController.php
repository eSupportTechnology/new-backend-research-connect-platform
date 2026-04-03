<?php



namespace App\Http\Controllers;

use App\Models\InvestorZone\InvestorZonePost;
use App\Models\InvestorZone\PostLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Aws\S3\S3Client;

class InvestorZoneController extends Controller
{
    // ─────────────────────────────────────────────
    // PUBLIC: List posts
    // GET /api/investorzone/{type}/{category}
    // ─────────────────────────────────────────────
    public function index(Request $request, string $type, string $category)
    {
        $perPage = $request->get('per_page', 9);

        $posts = InvestorZonePost::with(['user', 'user.profile'])  // ← add user.profile
        ->where('type', $type)
            ->where('category', $category)
            ->latest()
            ->paginate($perPage);

        $userId = Auth::id();

        $transformed = $posts->getCollection()->map(
            fn($post) => $this->transformPost($post, $userId, false)
        );

        return response()->json([
            'projects' => $transformed,
            'totalPages' => $posts->lastPage(),
            'currentPage' => $posts->currentPage(),
            'total' => $posts->total(),
        ]);
    }

// ─────────────────────────────────────────────
// PUBLIC: Single post detail
// GET /api/investorzone/{type}/{category}/project/{id}
// ─────────────────────────────────────────────
    public function show(string $type, string $category, int $id)
    {
        try {
            $post = InvestorZonePost::with(['user', 'user.profile', 'likes'])  // ← add user.profile
            ->findOrFail($id);

            return response()->json([
                'success' => true,
                'project' => $this->transformPost($post, Auth::id(), true),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found',
            ], 404);
        }
    }
    // ─────────────────────────────────────────────
    // AUTH: Create post
    // POST /api/investorzone/posts
    // ─────────────────────────────────────────────
    public function store(Request $request)
    {
        Log::info('InvestorZone Post Store Started', [
            'has_media' => $request->hasFile('media'),
            'user_id' => Auth::id(),
            'type' => $request->type,
            'category' => $request->category,
        ]);

        $validator = Validator::make($request->all(), [
            'media' => 'required|file|mimes:png,jpg,jpeg,mp4|max:10240',
            'title' => 'required|string|max:100',
            'description' => 'required|string|min:20|max:1000',
            'keyHighlights' => 'required|string|min:20|max:1000',
            'type' => 'required|string',
            'category' => 'required|string',
            'email' => 'nullable|email',
            'mobile' => 'nullable|string|max:20',
            'linkedin' => 'nullable|string|max:255',
            'allowLikes' => 'nullable',
            'allowContactSharing' => 'nullable',
            'requestCollaboration' => 'nullable',
            'requireApproval' => 'nullable',
            'notifyEngagement' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $uploadedFiles = [];

            // ── Upload media file to S3 ──
            if ($request->hasFile('media')) {
                $file = $request->file('media');
                Log::info('Uploading investor zone media', [
                    'filename' => $file->getClientOriginalName(),
                ]);

                // Images → investorzone/images, Videos → investorzone/videos
                $isVideo = in_array(
                    strtolower($file->getClientOriginalExtension()),
                    ['mp4']
                );
                $folder = $isVideo ? 'investorzone/videos' : 'investorzone/images';

                $mediaUrl = $this->uploadFileToS3($file, $folder);
                $uploadedFiles['media'] = $mediaUrl;

                Log::info('InvestorZone media uploaded', ['url' => $mediaUrl]);
            }

            // ── Determine status ──
            $requireApproval = filter_var(
                $request->requireApproval,
                FILTER_VALIDATE_BOOLEAN
            );
            $status = $requireApproval ? 'pending' : 'approved';

            // ── Create DB record ──
            $post = InvestorZonePost::create([
                'user_id' => Auth::id(),
                'type' => $request->type,
                'category' => $request->category,
                'title' => $request->title,
                'description' => $request->description,
                'key_highlights' => $request->keyHighlights,
                'media_path' => $uploadedFiles['media'] ?? null,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'linkedin' => $request->linkedin,
                'allow_likes' => filter_var($request->allowLikes, FILTER_VALIDATE_BOOLEAN),
                'allow_contact_sharing' => filter_var($request->allowContactSharing, FILTER_VALIDATE_BOOLEAN),
                'request_collaboration' => filter_var($request->requestCollaboration, FILTER_VALIDATE_BOOLEAN),
                'require_approval' => $requireApproval,
                'notify_engagement' => filter_var($request->notifyEngagement, FILTER_VALIDATE_BOOLEAN),
                'status' => $status,
            ]);

            DB::commit();

            Log::info('InvestorZone post created', ['post_id' => $post->id]);

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'data' => [
                    'post' => $this->transformPost($post->load('user'), Auth::id(), false),
                    'files' => $uploadedFiles,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('InvestorZone post creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // ── Clean up S3 on failure ──
            if (isset($uploadedFiles['media'])) {
                $this->deleteFileByUrl($uploadedFiles['media']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Post creation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function related(Request $request, string $type, string $category)
    {
        $excludeId = $request->get('exclude');

        $posts = InvestorZonePost::with(['user', 'user.profile'])  // ← add user.profile
        ->where('type', $type)
            ->where('category', $category)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->latest()
            ->limit(3)
            ->get();

        return response()->json([
            'success' => true,
            'projects' => $posts->map(
                fn($p) => $this->transformPost($p, Auth::id(), false)
            ),
        ]);
    }

    // ─────────────────────────────────────────────
    // AUTH: Toggle like
    // POST /api/investorzone/project/{id}/like
    // ─────────────────────────────────────────────
    public function toggleLike(int $id)
    {
        try {
            $post = InvestorZonePost::findOrFail($id);

            if (!$post->allow_likes) {
                return response()->json([
                    'success' => false,
                    'message' => 'Likes are disabled for this post',
                ], 403);
            }

            $userId = Auth::id();
            $existing = PostLike::where('post_id', $id)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                $existing->delete();
                $liked = false;
            } else {
                PostLike::create(['post_id' => $id, 'user_id' => $userId]);
                $liked = true;
            }

            return response()->json([
                'success' => true,
                'liked' => $liked,
                'like_count' => $post->likes()->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Like action failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────
    // AUTH: Get presigned URL (for secure media access)
    // POST /api/investorzone/presigned-url
    // ─────────────────────────────────────────────
    public function getPresignedUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_url' => 'required|string',
            'expiration' => 'nullable|integer|min:1|max:10080',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $expiration = $request->expiration ?? 60;
            $presignedUrl = $this->generatePresignedUrl(
                $request->file_url,
                $expiration
            );

            return response()->json([
                'success' => true,
                'url' => $presignedUrl,
                'expires_in' => $expiration . ' minutes',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate URL: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ═══════════════════════════════════════════════
    // PRIVATE S3 HELPERS  (same pattern as UploadController)
    // ═══════════════════════════════════════════════

    private function getS3Client(): S3Client
    {
        return new S3Client([
            'region' => config('filesystems.disks.s3.region'),
            'version' => 'latest',
            'credentials' => [
                'key' => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],
            'http' => [
                'verify' => false, // Disable SSL for dev (enable in production)
            ],
        ]);
    }

    private function uploadFileToS3($file, string $directory): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = $directory . '/' . $filename;

        try {
            $s3 = $this->getS3Client();

            $result = $s3->putObject([
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $path,
                'Body' => file_get_contents($file),
                'ContentType' => $file->getMimeType(),
            ]);

            Log::info('S3 upload success', [
                'path' => $path,
                'url' => $result['ObjectURL'],
            ]);

            return $result['ObjectURL'];

        } catch (\Exception $e) {
            Log::error('S3 upload failed', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);
            throw new \Exception('S3 upload failed: ' . $e->getMessage());
        }
    }

    private function deleteFileByUrl(string $url): void
    {
        try {
            $path = ltrim(parse_url($url, PHP_URL_PATH), '/');

            if ($path) {
                $this->getS3Client()->deleteObject([
                    'Bucket' => config('filesystems.disks.s3.bucket'),
                    'Key' => $path,
                ]);
                Log::info('S3 file deleted', ['path' => $path]);
            }
        } catch (\Exception $e) {
            Log::error('S3 delete failed', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
        }
    }

    private function generatePresignedUrl(string $url, int $minutes = 60): string
    {
        try {
            $path = ltrim(parse_url($url, PHP_URL_PATH), '/');

            if (!$path) return $url;

            $s3 = $this->getS3Client();
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $path,
            ]);

            $presigned = $s3->createPresignedRequest($cmd, "+{$minutes} minutes");

            return (string)$presigned->getUri();

        } catch (\Exception $e) {
            Log::error('Presigned URL generation failed', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            return $url; // Fallback to original URL
        }
    }

    // ═══════════════════════════════════════════════
    // PRIVATE: Response transformer
    // ═══════════════════════════════════════════════

    private function transformPost(
        InvestorZonePost $post,
        ?string          $userId,
        bool             $fullDetail
    ): array
    {
        // ── Resolve author info from user + profile ──
        $user = $post->user;
        $profile = $user?->profile;  // Profile model via user.profile

        $authorName = $profile?->name ?? $user?->name ?? 'Unknown';
        $authorEmail = $profile?->email ?? $user?->email ?? null;
        $authorAvatar = $profile?->profile_image_url        // uses S3 accessor
            ?? 'https://i.pravatar.cc/150?img=1';

        $base = [
            'id' => $post->id,
            'title' => $post->title,
            'description' => $post->description,
            'image' => $post->media_path,
            'categoryBadge' => ucwords(str_replace('-', ' ', $post->category)),
            'type' => $post->type,
            'category' => $post->category,
            'createdAt' => $post->created_at->toDateString(),
            'author' => [
                'name' => $authorName,
                'email' => $authorEmail,
                'avatar' => $authorAvatar,
                'bio' => $profile?->bio ?? null,
                'telephone' => $profile?->telephone ?? null,
                'skills' => $profile?->skills ?? [],
                'user_id' => $user?->id ?? null,
            ],
            'likes' => $post->likes()->count(),
            'isLiked' => $userId ? $post->isLikedByUser($userId) : false,
        ];

        if (!$fullDetail) {
            return $base;
        }

        // ── Extra fields for detail page ──
        return array_merge($base, [
            'keyHighlights' => array_values(array_filter(
                explode("\n", $post->key_highlights),
                fn($line) => trim($line) !== ''
            )),
            'contact' => $post->allow_contact_sharing ? [
                'phone' => $post->mobile,
                'email' => $post->email,
                'linkedin' => $post->linkedin,
            ] : null,
            'allowLikes' => $post->allow_likes,
            'allowContactSharing' => $post->allow_contact_sharing,
            'requestCollaboration' => $post->request_collaboration,
            // ── Full author profile for detail page ──
            'authorProfile' => [
                'name' => $authorName,
                'avatar' => $authorAvatar,
                'cover_image' => $profile?->cover_image_url ?? null,
                'bio' => $profile?->bio ?? null,
                'skills' => $profile?->skills ?? [],
                'telephone' => $profile?->telephone ?? null,
                'innovation_count' => $profile?->innovation_count ?? 0,
                'research_count' => $profile?->research_count ?? 0,
                'follower_count' => $profile?->follower_count ?? 0,
                'system_level' => $profile?->system_level ?? 0,
                'user_id' => $user?->id ?? null,
            ],
        ]);
    }
}
