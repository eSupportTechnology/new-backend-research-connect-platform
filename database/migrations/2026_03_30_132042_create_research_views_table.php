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
        Schema::create('research_views', function (Blueprint $table) {
            $table->id();

            $table->foreignId('research_id')
                ->constrained('research')
                ->onDelete('cascade');

            // Optional: track logged-in user
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');

            // Optional: track guest users
            $table->string('ip_address')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research_views');
    }
};
