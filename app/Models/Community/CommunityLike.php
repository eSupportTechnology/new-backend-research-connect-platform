<?php

namespace App\Models\Community;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityLike extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'post_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(CommunityPost::class, 'post_id');
    }
}
