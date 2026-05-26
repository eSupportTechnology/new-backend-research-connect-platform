<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->string('sub_category')->nullable()->after('category');
            $table->string('research_type')->nullable()->after('sub_category');
            $table->string('research_level')->nullable()->after('research_type');
            $table->json('extra_people')->nullable()->after('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->dropColumn(['sub_category', 'research_type', 'research_level', 'extra_people']);
        });
    }
};