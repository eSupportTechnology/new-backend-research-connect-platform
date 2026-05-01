<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_pricing_configs', function (Blueprint $table) {
            $table->id();
            $table->decimal('carousel_price', 10, 2)->default(5000.00);
            $table->decimal('banner_price',   10, 2)->default(3000.00);
            $table->decimal('side_price',     10, 2)->default(2000.00);
            $table->decimal('popup_price',    10, 2)->default(4000.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_pricing_configs');
    }
};