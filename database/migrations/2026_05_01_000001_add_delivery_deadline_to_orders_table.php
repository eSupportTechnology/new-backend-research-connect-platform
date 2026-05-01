<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // prototype items = 4 days, all others = 14 days; set at order creation
            $table->timestamp('delivery_deadline')->nullable()->after('paid_out_at');
            // stamped when seller marks delivery_status = 'delivered'
            $table->timestamp('delivered_at')->nullable()->after('delivery_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_deadline', 'delivered_at']);
        });
    }
};