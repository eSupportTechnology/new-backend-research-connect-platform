<?php

namespace App\Models\Research;

use App\Models\Innovation\InnovationViews;
use App\Models\Portfolio\Profile;
use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Research extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'research';

    /** Open to every tier, including Bronze. */
    public const ACCESS_FREE = 'free';

    /** Silver and above (School Students bypass the tier requirement). */
    public const ACCESS_LIMITED = 'limited';

    /** Costs the publisher's price. */
    public const ACCESS_PAID = 'paid';

    /** The only values access_type may hold. */
    public const ACCESS_TYPES = [
        self::ACCESS_FREE,
        self::ACCESS_LIMITED,
        self::ACCESS_PAID,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'abstract',
        'document_url',
        'thumbnail',
        'category',
        'sub_category',
        'research_type',
        'research_level',
        'first_name',
        'last_name',
        'extra_people',
        'tags',
        'is_paid',
        'access_type',
        'is_adult',
        'price',
        'status',
        'views',
        'downloads',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_paid' => 'boolean',
        'access_type' => 'string',
        'is_adult' => 'boolean',
        'price' => 'decimal:2',
        'views' => 'integer',
        'downloads' => 'integer',
        'extra_people' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'full_author_name',
        'tags_array',
        'user_has_liked',
        'user_has_disliked',
        'likes_count',
        'dislikes_count',
        'average_rating',
        'total_ratings',
        'user_rating',
    ];

    /**
     * Get the full author name.
     *
     * @return string
     */
    public function getFullAuthorNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get tags as an array.
     *
     * @return array
     */
    public function getTagsArrayAttribute()
    {
        if (empty($this->tags)) {
            return [];
        }

        return array_map('trim', explode(',', $this->tags));
    }

    /**
     * Scope a query to only include approved research.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include pending research.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to filter by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to research that costs nothing — FREE *and* LIMITED
     * ACCESS. This is the price filter behind `?free=1`, not an access-type
     * filter; use scopeAccessType() when you mean the type specifically.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFree($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Scope a query to filter by paid research.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Scope a query to one access type (free | limited | paid).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccessType($query, string $type)
    {
        return $query->where('access_type', strtolower($type));
    }

    /**
     * Scope a query to research every tier can open, Bronze included.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpenAccess($query)
    {
        return $query->where('access_type', self::ACCESS_FREE);
    }

    /**
     * Scope a query to research that requires Silver or above.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLimitedAccess($query)
    {
        return $query->where('access_type', self::ACCESS_LIMITED);
    }

    /**
     * Scope a query to exclude adult (18+) content.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExcludeAdult($query)
    {
        return $query->where('is_adult', false);
    }

    /**
     * Scope a query to order by most viewed.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query)
    {
        return $query->orderBy('views', 'desc');
    }

    /**
     * Scope a query to order by latest.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to search by title or abstract.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('abstract', 'like', "%{$search}%")
                ->orWhere('tags', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%");
        });
    }

    /**
     * Increment views count.
     *
     * @return void
     */
    public function incrementViews()
    {
        $this->increment('views');
    }
    public function researchViews()
    {
        return $this->hasMany(ResearchViews::class, 'research_id');
    }
    /**
     * Increment downloads count.
     *
     * @return void
     */
    public function incrementDownloads()
    {
        $this->increment('downloads');
    }

    /**
     * Get the user that owns the research.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function userProfile()
    {
        return $this->belongsTo(Profile::class, 'user_id', 'user_id');
    }

    public function allLikes()
    {
        return $this->hasMany(ResearchLike::class, 'research_id');
    }

    public function likes()
    {
        return $this->hasMany(ResearchLike::class, 'research_id')->where('is_like', true);
    }

    public function dislikes()
    {
        return $this->hasMany(ResearchLike::class, 'research_id')->where('is_like', false);
    }

    public function comments()
    {
        return $this->hasMany(ResearchComment::class, 'research_id');
    }

    // Accessors
    public function getUserHasLikedAttribute()
    {
        if (!auth()->check()) {
            return false;
        }

        return $this->allLikes()
            ->where('user_id', auth()->id())
            ->where('is_like', true)
            ->exists();
    }

    public function getUserHasDislikedAttribute()
    {
        if (!auth()->check()) {
            return false;
        }

        return $this->allLikes()
            ->where('user_id', auth()->id())
            ->where('is_like', false)
            ->exists();
    }

    public function getLikesCountAttribute()
    {
        return $this->likes()->count();
    }

    public function getDislikesCountAttribute()
    {
        return $this->dislikes()->count();
    }

    /**
     * Get the average rating from comments.
     */
    public function getAverageRatingAttribute()
    {
        $avg = $this->comments()->where('rating', '>', 0)->avg('rating');
        return round($avg ?? 0, 1);
    }

    /**
     * Get the total number of ratings.
     */
    public function getTotalRatingsAttribute()
    {
        return $this->comments()->where('rating', '>', 0)->count();
    }

    /**
     * Get the current user's rating (if any).
     */
    public function getUserRatingAttribute()
    {
        if (!auth()->check()) {
            return null;
        }

        $comment = $this->comments()
            ->where('user_id', auth()->id())
            ->first();

        return $comment ? $comment->rating : null;
    }

    /**
     * Normalise access_type and keep the legacy is_paid boolean in step.
     *
     * access_type is the source of truth; is_paid is a derived convenience for
     * the price filters, the marketplace and the admin screens that still read
     * it. Whichever of the two a caller sets, both end up consistent.
     */
    public function normaliseAccessType(): void
    {
        $type = strtolower((string) $this->access_type);

        if (! in_array($type, self::ACCESS_TYPES, true)) {
            // Nothing usable was set — fall back to the is_paid boolean so
            // older callers that only know about is_paid still work.
            $type = $this->is_paid ? self::ACCESS_PAID : self::ACCESS_LIMITED;
        }

        $this->access_type = $type;
        $this->is_paid     = $type === self::ACCESS_PAID;

        // A price only means anything on a PAID paper.
        if ($type !== self::ACCESS_PAID) {
            $this->price = null;
        }
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($research) {
            if (is_null($research->status)) {
                $research->status = 'pending';
            }
            if (is_null($research->views)) {
                $research->views = 0;
            }
            if (is_null($research->downloads)) {
                $research->downloads = 0;
            }
            if (is_null($research->is_paid)) {
                $research->is_paid = false;
            }
        });

        // Runs on create *and* update, so access_type and is_paid can never
        // drift apart no matter which one a controller touched.
        static::saving(function ($research) {
            $research->normaliseAccessType();
        });
    }
}
