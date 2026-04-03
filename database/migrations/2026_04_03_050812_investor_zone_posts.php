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
        Schema::create('investor_zone_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // investment-categories, partnerships, mentorships, financial-services
            $table->string('category'); // seed, banks, labs, etc.
            $table->string('title', 100);
            $table->text('description');
            $table->text('key_highlights');
            $table->string('media_path')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('linkedin')->nullable();
            $table->boolean('allow_likes')->default(false);
            $table->boolean('allow_contact_sharing')->default(false);
            $table->boolean('request_collaboration')->default(false);
            $table->boolean('require_approval')->default(false);
            $table->boolean('notify_engagement')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_zone_posts');
    }
};
