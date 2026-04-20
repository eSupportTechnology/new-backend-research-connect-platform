<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('innovations', function (Blueprint $table) {
            $table->tinyInteger('revision_count')->unsigned()->default(0)->after('status');
            $table->text('revision_reason')->nullable()->after('revision_count');
            $table->boolean('is_blocked')->default(false)->after('revision_reason');
        });

        Schema::table('research', function (Blueprint $table) {
            $table->tinyInteger('revision_count')->unsigned()->default(0)->after('status');
            $table->text('revision_reason')->nullable()->after('revision_count');
            $table->boolean('is_blocked')->default(false)->after('revision_reason');
        });

        // Extend innovation status enum
        DB::statement("ALTER TABLE innovations MODIFY COLUMN status ENUM('active','inactive','revision_requested','permanently_rejected') NOT NULL DEFAULT 'active'");

        // Extend research status enum
        DB::statement("ALTER TABLE research MODIFY COLUMN status ENUM('pending','approved','rejected','revision_requested','permanently_rejected') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE innovations MODIFY COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
        DB::statement("ALTER TABLE research MODIFY COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");

        Schema::table('innovations', function (Blueprint $table) {
            $table->dropColumn(['revision_count', 'revision_reason', 'is_blocked']);
        });

        Schema::table('research', function (Blueprint $table) {
            $table->dropColumn(['revision_count', 'revision_reason', 'is_blocked']);
        });
    }
};