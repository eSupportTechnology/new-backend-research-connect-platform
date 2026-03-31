<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InnovationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // Basic info
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,

            // View count
            'views' => $this->innovation_views_count ?? 0,

            // Video/Media info
            'video_url' => $this->video_url,
            'thumbnail' => $this->thumbnail,

            // Metadata
            'category' => $this->category,
            'tags' => $this->tags_array, // Already array format
            'is_paid' => $this->is_paid,
            'price' => $this->when($this->is_paid, $this->price),

            // Innovator info (combined from first_name, last_name)
            'innovator' => [
                'name' => $this->full_innovator_name,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
            ],

            // User profile (detailed but useful)
            'user' => [
                'id' => $this->userProfile?->id,
                'name' => $this->userProfile?->name,
                'email' => $this->userProfile?->email,
                'profile_image' => $this->userProfile?->profile_image_url,
                'bio' => $this->userProfile?->bio,
                'follower_count' => $this->userProfile?->follower_count,
            ],

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
