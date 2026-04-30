<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hire_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('requester_user_id');
            $table->foreign('requester_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('provider_user_id');
            $table->foreign('provider_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->decimal('budget', 10, 2)->nullable();
            $table->date('deadline')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'completed'])->default('pending');
            $table->timestamps();

            $table->index('requester_user_id');
            $table->index('provider_user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hire_requests');
    }
};