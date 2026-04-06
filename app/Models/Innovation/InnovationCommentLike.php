<?php

namespace App\Models\Innovation;

use App\Models\Innovation\InnovationComment;
use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InnovationCommentLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'comment_id',
        'is_like',
    ];

    protected $casts = [
        'is_like' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function comment()
    {
        return $this->belongsTo(InnovationComment::class, 'comment_id');
    }
}
