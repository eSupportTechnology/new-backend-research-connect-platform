<?php

namespace App\Models\Portfolio;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Profile extends Model
{
    use HasFactory;

    protected $table = 'profile';

    protected $fillable = [
        'user_id',
        'name',
        'business_name',
        'email',
        'telephone',
        'dob',
        'bio',
        'profile_image',
        'cover_image',
        'skills',
        'follower_count',
        'following_count',
        'innovation_count',
        'research_count',
        'system_level',
    ];

    protected $casts = [
        'skills' => 'array',
        'follower_count' => 'integer',
        'following_count' => 'integer',
        'innovation_count' => 'integer',
        'research_count' => 'integer',
        'system_level' => 'integer',
    ];

    /* ================= Relationships ================= */
    protected $appends = [
        'profile_image_url',
        'cover_image_url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function experiences()
    {
        return $this->hasMany(Experience::class, 'user_id', 'user_id');
    }

    public function educations()
    {
        return $this->hasMany(Education::class, 'user_id', 'user_id');
    }

    /* ================= Accessors ================= */

    public function getProfileImageUrlAttribute(): ?string
    {
        if (!$this->profile_image) return null;

        $cleaned = trim($this->profile_image);
        if (strlen($cleaned) === 0) return null;

        try {
            // Use S3 for profile images
            return Storage::disk('s3')->url($cleaned);
        } catch (\Exception $e) {
            \Log::warning('S3 URL failed for profile_image', [
                'path' => $cleaned,
                'error' => $e->getMessage(),
            ]);

            // Fallback to local storage if S3 fails
            return asset('storage/' . $cleaned);
        }
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (!$this->cover_image) return null;

        $cleaned = trim($this->cover_image);
        if (strlen($cleaned) === 0) return null;

        try {
            // Use S3 for cover images
            return Storage::disk('s3')->url($cleaned);
        } catch (\Exception $e) {
            \Log::warning('S3 URL failed for cover_image', [
                'path' => $cleaned,
                'error' => $e->getMessage(),
            ]);

            // Fallback to local storage if S3 fails
            return asset('storage/' . $cleaned);
        }
    }
}
