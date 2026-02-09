<?php

namespace App\Models\Profile;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'card_number', 'expiry', 'security_code', 'holder_name', 'is_default'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}

