<?php

namespace App\Models;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Career extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'company_name',
        'logo_url',
        'category',
        'location',
        'job_type',
        'salary_range',
        'description',
        'requirements',
        'apply_link',
        'status',
        'is_featured'
    ];

    /**
     * Relationship: A career belongs to a user (employer/admin).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
