<?php

namespace App\Models;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Model;

class HireRequest extends Model
{
    protected $fillable = [
        'requester_user_id',
        'provider_user_id',
        'title',
        'description',
        'budget',
        'start_date',
        'deadline',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'deadline'   => 'date',
        'budget'     => 'decimal:2',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_user_id', 'id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_user_id', 'id');
    }
}