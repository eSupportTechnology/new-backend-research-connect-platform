<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class S3Controller extends Controller
{
    /**
     * Generate pre-signed URL for S3 upload
     */
    public function getPresignedUrl(Request $request)
    {
        // Log incoming request for debugging
        Log::info('Pre-signed URL request received', [
            'fileName' => $request->fileName,
            'fileType' => $request->fileType,
            'uploadType' => $request->uploadType
        ]);

        // Validate request
        $validator = \Validator::make($request->all(), [
            'fileName' => 'required|string',
            'fileType' => 'required|string',
            'uploadType' => 'required|in:video,thumbnail,document',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $fileName = $request->fileName;
            $fileType = $request->fileType;
            $uploadType = $request->uploadType;

            // Generate unique filename to prevent overwrites
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $uniqueFileName = Str::uuid() . '_' . time() . '.' . $extension;

            // Determine folder based on upload type
            $folder = match($uploadType) {
                'video' => 'videos',
                'thumbnail' => 'thumbnails',
                'document' => 'documents',
                default => 'uploads'
            };

            $key = "{$folder}/{$uniqueFileName}";

            Log::info('Generating pre-signed URL', [
                'key' => $key,
                'bucket' => env('AWS_BUCKET'),
                'region' => env('AWS_DEFAULT_REGION')
            ]);

            // Create S3 client
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => env('AWS_DEFAULT_REGION'),
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);

            // Create a command to put an object
            $cmd = $s3Client->getCommand('PutObject', [
                'Bucket' => env('AWS_BUCKET'),
                'Key' => $key,
                'ContentType' => $fileType,
                'ACL' => 'public-read', // Make file publicly readable
            ]);

            // Create the pre-signed request (valid for 60 minutes)
            $presignedRequest = $s3Client->createPresignedRequest($cmd, '+60 minutes');

            // Get the actual pre-signed URL
            $uploadUrl = (string) $presignedRequest->getUri();

            // Generate the permanent file URL
            $fileUrl = "https://" . env('AWS_BUCKET') . ".s3." . env('AWS_DEFAULT_REGION') . ".amazonaws.com/{$key}";

            Log::info('Pre-signed URL generated successfully', [
                'fileUrl' => $fileUrl
            ]);

            return response()->json([
                'uploadUrl' => $uploadUrl,
                'fileUrl' => $fileUrl,
                'key' => $key,
            ], 200);

        } catch (AwsException $e) {
            Log::error('AWS Error', [
                'message' => $e->getMessage(),
                'code' => $e->getAwsErrorCode(),
                'type' => $e->getAwsErrorType()
            ]);

            return response()->json([
                'error' => 'AWS Error',
                'message' => $e->getMessage(),
                'code' => $e->getAwsErrorCode()
            ], 500);

        } catch (\Exception $e) {
            Log::error('Server Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Server Error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alternative: Direct upload to S3 through Laravel (if pre-signed URLs don't work)
     */
    public function directUpload(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'file' => 'required|file|max:524288', // 500MB max
            'uploadType' => 'required|in:video,thumbnail,document',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $uploadType = $request->uploadType;

            // Determine folder
            $folder = match($uploadType) {
                'video' => 'videos',
                'thumbnail' => 'thumbnails',
                'document' => 'documents',
                default => 'uploads'
            };

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $uniqueFileName = Str::uuid() . '_' . time() . '.' . $extension;
            $path = "{$folder}/{$uniqueFileName}";

            // Upload to S3
            Storage::disk('s3')->put(
                $path,
                file_get_contents($file),
                'public'
            );

            // Get the URL
            $fileUrl = Storage::disk('s3')->url($path);

            Log::info('File uploaded directly to S3', ['url' => $fileUrl]);

            return response()->json([
                'fileUrl' => $fileUrl,
                'key' => $path,
                'message' => 'File uploaded successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Direct upload error', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Upload failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete file from S3
     */
    public function deleteFile(Request $request)
    {
        $request->validate([
            'fileUrl' => 'required|string',
        ]);

        try {
            // Extract key from URL
            $fileUrl = $request->fileUrl;
            $parsedUrl = parse_url($fileUrl);
            $key = ltrim($parsedUrl['path'], '/');

            Storage::disk('s3')->delete($key);

            Log::info('File deleted from S3', ['key' => $key]);

            return response()->json([
                'message' => 'File deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Delete error', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to delete file',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test S3 connection
     */
    public function testConnection()
    {
        try {
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => env('AWS_DEFAULT_REGION'),
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);

            // Try to list buckets
            $result = $s3Client->listBuckets();

            return response()->json([
                'status' => 'success',
                'message' => 'S3 connection successful',
                'bucket' => env('AWS_BUCKET'),
                'region' => env('AWS_DEFAULT_REGION')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
