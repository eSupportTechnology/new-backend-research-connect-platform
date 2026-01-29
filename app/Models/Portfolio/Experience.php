<?php

namespace App\Models\Portfolio;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    use HasFactory;

    protected $table = 'experience';
    protected $fillable = [
        'user_id',
        'title',
        'organization',
        'employment_type',
        'is_currently_working',
        'start_month',
        'start_year',
        'end_month',
        'end_year',
        'location',
        'description'
    ];

    protected $casts = [
        'is_currently_working' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'user_id', 'user_id');
    }

    // Accessors
    public function getDurationAttribute()
    {
        if ($this->is_currently_working) {
            return $this->start_month . ' ' . $this->start_year . ' - Present';
        }
        return $this->start_month . ' ' . $this->start_year . ' - ' . $this->end_month . ' ' . $this->end_year;
    }
}
