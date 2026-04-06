<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('innovation_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->unsignedBigInteger('innovation_id');
            $table->text('text');
            $table->tinyInteger('rating')->unsigned()->default(0); // 0-5
            $table->integer('likes')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('innovation_id')->references('id')->on('innovations')->onDelete('cascade');

            $table->index(['innovation_id', 'created_at']);
        });

        // Table for tracking who liked which comment
        Schema::create('innovation_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->unsignedBigInteger('comment_id');
            $table->boolean('is_like')->default(true); // true = like, false = dislike
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('comment_id')->references('id')->on('innovation_comments')->onDelete('cascade');

            $table->unique(['user_id', 'comment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('innovation_comment_likes');
        Schema::dropIfExists('innovation_comments');
    }
};
