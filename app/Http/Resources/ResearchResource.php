<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResearchResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // Basic info
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->abstract, // Map abstract to description for consistency

            // View count & Downloads
            'views' => $this->views,
            'downloads' => $this->downloads,

            // Rating stats
            'average_rating' => $this->average_rating,
            'total_ratings' => $this->total_ratings,
            'user_rating' => $this->user_rating,

            // Document/Media info
            'document_url' => $this->document_url,
            'thumbnail' => $this->thumbnail,

            // Metadata
            'category' => $this->category,
            'tags' => $this->tags_array, // Already array format
            'is_paid' => $this->is_paid,
            'price' => $this->when($this->is_paid, $this->price),
            'status' => $this->status,

            // Author info (mapped as innovator for consistency if needed, or structured similarly)
            'innovator' => [
                'name' => $this->full_author_name,
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
