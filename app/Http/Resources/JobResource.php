<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    public function toArray($request)
    {
        $logoUrl = $this->logo_url;

        // Generate presigned URL for S3 logos to bypass 403 Forbidden errors
        if ($logoUrl && str_contains($logoUrl, 'amazonaws.com')) {
            try {
                $parsedUrl = parse_url($logoUrl);
                $path = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '';
                
                $bucket = config('filesystems.disks.s3.bucket');
                // Remove bucket name from path if virtual host style (some S3 setups)
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

                    $cmd = $s3Client->getCommand('GetObject', [
                        'Bucket' => $bucket,
                        'Key' => $path
                    ]);

                    $presignedRequest = $s3Client->createPresignedRequest($cmd, '+60 minutes');
                    $logoUrl = (string) $presignedRequest->getUri();
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Logo Presigned URL Error: ' . $e->getMessage());
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'company_name' => $this->company_name,
            'logo_url' => $logoUrl,
            'category' => $this->category,
            'location' => $this->location,
            'job_type' => $this->job_type,
            'salary_range' => $this->salary_range,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'apply_link' => $this->apply_link,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->full_name,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
