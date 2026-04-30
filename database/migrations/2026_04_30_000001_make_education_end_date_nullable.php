<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('education', function (Blueprint $table) {
            $table->string('end_month', 20)->nullable()->change();
            $table->integer('end_year')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('education', function (Blueprint $table) {
            $table->string('end_month', 20)->nullable(false)->change();
            $table->integer('end_year')->nullable(false)->change();
        });
    }
};