<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('membership_tier', ['bronze', 'silver', 'gold'])
                  ->default('bronze')
                  ->after('status');
            $table->timestamp('tier_upgraded_at')->nullable()->after('membership_tier');
            $table->enum('tier_upgrade_source', ['default', 'organic', 'paid'])->default('default')->after('tier_upgraded_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['membership_tier', 'tier_upgraded_at', 'tier_upgrade_source']);
        });
    }
};