<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investor_zone_posts', function (Blueprint $table) {
            $table->decimal('investment_amount', 15, 2)->nullable()->after('key_highlights');
        });
    }

    public function down(): void
    {
        Schema::table('investor_zone_posts', function (Blueprint $table) {
            $table->dropColumn('investment_amount');
        });
    }
};