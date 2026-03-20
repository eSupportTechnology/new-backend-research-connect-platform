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
        Schema::create('innovations', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('title');
            $table->text('description');

            $table->string('video_url');
            $table->string('thumbnail')->nullable();

            $table->string('category');

            $table->string('first_name');
            $table->string('last_name');

            $table->string('tags')->nullable();

            $table->boolean('is_paid')->default(false);
            $table->decimal('price',8,2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('innovations');
    }
};
