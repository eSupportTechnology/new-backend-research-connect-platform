<?php

namespace App\Models\RegisterUsers;

use App\Models\Innovation\Innovation;
use App\Models\Portfolio\Education;
use App\Models\Portfolio\Experience;
use App\Models\Portfolio\Profile;
use App\Models\Profile\Cards;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * UUID configuration
     */
    protected $keyType = 'string';
    public $incrementing = false;
    protected $appends = ['follower_count', 'following_count', 'innovation_count', 'research_count', 'tier_badge'];

    // Organic progression thresholds
    const TIER_THRESHOLDS = [
        'bronze_to_silver' => 5,
        'silver_to_gold'   => 15,
    ];
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'user_type',
        'status',
        'membership_tier',
        'tier_upgraded_at',
        'tier_upgrade_source',
        'google_id',
        'facebook_id',
        'oauth_provider',
    ];

    /**
     * Hidden fields
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'tier_upgraded_at'  => 'datetime',
        ];
    }
    public function following()
    {
        return $this->belongsToMany(
            User::class,
            'followers',
            'follower_id',
            'following_id'
        )->withTimestamps();
    }
    public function followers()
    {
        return $this->belongsToMany(
            User::class,
            'followers',
            'following_id',
            'follower_id'
        )->withTimestamps();
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
    public function bankDetails()
    {
        return $this->hasMany(\App\Models\Profile\BankDetail::class, 'user_id');
    }
    public function innovations()
    {
        return $this->hasMany(Innovation::class);
    }
    public function researches()
    {
        return $this->hasMany(\App\Models\Research\Research::class);
    }
    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */
    public function isFollowing($userId)
    {
        return $this->following()->where('following_id', $userId)->exists();
    }

    /**
     * Check if this user is followed by another user
     */
    public function isFollowedBy($userId)
    {
        return $this->followers()->where('follower_id', $userId)->exists();
    }

    /**
     * Get follower count
     */
    public function getFollowerCountAttribute()
    {
        return $this->followers()->count();
    }

    /**
     * Get following count
     */
    public function getFollowingCountAttribute()
    {
        return $this->following()->count();
    }
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    public function getInnovationCountAttribute()
    {
        return $this->innovations()->count();
    }
    public function getResearchCountAttribute()
    {
        return $this->researches()->count();
    }

    public function getTierBadgeAttribute(): array
    {
        $tier = $this->membership_tier ?? 'bronze';
        $badges = [
            'bronze' => ['label' => 'Bronze', 'color' => '#CD7F32', 'bg' => '#FFF3E0', 'icon' => '🥉'],
            'silver' => ['label' => 'Silver', 'color' => '#9E9E9E', 'bg' => '#F5F5F5', 'icon' => '🥈'],
            'gold'   => ['label' => 'Gold',   'color' => '#FFD700', 'bg' => '#FFFDE7', 'icon' => '🥇'],
        ];
        return $badges[$tier] ?? $badges['bronze'];
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    public function canAccess(string $requiredTier): bool
    {
        $order = ['bronze' => 1, 'silver' => 2, 'gold' => 3];
        $current = $order[$this->membership_tier ?? 'bronze'] ?? 1;
        $required = $order[$requiredTier] ?? 1;
        return $current >= $required;
    }

    public function approvedUploadCount(): int
    {
        $innovations = $this->innovations()->where('status', 'active')->count();
        $research    = $this->researches()->whereIn('status', ['approved', 'active'])->count();
        return $innovations + $research;
    }

    public function checkAndUpgradeTier(): bool
    {
        $current = $this->membership_tier ?? 'bronze';
        if ($current === 'gold') {
            return false;
        }

        $uploadCount = $this->approvedUploadCount();
        $newTier = $current;

        if ($current === 'bronze' && $uploadCount >= self::TIER_THRESHOLDS['bronze_to_silver']) {
            $newTier = 'silver';
        }
        if ($current === 'silver' && $uploadCount >= self::TIER_THRESHOLDS['silver_to_gold']) {
            $newTier = 'gold';
        }

        if ($newTier !== $current) {
            $this->update([
                'membership_tier'      => $newTier,
                'tier_upgraded_at'     => now(),
                'tier_upgrade_source'  => 'organic',
            ]);
            return true;
        }

        return false;
    }
}
