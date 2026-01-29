<?php

namespace App\Models\Portfolio;


use App\Models\RegisterUsers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use HasFactory;

    protected $table = 'education';
    protected $fillable = [
        'user_id',
        'school',
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
}
