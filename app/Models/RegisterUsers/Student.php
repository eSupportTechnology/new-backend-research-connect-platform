<?php

namespace App\Models\RegisterUsers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [

        'user_id',
        'school_name',
        'grade_level',
        'student_id',
    ];


    protected static function boot()
    {
        parent::boot();

        // auto-generate UUID for 'id' if not provided
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->hasOne(ParentModel::class);
    }
}
