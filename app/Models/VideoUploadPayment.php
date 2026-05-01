<?php

namespace App\Models;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VideoUploadPayment extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'file_size_bytes', 'file_size_mb',
        'excess_mb', 'amount', 'status', 'payhere_payment_id', 'upload_token',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public static function generateOrderId(int $id): string
    {
        return 'VID' . $id . 'T' . time();
    }

    public function generateUploadToken(): string
    {
        $token = Str::random(64);
        $this->update(['upload_token' => $token, 'status' => 'paid']);
        return $token;
    }
}