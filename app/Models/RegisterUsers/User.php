<?php

namespace App\Models\RegisterUsers;

use App\Models\Portfolio\Education;
use App\Models\Portfolio\Experience;
use App\Models\Portfolio\Profile;
use App\Models\Profile\Cards;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * UUID configuration
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'user_type',
    ];

    /**
     * Hidden fields
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Model boot
     */
    protected static function boot()
    {
        parent::boot();

        // 1️⃣ Generate UUID automatically
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });

        // 2️⃣ Auto-create profile AFTER user created
        static::created(function ($user) {
            // avoid duplicate profile creation
            if (! $user->profile) {
                $user->profile()->create([
                    'user_id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                ]);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id');
    }

    public function experiences()
    {
        return $this->hasMany(Experience::class, 'user_id');
    }

    public function educations()
    {
        return $this->hasMany(Education::class, 'user_id');
    }

    public function investor()
    {
        return $this->hasOne(Investor::class, 'user_id');
    }

    public function student()
    {
        return $this->hasOne(Student::class, 'user_id');
    }
    public function cards()
    {
        return $this->hasMany(Cards::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
