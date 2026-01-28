<?php

namespace App\Models\RegisterUsers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Investor extends Model
{
    use HasFactory; use Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'user_id',
        'phone',
        'address',
        'investment_preferences',
    ];
    protected static function boot()
    {
        parent::boot();

        // Automatically generate UUID
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });
    }
    protected $hidden = ['password'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
