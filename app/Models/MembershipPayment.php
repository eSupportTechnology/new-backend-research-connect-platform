<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipPayment extends Model
{
    protected $fillable = [
        'user_id',
        'from_tier',
        'to_tier',
        'amount',
        'order_id_string',
        'payhere_payment_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\RegisterUsers\User::class, 'user_id');
    }
}