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
        Schema::create('community_posts', function (Blueprint $col) {
            $col->id();
            $col->foreignId('user_id')->constrained()->onDelete('cascade');
            $col->enum('type', ['research', 'discussion']);
            $col->string('title');
            $col->text('description');
            $col->longText('full_content')->nullable();
            $col->string('category')->nullable();
            $col->string('image_url')->nullable();
            $col->string('location')->nullable();
            $col->json('tags')->nullable();
            $col->boolean('is_recruiting')->default(false);
            $col->enum('status', ['active', 'inactive'])->default('active');
            $col->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_posts');
    }
};
