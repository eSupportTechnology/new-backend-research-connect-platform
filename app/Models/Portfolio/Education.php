<?php

namespace App\Models\Portfolio;

use App\Models\RegisterUsers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Education extends Model
{
    use HasFactory;

    protected $table = 'education';
    protected $fillable = [
        'user_id',
        'school',
        'institute_logo',
        'degree',
        'field_of_study',
        'grade',
        'activities',
        'description',
        'start_month',
        'start_year',
        'end_month',
        'end_year'
    ];

    protected $casts = [
        'start_year' => 'integer',
        'end_year'   => 'integer',
    ];

    protected $appends = ['institute_logo_url'];  // ✅ Added so it appears in API responses

    // Relationships
    public function user()
    {
        return $this->belongsTo(RegisterUsers\User::class, 'user_id');
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'user_id', 'user_id');
    }

    // Accessors
    public function getDurationAttribute()
    {
        return $this->start_month . ' ' . $this->start_year . ' - ' . $this->end_month . ' ' . $this->end_year;
    }

    public function getDegreeWithFieldAttribute()
    {
        $result = $this->degree ?? '';
        if ($this->field_of_study) {
            $result .= $result ? ' in ' . $this->field_of_study : $this->field_of_study;
        }
        return $result;
    }

    // Mutators
    public function setDegreeAttribute($value)
    {
        $this->attributes['degree'] = $value === '' || $value === null ? null : $value;
    }

    public function setFieldOfStudyAttribute($value)
    {
        $this->attributes['field_of_study'] = $value === '' || $value === null ? null : $value;
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = $value === '' || $value === null ? null : $value;
    }

    // ✅ Changed from local storage to S3
    public function getInstituteLogoUrlAttribute(): ?string
    {
        if (!$this->institute_logo) return null;

        $cleaned = trim($this->institute_logo);
        if (strlen($cleaned) === 0) return null;

        try {
            return Storage::disk('s3')->url($cleaned);
        } catch (\Exception $e) {
            \Log::warning('S3 URL failed for institute_logo', [
                'path'  => $cleaned,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
