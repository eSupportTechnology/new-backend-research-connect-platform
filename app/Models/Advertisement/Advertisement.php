<?php

namespace App\Models\Advertisement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'display_start_time' => 'datetime',
        'display_end_time' => 'datetime',
    ];

    /**
     * Scope to get only active ads
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_impressions')
                    ->orWhereRaw('current_impressions < max_impressions');
            })
            ->where(function ($q) {
                // Check time slot constraints (only for side ads with time slots)
                $q->whereNull('display_start_time')
                    ->orWhere('display_start_time', '<=', now()->format('H:i:s'))
                    ->where(function ($sub) {
                        $sub->whereNull('display_end_time')
                            ->orWhere('display_end_time', '>=', now()->format('H:i:s'));
                    });
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
        if (!$this->is_active) {
            return false;
        }

        if ($this->start_date && $this->start_date->isFuture()) {
            return false;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        if ($this->max_impressions && $this->current_impressions >= $this->max_impressions) {
            return false;
        }

        // Check time slot constraints (only for side ads)
        if ($this->type === 'side') {
            $now = now();
            $currentTime = $now->format('H:i:s');

            if ($this->display_start_time && $currentTime < $this->display_start_time->format('H:i:s')) {
                return false;
            }

            if ($this->display_end_time && $currentTime > $this->display_end_time->format('H:i:s')) {
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
        return asset('storage/' . $this->image_path);
    }

    /**
     * Check if ad has time slot restrictions
     */
    public function hasTimeRestrictions(): bool
    {
        return $this->type === 'side' && ($this->display_start_time || $this->display_end_time);
    }

    /**
     * Get formatted display start time (H:i)
     */
    public function getFormattedStartTimeAttribute(): ?string
    {
        return $this->display_start_time ? $this->display_start_time->format('H:i') : null;
    }

    /**
     * Get formatted display end time (H:i)
     */
    public function getFormattedEndTimeAttribute(): ?string
    {
        return $this->display_end_time ? $this->display_end_time->format('H:i') : null;
    }

    /**
     * Get time slot display string
     */
    public function getTimeSlotDisplayAttribute(): string
    {
        if (!$this->hasTimeRestrictions()) {
            return 'All day';
        }

        $start = $this->formatted_start_time ?? '00:00';
        $end = $this->formatted_end_time ?? '23:59';

        return "{$start} - {$end}";
    }
}
