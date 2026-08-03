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
        Schema::create('community_likes', function (Blueprint $col) {
            $col->id();
            // No FK yet: users.id is a UUID, so this bigint column can't
            // reference it directly. 2026_04_18_082754_fix_community_tables_user_id_type
            // drops and recreates it as a UUID with the correct FK.
            $col->unsignedBigInteger('user_id');
            $col->foreignId('post_id')->constrained('community_posts')->onDelete('cascade');
            $col->timestamps();

            // Prevent duplicate likes
            $col->unique(['user_id', 'post_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_likes');
    }
};
