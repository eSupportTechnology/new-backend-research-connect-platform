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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'side', 'banner', 'carousel'
            $table->string('position')->nullable(); // 'left', 'right', null for banner/carousel
            $table->string('badge')->nullable();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description');
            $table->string('cta_text');
            $table->string('cta_link')->nullable();
            $table->string('image_path');
            $table->string('color')->nullable(); // for carousel ads
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            // NEW: Time slot fields for side ads
            $table->time('display_start_time')->nullable(); // e.g., '09:00:00'
            $table->time('display_end_time')->nullable();   // e.g., '17:00:00'

            $table->integer('max_impressions')->nullable();
            $table->integer('current_impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
