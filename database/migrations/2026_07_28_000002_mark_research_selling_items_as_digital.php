<?php

use App\Models\Research\Research;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Research papers listed on the marketplace before delivery_type was set
 * carry the 'physical' default, which makes checkout demand a shipping
 * address from the buyer for a PDF download.
 *
 * Nothing about a research paper is ever shipped, so they are all digital.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('selling_items')
            ->where('sellable_type', Research::class)
            ->update(['delivery_type' => 'digital']);
    }

    public function down(): void
    {
        DB::table('selling_items')
            ->where('sellable_type', Research::class)
            ->update(['delivery_type' => 'physical']);
    }
};
