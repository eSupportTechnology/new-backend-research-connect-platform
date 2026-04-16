<?php

namespace App\Models\Profile;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'bank_name', 
        'account_holder_name', 
        'account_number', 
        'branch_name', 
        'is_default'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
