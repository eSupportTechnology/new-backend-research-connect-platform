<?php

namespace App\Models\Research;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResearchComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'research_id',
        'text',
        'rating',
        'likes',
    ];

    protected $casts = [
        'rating' => 'integer',
        'likes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['user_has_liked', 'user_has_disliked'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function research()
    {
        return $this->belongsTo(Research::class);
    }

    public function commentLikes()
    {
        return $this->hasMany(ResearchCommentLike::class, 'comment_id');
    }

    public function likes()
    {
        return $this->hasMany(ResearchCommentLike::class, 'comment_id')->where('is_like', true);
    }

    public function dislikes()
    {
        return $this->hasMany(ResearchCommentLike::class, 'comment_id')->where('is_like', false);
    }

    // Accessors
    public function getUserHasLikedAttribute()
    {
        if (!auth()->check()) {
            return false;
        }

        return $this->commentLikes()
            ->where('user_id', auth()->id())
            ->where('is_like', true)
            ->exists();
    }

    public function getUserHasDislikedAttribute()
    {
        if (!auth()->check()) {
            return false;
        }

        return $this->commentLikes()
            ->where('user_id', auth()->id())
            ->where('is_like', false)
            ->exists();
    }
}
