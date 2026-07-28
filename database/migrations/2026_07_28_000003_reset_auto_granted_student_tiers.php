<?php

use App\Services\ResearchAccessService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * School Students used to be handed Gold at registration. Combined with the
 * Gold-unlocks-paid-research benefit that made every paid paper free for every
 * student — the opposite of what the specification asks for.
 *
 * Registration now starts everyone at Bronze; this resets the accounts that
 * were created under the old rule.
 *
 * Students who actually *paid* for an upgrade keep it. Their eStudent benefits
 * (free + limited access research at any tier) come from user_type and are
 * unaffected by the tier reset.
 */
return new class extends Migration
{
    public function up(): void
    {
        $paidUpgradeUserIds = DB::table('membership_payments')
            ->where('status', 'paid')
            ->pluck('user_id');

        DB::table('users')
            ->where('user_type', ResearchAccessService::SCHOOL_STUDENT)
            ->whereIn('membership_tier', ['silver', 'gold'])
            ->whereNotIn('id', $paidUpgradeUserIds)
            ->update(['membership_tier' => 'bronze']);
    }

    public function down(): void
    {
        // Deliberately irreversible: restoring the old auto-Gold would hand
        // students back free access to paid research.
    }
};
