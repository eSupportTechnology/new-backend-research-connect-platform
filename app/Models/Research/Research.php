<?php

namespace App\Models\Research;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Model;

class Research extends Model
{

    protected $table = 'research';

    protected $fillable = [
        'user_id',
        'title',
        'abstract',
        'document_url',
        'thumbnail',
        'category',
        'first_name',
        'last_name',
        'tags',
        'is_paid',
        'price',
        'status',
        'views',
        'downloads'
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'price' => 'decimal:2',
        'views' => 'integer',
        'downloads' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    /**
     * Get the user that owns the research
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getTagsArrayAttribute()
    {
        return $this->tags ? array_map('trim', explode(',', $this->tags)) : [];
    }
}
