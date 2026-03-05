<?php

namespace App\Models\Innovation;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Innovation extends Model
{
    use HasUuids ;
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'video_url',
        'thumbnail',
        'category',
        'first_name',
        'last_name',
        'tags',
        'is_paid',
        'price'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
