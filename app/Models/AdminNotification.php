<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $table = 'admin_notifications';

    protected $fillable = ['type', 'title', 'message', 'data', 'is_read'];

    protected $casts = [
        'data'    => 'array',
        'is_read' => 'boolean',
    ];

    public static function notify(string $type, string $title, string $message, array $data = []): void
    {
        static::create(compact('type', 'title', 'message', 'data'));
    }
}