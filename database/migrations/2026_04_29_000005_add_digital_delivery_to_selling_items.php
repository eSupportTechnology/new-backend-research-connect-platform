<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('selling_items', function (Blueprint $table) {
            $table->string('delivery_type')->default('physical')->after('type'); // physical | digital
            $table->text('digital_file_url')->nullable()->after('delivery_type');
        });
    }

    public function down(): void
    {
        Schema::table('selling_items', function (Blueprint $table) {
            $table->dropColumn(['delivery_type', 'digital_file_url']);
        });
    }
};