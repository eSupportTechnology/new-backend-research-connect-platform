<?php

namespace App\Models\RegisterUsers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ParentModel extends Model
{
    use HasFactory;

    protected $table = 'parents'; // Explicit table name
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'student_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'relation',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });
    }

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
