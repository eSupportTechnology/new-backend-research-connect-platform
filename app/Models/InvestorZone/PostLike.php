<?php

namespace App\Models\InvestorZone;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Model;

class PostLike extends Model
{
    protected $fillable = ['post_id', 'user_id'];

    public function post()
    {
        return $this->belongsTo(InvestorZonePost::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
