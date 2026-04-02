<?php
// database/migrations/2026_04_02_000000_create_selling_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('selling_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->morphs('sellable'); // Creates sellable_type and sellable_id

            // Basic Information
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail');
            $table->json('additional_images')->nullable();
            $table->string('category');
            $table->json('tags')->nullable();

            // Pricing
            $table->boolean('is_paid')->default(true);
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discounted_price', 10, 2)->nullable();

            // Inventory
            $table->integer('stock_quantity')->default(1);
            $table->string('sku')->nullable()->unique();
            $table->string('condition')->default('new'); // new, like_new, excellent, good, fair

            // Shipping & Delivery
            $table->integer('delivery_time')->default(5); // days
            $table->integer('warranty_period')->default(6); // months
            $table->integer('return_policy')->default(30); // days
            $table->decimal('shipping_cost', 10, 2)->default(0);

            // Product Details
            $table->text('whats_included')->nullable();
            $table->json('specifications')->nullable();
            $table->boolean('is_featured')->default(false);

            // Status & Statistics
            $table->string('status')->default('active'); // active, sold_out, inactive
            $table->integer('total_views')->default(0);
            $table->integer('total_purchases')->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0);

            $table->timestamp('listed_at')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('sellable_type');
            $table->index('sku');
            $table->index('is_featured');
            $table->index('condition');
        });
    }

    public function down()
    {
        Schema::dropIfExists('selling_items');
    }
};
