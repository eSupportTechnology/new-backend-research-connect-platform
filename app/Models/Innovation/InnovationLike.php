<?php

namespace App\Models\Innovation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InnovationLike extends Model
{
    /** @use HasFactory<\Database\Factories\Innovation\InnovationLikeFactory> */
    use HasFactory;

    protected $table = 'innovation_likes';

    protected $fillable = [
        'user_id',
        'innovation_id',
        'is_like',
    ];

    protected $casts = [
        'is_like' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\RegisterUsers\User::class, 'user_id');
    }

    public function innovation()
    {
        return $this->belongsTo(\App\Models\Innovation\Innovation::class, 'innovation_id');
    }
}
