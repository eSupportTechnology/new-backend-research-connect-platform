<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdPricingConfig extends Model
{
    protected $table = 'ad_pricing_configs';

    protected $fillable = [
        'carousel_price',
        'banner_price',
        'side_price',
        'popup_price',
    ];

    protected $casts = [
        'carousel_price' => 'decimal:2',
        'banner_price'   => 'decimal:2',
        'side_price'     => 'decimal:2',
        'popup_price'    => 'decimal:2',
    ];

    public static function config(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'carousel_price' => 5000.00,
            'banner_price'   => 3000.00,
            'side_price'     => 2000.00,
            'popup_price'    => 4000.00,
        ]);
    }
}