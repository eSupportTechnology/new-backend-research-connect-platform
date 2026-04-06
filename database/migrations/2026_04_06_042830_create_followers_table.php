<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('followers', function (Blueprint $table) {
            $table->id();
            $table->uuid('follower_id'); // The user who is following
            $table->uuid('following_id'); // The user being followed
            $table->timestamps();

            // Foreign keys
            $table->foreign('follower_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('following_id')->references('id')->on('users')->onDelete('cascade');

            // Prevent duplicate follows
            $table->unique(['follower_id', 'following_id']);

            // Indexes for performance
            $table->index('follower_id');
            $table->index('following_id');
            $table->index(['follower_id', 'following_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('followers');
    }
};
