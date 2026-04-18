<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hub_cards', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('subtitle');
            $table->text('description')->nullable();
            $table->string('tag');
            $table->string('image');
            $table->string('route');
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hub_cards');
    }
};
