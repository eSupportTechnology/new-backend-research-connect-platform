<?php

namespace App\Models;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'career_id',
        'applicant_id',
        'applicant_name',
        'applicant_email',
        'profile_url',
        'message',
        'cv_path',
        'status',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    protected $appends = ['cv_url'];

    public function career()
    {
        return $this->belongsTo(Career::class);
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function getCvUrlAttribute()
    {
        return $this->cv_path ? Storage::disk('public')->url($this->cv_path) : null;
    }
}
