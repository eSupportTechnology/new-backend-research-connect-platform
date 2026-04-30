<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // pending = money received by platform, not yet sent to seller
            // paid_out = admin has transferred to seller's bank
            $table->string('payout_status')->default('pending')->after('business_name');
            $table->text('payout_notes')->nullable()->after('payout_status');
            $table->timestamp('paid_out_at')->nullable()->after('payout_notes');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payout_status', 'payout_notes', 'paid_out_at']);
        });
    }
};