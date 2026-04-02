<?php

namespace App\Models\Innovation;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
class SellingItem extends Model
{
    protected $table = 'selling_items';

    protected $fillable = [
        'user_id',
        'sellable_type',
        'sellable_id',
        'title',
        'description',
        'thumbnail',
        'additional_images',
        'category',
        'tags',
        'is_paid',
        'price',
        'discount_percentage',
        'discounted_price',
        'stock_quantity',
        'sku',
        'condition',
        'delivery_time',
        'warranty_period',
        'return_policy',
        'shipping_cost',
        'whats_included',
        'specifications',
        'is_featured',
        'status',
        'total_views',
        'total_purchases',
        'total_revenue',
        'listed_at'
    ];

    protected $casts = [
        'tags' => 'array',
        'additional_images' => 'array',
        'specifications' => 'array',
        'is_paid' => 'boolean',
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'stock_quantity' => 'integer',
        'total_views' => 'integer',
        'total_purchases' => 'integer',
        'total_revenue' => 'decimal:2',
        'listed_at' => 'datetime'
    ];

    public function sellable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Accessor for discounted price
    public function getDiscountedPriceAttribute()
    {
        if ($this->discount_percentage > 0 && $this->price) {
            return $this->price - ($this->price * $this->discount_percentage / 100);
        }
        return $this->price;
    }

    // Check if item is in stock
    public function isInStock()
    {
        return $this->stock_quantity > 0;
    }

    // Decrease stock when purchased
    public function decreaseStock($quantity = 1)
    {
        if ($this->stock_quantity >= $quantity) {
            $this->stock_quantity -= $quantity;
            $this->save();
            return true;
        }
        return false;
    }

    // Get formatted price with discount
    public function getFormattedPrice()
    {
        if ($this->discount_percentage > 0) {
            return [
                'original' => '$' . number_format($this->price, 2),
                'discounted' => '$' . number_format($this->discounted_price, 2),
                'discount_percentage' => $this->discount_percentage
            ];
        }
        return [
            'original' => '$' . number_format($this->price, 2),
            'discounted' => null,
            'discount_percentage' => 0
        ];
    }
}
