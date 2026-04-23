<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('research_views', 'ip_address')) {
            Schema::table('research_views', function (Blueprint $table) {
                $table->dropColumn('ip_address');
            });
        }

        if (Schema::hasColumn('innovation_views', 'ip_address')) {
            Schema::table('innovation_views', function (Blueprint $table) {
                $table->dropColumn('ip_address');
            });
        }
    }

    public function down(): void
    {
        Schema::table('research_views', function (Blueprint $table) {
            $table->string('ip_address')->nullable();
        });

        Schema::table('innovation_views', function (Blueprint $table) {
            $table->string('ip_address')->nullable();
        });
    }
};
