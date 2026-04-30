<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_upload_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('order_id')->unique();
            $table->unsignedBigInteger('file_size_bytes');
            $table->unsignedInteger('file_size_mb');
            $table->unsignedInteger('excess_mb');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'failed', 'used'])->default('pending');
            $table->string('payhere_payment_id')->nullable();
            $table->string('upload_token')->nullable()->unique();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_upload_payments');
    }
};