<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoUploadFee extends Model
{
    protected $fillable = ['free_limit_mb', 'price_per_100mb'];

    public static function config(): self
    {
        return self::firstOrCreate([], [
            'free_limit_mb'   => 500,
            'price_per_100mb' => 100,
        ]);
    }

    public function calculate(int $fileSizeMb): array
    {
        $excessMb = max(0, $fileSizeMb - $this->free_limit_mb);
        $amount   = $excessMb > 0 ? (int) ceil($excessMb / 100) * $this->price_per_100mb : 0;

        return [
            'requires_payment' => $excessMb > 0,
            'file_size_mb'     => $fileSizeMb,
            'free_limit_mb'    => $this->free_limit_mb,
            'excess_mb'        => $excessMb,
            'price_per_100mb'  => $this->price_per_100mb,
            'amount'           => $amount,
        ];
    }
}