<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('selling_items', function (Blueprint $table) {
            $table->string('sellable_type')->nullable()->change();
            $table->unsignedBigInteger('sellable_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('selling_items', function (Blueprint $table) {
            $table->string('sellable_type')->nullable(false)->change();
            $table->unsignedBigInteger('sellable_id')->nullable(false)->change();
        });
    }
};