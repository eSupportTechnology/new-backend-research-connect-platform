<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HubCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'subtitle',
        'description',
        'tag',
        'image',
        'route',
        'order_index',
    ];
}
