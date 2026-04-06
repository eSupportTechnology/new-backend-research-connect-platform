<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->unsignedBigInteger('research_id');
            $table->text('text');
            $table->tinyInteger('rating')->unsigned()->default(0); // 0-5
            $table->integer('likes')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('research_id')->references('id')->on('research')->onDelete('cascade');

            $table->index(['research_id', 'created_at']);
        });

        // Table for tracking who liked which comment
        Schema::create('research_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->unsignedBigInteger('comment_id');
            $table->boolean('is_like')->default(true); // true = like, false = dislike
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('comment_id')->references('id')->on('research_comments')->onDelete('cascade');

            $table->unique(['user_id', 'comment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_comment_likes');
        Schema::dropIfExists('research_comments');
    }
};
