<?php

namespace App\Models\Innovation;

use Illuminate\Database\Eloquent\Model;

class InnovationViews extends Model
{
    protected $fillable = [
        'innovation_id',
        'user_id',
        'ip_address',
    ];
}
