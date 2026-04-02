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
            $table->morphs('sellable'); // This creates sellable_type and sellable_id
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail');
            $table->string('category');
            $table->json('tags')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('status')->default('active'); // active, sold_out, inactive
            $table->integer('total_views')->default(0);
            $table->integer('total_purchases')->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0);
            $table->timestamp('listed_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('sellable_type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('selling_items');
    }
};
