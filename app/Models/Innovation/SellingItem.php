<?php

namespace App\Models\Innovation;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
class SellingItem extends Model
{
    protected $fillable = [
        'user_id',
        'sellable_type',
        'sellable_id',
        'title',
        'description',
        'thumbnail',
        'category',
        'tags',
        'is_paid',
        'price',
        'status',
        'total_views',
        'total_purchases',
        'total_revenue',
        'listed_at'
    ];

    protected $casts = [
        'tags' => 'array',
        'is_paid' => 'boolean',
        'price' => 'decimal:2',
        'total_views' => 'integer',
        'total_purchases' => 'integer',
        'total_revenue' => 'decimal:2',
        'listed_at' => 'datetime'
    ];

    public function sellable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
