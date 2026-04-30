<?php

namespace App\Models;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'sender_user_id',
        'recipient_user_id',
        'sender_name',
        'sender_email',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id', 'id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_user_id', 'id');
    }
}