<?php

namespace App\Models\RegisterUsers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Investor extends Model
{
    use HasFactory; use Notifiable;
    protected $primaryKey = 'user_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'user_id',
        'phone',
        'address',
        'investment_preferences',
    ];

    protected $hidden = ['password'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
