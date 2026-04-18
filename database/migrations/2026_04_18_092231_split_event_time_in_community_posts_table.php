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
        Schema::table('community_posts', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('end_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->dropColumn('event_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->string('event_time')->nullable()->after('end_date');
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
