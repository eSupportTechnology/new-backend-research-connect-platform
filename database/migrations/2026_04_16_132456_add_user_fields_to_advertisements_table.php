<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->string('status')->default('active')->after('is_active'); // pending, approved, rejected, active, expired
            $table->string('payment_status')->default('paid')->after('status'); // unpaid, paid, failed
            $table->string('payment_id')->nullable()->after('payment_status');
            $table->decimal('price', 10, 2)->nullable()->after('payment_id');
            $table->text('rejection_reason')->nullable()->after('price');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'status', 'payment_status', 'payment_id', 'price', 'rejection_reason']);
        });
    }
};
