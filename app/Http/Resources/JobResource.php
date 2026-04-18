<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'company_name' => $this->company_name,
            'logo_url' => $this->logo_url,
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
                'name' => $this->user?->name,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
