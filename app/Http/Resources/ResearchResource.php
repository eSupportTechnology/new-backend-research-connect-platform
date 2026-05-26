<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResearchResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // Basic info
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->abstract,
            'abstract'    => $this->abstract,

            // Classification
            'category'       => $this->category,
            'sub_category'   => $this->sub_category,
            'research_type'  => $this->research_type,
            'research_level' => $this->research_level,

            // Content flags
            'is_adult' => $this->is_adult,
            'is_paid'  => $this->is_paid,
            'price'    => $this->when($this->is_paid, $this->price),
            'status'   => $this->status,

            // Tags
            'tags'       => $this->tags_array,
            'tags_array' => $this->tags_array,

            // Document/Media
            'document_url' => $this->document_url,
            'thumbnail'    => $this->thumbnail,

            // Stats
            'views'     => $this->views,
            'downloads' => $this->downloads,

            // Ratings
            'average_rating' => $this->average_rating,
            'total_ratings'  => $this->total_ratings,
            'user_rating'    => $this->user_rating,

            // Likes
            'likes_count'       => $this->likes_count,
            'dislikes_count'    => $this->dislikes_count,
            'user_has_liked'    => $this->user_has_liked,
            'user_has_disliked' => $this->user_has_disliked,

            // Author info
            'first_name'       => $this->first_name,
            'last_name'        => $this->last_name,
            'full_author_name' => $this->full_author_name,
            'extra_people'     => $this->extra_people,
            'innovator' => [
                'name'       => $this->full_author_name,
                'first_name' => $this->first_name,
                'last_name'  => $this->last_name,
            ],

            // User profile
            'user_id' => $this->user_id,
            'user' => [
                'id'            => $this->userProfile?->id,
                'name'          => $this->userProfile?->name,
                'email'         => $this->userProfile?->email,
                'profile_image' => $this->userProfile?->profile_image_url,
                'bio'           => $this->userProfile?->bio,
                'follower_count'=> $this->userProfile?->follower_count,
            ],
            'user_profile' => [
                'name'              => $this->userProfile?->name,
                'profile_image_url' => $this->userProfile?->profile_image_url,
            ],

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
