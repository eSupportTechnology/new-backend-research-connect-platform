<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('innovations', function (Blueprint $table) {
            $table->string('main_field')->nullable()->after('category');
            $table->string('innovation_category')->nullable()->after('main_field');
            $table->json('extra_people')->nullable()->after('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('innovations', function (Blueprint $table) {
            $table->dropColumn(['main_field', 'innovation_category', 'extra_people']);
        });
    }
};