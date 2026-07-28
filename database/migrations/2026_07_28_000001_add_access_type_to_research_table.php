<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the is_paid boolean with a three-way access type:
 * free | limited | paid.
 *
 * is_paid is kept (and kept in sync by the Research model) because ~20 call
 * sites — listing filters, the marketplace, admin screens and the API
 * resources — still read it as "does this paper cost money".
 *
 * Backfill keeps every existing paper behaving exactly as it does today:
 * the old "free" papers already required Silver, which is precisely what
 * LIMITED ACCESS means, so is_paid=false maps to `limited`, not `free`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->string('access_type', 20)
                ->default('limited')
                ->after('is_paid')
                ->index();
        });

        DB::table('research')
            ->where('is_paid', true)
            ->update(['access_type' => 'paid']);

        DB::table('research')
            ->where('is_paid', false)
            ->orWhereNull('is_paid')
            ->update(['access_type' => 'limited']);
    }

    public function down(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->dropIndex(['access_type']);
            $table->dropColumn('access_type');
        });
    }
};
