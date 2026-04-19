<?php

namespace App\Models;

use App\Models\RegisterUsers\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
    ];

    /**
     * Helper to log an action
     */
    public static function logAction($action, $description = null)
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get the user that performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
