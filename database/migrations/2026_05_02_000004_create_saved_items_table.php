<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('item_type'); // 'innovation' | 'research'
            $table->uuid('item_id');
            $table->timestamps();

            $table->index(['user_id', 'item_type']); // for fast lookups
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_items');
    }
};