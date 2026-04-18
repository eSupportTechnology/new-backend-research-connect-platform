<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityLike;
use App\Models\Community\CommunityComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    /**
     * Helper to upload file to S3
     */
    private function uploadFile($file, $directory)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
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
            Log::error('S3 Upload Error in CommunityController', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);
            throw new \Exception('Failed to upload file to S3: ' . $e->getMessage());
        }
    }

    /**
     * Get all community posts
     */
    public function index()
    {
        try {
            $posts = CommunityPost::with(['user.profile', 'userLiked'])
                ->withCount(['likes', 'comments'])
                ->where('status', 'active')
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $posts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch posts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new community post
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:research,discussion',
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'full_content' => 'nullable|string',
                'category' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'tags' => 'nullable|string', // Expecting JSON string or comma-separated
                'is_recruiting' => 'boolean',
                'image_file' => 'nullable|image|max:10240', // 10MB
            ]);

            $data = $validated;
            $data['user_id'] = auth()->id();
            
            // Handle tags
            if ($request->has('tags')) {
                $tags = is_string($request->tags) ? explode(',', $request->tags) : $request->tags;
                $data['tags'] = array_map('trim', $tags);
            }

            // Handle image upload
            if ($request->hasFile('image_file')) {
                $data['image_url'] = $this->uploadFile($request->file('image_file'), 'community/posts');
            }

            $post = CommunityPost::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'data' => $post->load('user.profile')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create post: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific post with comments
     */
    public function show($id)
    {
        try {
            $post = CommunityPost::with(['user.profile', 'comments.user.profile', 'userLiked'])
                ->withCount(['likes', 'comments'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $post
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }
    }

    /**
     * Toggle like on a post
     */
    public function toggleLike($id)
    {
        try {
            $userId = auth()->id();
            $like = CommunityLike::where('post_id', $id)->where('user_id', $userId)->first();

            if ($like) {
                $like->delete();
                $liked = false;
            } else {
                CommunityLike::create([
                    'post_id' => $id,
                    'user_id' => $userId
                ]);
                $liked = true;
            }

            return response()->json([
                'success' => true,
                'liked' => $liked,
                'likes_count' => CommunityLike::where('post_id', $id)->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a comment
     */
    public function storeComment(Request $request, $id)
    {
        try {
            $request->validate([
                'content' => 'required|string|max:1000',
            ]);

            $comment = CommunityComment::create([
                'post_id' => $id,
                'user_id' => auth()->id(),
                'content' => $request->content
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => $comment->load('user.profile')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment: ' . $e->getMessage()
            ], 500);
        }
    }
}
