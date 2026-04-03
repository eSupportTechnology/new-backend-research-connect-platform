<?php

namespace App\Models\InvestorZone;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestorZonePost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type', 'category', 'title', 'description',
        'key_highlights', 'media_path', 'email', 'mobile', 'linkedin',
        'allow_likes', 'allow_contact_sharing', 'request_collaboration',
        'require_approval', 'notify_engagement', 'status',
    ];

    protected $casts = [
        'allow_likes' => 'boolean',
        'allow_contact_sharing' => 'boolean',
        'request_collaboration' => 'boolean',
        'require_approval' => 'boolean',
        'notify_engagement' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class, 'post_id');
    }

    public function getLikesCountAttribute()
    {
        return $this->likes()->count();
    }

    public function isLikedByUser(string $userId): bool  // ← was ?int, now string
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    public function getMediaUrlAttribute()
    {
        return $this->media_path
            ? asset('storage/' . $this->media_path)
            : null;
    }
    public function userProfile()
    {
        return $this->hasOneThrough(
            \App\Models\Portfolio\Profile::class,
            User::class,
            'id',        // users.id
            'user_id',   // profile.user_id
            'user_id',   // investor_zone_posts.user_id
            'id'         // users.id
        );
    }
}
