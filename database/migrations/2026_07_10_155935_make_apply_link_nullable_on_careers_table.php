<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Application link is now optional (contact email is required instead).
     */
    public function up(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            $table->string('apply_link')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            $table->string('apply_link')->nullable(false)->change();
        });
    }
};
