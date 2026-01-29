<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('headline')->nullable();
            $table->text('bio')->nullable();
            $table->string('phone')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('skills')->nullable();
            $table->integer('follower_count')->default(0);
            $table->integer('following_count')->default(0);
            $table->integer('innovation_count')->default(0);
            $table->integer('research_count')->default(0);
            $table->integer('system_level')->default(1);
            $table->string('website')->nullable();
            $table->string('location')->nullable();
            $table->string('github_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile');
    }
};
