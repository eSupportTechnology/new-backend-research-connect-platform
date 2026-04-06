<?php

namespace App\Models\Research;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResearchLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'research_id',
        'is_like',
    ];

    protected $casts = [
        'is_like' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function research()
    {
        return $this->belongsTo(Research::class, 'research_id');
    }

}
