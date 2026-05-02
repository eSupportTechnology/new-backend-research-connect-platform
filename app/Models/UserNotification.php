<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserNotification extends Model
{
    protected $primaryKey = 'id';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = ['user_id', 'type', 'title', 'message', 'data', 'read_at'];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) $model->id = (string) Str::uuid();
        });
    }

    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }
}