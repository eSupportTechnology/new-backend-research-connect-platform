<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipPricing extends Model
{
    protected $table = 'membership_pricing';

    protected $fillable = [
        'bronze_to_silver',
        'silver_to_gold',
        'bronze_to_gold',
    ];

    protected $casts = [
        'bronze_to_silver' => 'decimal:2',
        'silver_to_gold'   => 'decimal:2',
        'bronze_to_gold'   => 'decimal:2',
    ];

    public static function config(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'bronze_to_silver' => 2500.00,
            'silver_to_gold'   => 4500.00,
            'bronze_to_gold'   => 6000.00,
        ]);
    }
}