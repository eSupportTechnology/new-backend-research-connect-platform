<?php

namespace App\Models;

use App\Models\RegisterUsers\User;
use App\Models\Innovation\SellingItem;
use App\Models\Profile\BankDetail;
use App\Models\Profile\ShippingAddress;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_id_string',
        'buyer_id',
        'seller_id',
        'selling_item_id',
        'bank_detail_id',
        'shipping_address_id',
        'quantity',
        'amount',
        'status',
        'payhere_payment_id',
        'payhere_method',
        'payment_method',
        'courier_name',
        'courier_phone',
        'tracking_number',
        'delivery_status',
        'business_name',
        'payout_status',
        'payout_notes',
        'paid_out_at',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id', 'id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id', 'id');
    }

    public function sellingItem()
    {
        return $this->belongsTo(SellingItem::class, 'selling_item_id');
    }

    public function bankDetail()
    {
        return $this->belongsTo(BankDetail::class, 'bank_detail_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(ShippingAddress::class, 'shipping_address_id');
    }
}
