<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('sender_user_id')->nullable();
            $table->foreign('sender_user_id')->references('id')->on('users')->onDelete('set null');
            $table->uuid('recipient_user_id');
            $table->foreign('recipient_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('sender_name');
            $table->string('sender_email');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index('recipient_user_id');
            $table->index('sender_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};