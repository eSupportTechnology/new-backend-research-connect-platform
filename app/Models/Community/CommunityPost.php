<?php

namespace App\Models\Community;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'full_content',
        'category',
        'image_url',
        'location',
        'tags',
        'is_recruiting',
        'status',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_recruiting' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(CommunityLike::class, 'post_id');
    }

    public function comments()
    {
        return $this->hasMany(CommunityComment::class, 'post_id');
    }

    public function userLiked()
    {
        return $this->hasOne(CommunityLike::class, 'post_id')->where('user_id', auth()->id());
    }
}
