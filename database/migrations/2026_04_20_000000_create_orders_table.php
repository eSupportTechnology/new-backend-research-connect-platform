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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id_string')->unique(); // For PayHere: ORD-ID-TIME
            $table->uuid('buyer_id');
            $table->uuid('seller_id');
            $table->unsignedBigInteger('selling_item_id');
            $table->unsignedBigInteger('bank_detail_id')->nullable(); // Snapshot of seller's bank detail for payout
            $table->integer('quantity');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending, paid, failed, cancelled
            $table->string('payhere_payment_id')->nullable();
            $table->string('payhere_method')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('buyer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('selling_item_id')->references('id')->on('selling_items')->onDelete('cascade');
            // We don't cascade bank_detail_id because we want to keep the reference for audit even if seller removes the detail from profile.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
