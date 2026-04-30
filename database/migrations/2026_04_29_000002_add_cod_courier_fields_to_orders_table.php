<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->default('payhere')->after('payhere_method');
            $table->string('courier_name')->nullable()->after('payment_method');
            $table->string('tracking_number')->nullable()->after('courier_name');
            $table->string('delivery_status')->default('pending')->after('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'courier_name', 'tracking_number', 'delivery_status']);
        });
    }
};