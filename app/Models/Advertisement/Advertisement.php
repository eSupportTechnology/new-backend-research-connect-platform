<?php

namespace App\Models\Advertisement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Advertisement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'position',
        'badge',
        'title',
        'subtitle',
        'description',
        'cta_text',
        'cta_link',
        'image_path',
        'color',
        'order',
        'is_active',
        'start_date',
        'end_date',
        'max_impressions',
        'current_impressions',
        'clicks',
        'display_start_time',
        'display_end_time',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'start_date'         => 'datetime',
        'end_date'           => 'datetime',
        'display_start_time' => 'datetime',
        'display_end_time'   => 'datetime',
    ];

    /**
     * Scope to get only active, date-valid, impression-valid ads.
     *
     * BUG FIX: time-slot check is now two SEPARATE where() groups so
     * the start and end conditions are always AND-ed correctly regardless
     * of whether display_start_time / display_end_time is NULL.
     *
     * Old (broken) pattern:
     *   whereNull('display_start_time')
     *     ->orWhere('display_start_time', '<=', $time)
     *     ->where(...)          <-- attaches only to the orWhere branch
     *
     * Fixed pattern: split into two independent groups, each with its own
     * NULL-fallback, so SQL becomes:
     *   AND (display_start_time IS NULL OR display_start_time <= ?)
     *   AND (display_end_time   IS NULL OR display_end_time   >= ?)
     */
    public function scopeActive($query, $tz = 'Asia/Colombo')
    {
        $now         = now($tz);
        $currentTime = $now->format('H:i:s');

        return $query
            // Must be active
            ->where('is_active', true)

            // Date range — start
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $now);
            })

            // Date range — end
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            })

            // Impression cap (0 means unlimited)
            ->where(function ($q) {
                $q->whereNull('max_impressions')
                    ->orWhere('max_impressions', 0)
                    ->orWhereRaw('current_impressions < max_impressions');
            })

            // ✅ FIX: display_start_time — own group
            ->where(function ($q) use ($currentTime) {
                $q->whereNull('display_start_time')
                    ->orWhere('display_start_time', '<=', $currentTime);
            })

            // ✅ FIX: display_end_time — own separate group
            ->where(function ($q) use ($currentTime) {
                $q->whereNull('display_end_time')
                    ->orWhere('display_end_time', '>=', $currentTime);
            });
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by position
     */
    public function scopePosition($query, $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Increment impression count
     */
    public function recordImpression()
    {
        $this->increment('current_impressions');
    }

    /**
     * Increment click count
     */
    public function recordClick()
    {
        $this->increment('clicks');
    }

    /**
     * Check if ad should still be shown
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) return false;

        $tz = 'Asia/Colombo';

        if ($this->start_date && $this->start_date->greaterThan(now($tz))) return false;
        if ($this->end_date   && $this->end_date->lessThan(now($tz)))     return false;

        if ($this->max_impressions > 0 && $this->current_impressions >= $this->max_impressions) {
            return false;
        }

        if ($this->type === 'side') {
            $currentTime = now($tz)->format('H:i:s');

            if ($this->display_start_time &&
                $currentTime < $this->display_start_time->format('H:i:s')) {
                return false;
            }

            if ($this->display_end_time &&
                $currentTime > $this->display_end_time->format('H:i:s')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the full image URL
     */
    public function getImageUrlAttribute()
    {
        return $this->image_path ? Storage::disk('s3')->url($this->image_path) : null;
    }

    /**
     * Check if ad has time slot restrictions
     */
    public function hasTimeRestrictions(): bool
    {
        return $this->type === 'side' &&
            ($this->display_start_time || $this->display_end_time);
    }

    public function getFormattedStartTimeAttribute(): ?string
    {
        return $this->display_start_time
            ? $this->display_start_time->format('H:i')
            : null;
    }

    public function getFormattedEndTimeAttribute(): ?string
    {
        return $this->display_end_time
            ? $this->display_end_time->format('H:i')
            : null;
    }

    public function getTimeSlotDisplayAttribute(): string
    {
        if (!$this->hasTimeRestrictions()) return 'All day';

        $start = $this->formatted_start_time ?? '00:00';
        $end   = $this->formatted_end_time   ?? '23:59';

        return "{$start} - {$end}";
    }
}
