<?php

namespace App\Models\Profile;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'country',
        'first_name',
        'last_name',
        'address',
        'apartment',
        'city',
        'postal_code',
        'phone',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // Relationship
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope for default address
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Scope for user addresses
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
