<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('innovation_views', function (Blueprint $table) {
            $table->id();

            $table->foreignId('innovation_id')
                ->constrained('innovations')
                ->onDelete('cascade');

            // Optional: track logged-in user
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('innovation_views');
    }
};
