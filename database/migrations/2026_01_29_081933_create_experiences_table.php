<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experience', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('title');
            $table->string('organization');
            $table->string('company_logo')->nullable();
            $table->string('employment_type')->nullable();
            $table->boolean('is_currently_working')->default(false);
            $table->string('start_month', 20);
            $table->integer('start_year');
            $table->string('end_month', 20)->nullable();
            $table->integer('end_year')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experience');
    }
};
