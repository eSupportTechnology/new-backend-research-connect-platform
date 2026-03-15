<?php

namespace App\Models\Innovation;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Innovation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'video_url',
        'thumbnail',
        'category',
        'first_name',
        'last_name',
        'tags',
        'is_paid',
        'price'
    ];
    protected $casts = [
        'is_paid' => 'boolean',
        'price' => 'decimal:2',
        'views' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the innovation
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get tags as array
     */
    public function getTagsArrayAttribute()
    {
        return $this->tags ? array_map('trim', explode(',', $this->tags)) : [];
    }
}
