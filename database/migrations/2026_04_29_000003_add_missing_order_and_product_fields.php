<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('courier_phone')->nullable()->after('courier_name');
            $table->string('business_name')->nullable()->after('delivery_status');
        });

        Schema::table('selling_items', function (Blueprint $table) {
            $table->string('type')->default('innovation')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['courier_phone', 'business_name']);
        });

        Schema::table('selling_items', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};