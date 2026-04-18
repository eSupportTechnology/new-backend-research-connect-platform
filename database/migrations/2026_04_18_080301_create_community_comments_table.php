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
        Schema::create('community_comments', function (Blueprint $col) {
            $col->id();
            $col->foreignId('user_id')->constrained()->onDelete('cascade');
            $col->foreignId('post_id')->constrained('community_posts')->onDelete('cascade');
            $col->text('content');
            $col->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_comments');
    }
};
