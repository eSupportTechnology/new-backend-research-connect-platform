<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_upload_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('free_limit_mb')->default(500);
            $table->unsignedInteger('price_per_100mb')->default(100);
            $table->timestamps();
        });

        // Seed the single config row
        DB::table('video_upload_fees')->insert([
            'free_limit_mb'   => 500,
            'price_per_100mb' => 100,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('video_upload_fees');
    }
};