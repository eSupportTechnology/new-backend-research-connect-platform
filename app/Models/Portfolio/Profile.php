<?php

namespace App\Models\Portfolio;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $table = 'profile';
    protected $fillable = [
        'user_id',
        'name',
        'title',
        'headline',
        'bio',
        'phone',
        'profile_image',
        'cover_image',
        'skills',
        'follower_count',
        'following_count',
        'innovation_count',
        'research_count',
        'system_level',
        'website',
        'location',
        'github_url',
        'linkedin_url',
        'twitter_url',
        'facebook_url'
    ];

    protected $casts = [
        'skills' => 'array',
        'follower_count' => 'integer',
        'following_count' => 'integer',
        'innovation_count' => 'integer',
        'research_count' => 'integer',
        'system_level' => 'integer',
    ];

    // Relationships
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

    // Helper methods
    public function getProfileImageUrlAttribute()
    {
        return $this->profile_image ? asset('storage/' . $this->profile_image) : null;
    }

    public function getCoverImageUrlAttribute()
    {
        return $this->cover_image ? asset('storage/' . $this->cover_image) : null;
    }

    // Increment counters
    public function incrementFollowerCount()
    {
        $this->increment('follower_count');
    }

    public function decrementFollowerCount()
    {
        $this->decrement('follower_count');
    }

    public function incrementInnovationCount()
    {
        $this->increment('innovation_count');
    }

    public function incrementResearchCount()
    {
        $this->increment('research_count');
    }
}
