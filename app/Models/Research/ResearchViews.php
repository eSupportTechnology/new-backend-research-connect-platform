<?php
// app/Models/Research/ResearchViews.php

namespace App\Models\Research;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Model;


class ResearchViews extends Model
{
    protected $table = 'research_views';

    protected $fillable = [
        'research_id',
        'user_id',

    ];

    protected $casts = [
        'research_id' => 'integer',
        'user_id' => 'string', // since it's UUID
    ];

    /**
     * Get the research that was viewed
     */
    public function research()
    {
        return $this->belongsTo(Research::class, 'research_id');
    }

    /**
     * Get the user who viewed the research
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
