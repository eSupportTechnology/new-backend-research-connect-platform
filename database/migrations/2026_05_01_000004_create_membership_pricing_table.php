<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_pricing', function (Blueprint $table) {
            $table->id();
            $table->decimal('bronze_to_silver', 10, 2)->default(2500.00);
            $table->decimal('silver_to_gold',   10, 2)->default(4500.00);
            $table->decimal('bronze_to_gold',   10, 2)->default(6000.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_pricing');
    }
};