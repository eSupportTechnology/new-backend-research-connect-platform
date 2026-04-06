<?php

namespace App\Models\Innovation;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follower extends Model
{
    use HasFactory;

    protected $fillable = [
        'follower_id',
        'following_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The user who is following
     */
    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id', 'id');
    }

    /**
     * The user being followed
     */
    public function following()
    {
        return $this->belongsTo(User::class, 'following_id', 'id');
    }
}
