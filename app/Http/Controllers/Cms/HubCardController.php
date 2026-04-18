<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Cms\HubCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class HubCardController extends Controller
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
            Log::error('S3 Upload Error in HubCardController', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);
            throw new \Exception('Failed to upload file to S3: ' . $e->getMessage());
        }
    }

    /**
     * Get all hub cards
     */
    public function index()
    {
        try {
            $cards = HubCard::orderBy('order_index')->get();
            return response()->json([
                'success' => true,
                'data' => $cards
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch hub cards: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a hub card
     */
    public function update(Request $request, $id)
    {
        try {
            $card = HubCard::findOrFail($id);

            $validated = $request->validate([
                'label' => 'sometimes|required|string|max:255',
                'subtitle' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|nullable|string',
                'tag' => 'sometimes|required|string|max:50',
                'image' => 'sometimes|nullable', // Can be string or file
                'route' => 'sometimes|required|string|max:255',
                'order_index' => 'sometimes|integer',
            ]);

            // Handle image upload if a file is provided
            if ($request->hasFile('image_file')) {
                $validated['image'] = $this->uploadFile($request->file('image_file'), 'cms/hub-cards');
            } elseif ($request->has('image') && filter_var($request->image, FILTER_VALIDATE_URL)) {
                $validated['image'] = $request->image;
            }

            $card->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Hub card updated successfully',
                'data' => $card
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update hub card: ' . $e->getMessage()
            ], 500);
        }
    }
}
