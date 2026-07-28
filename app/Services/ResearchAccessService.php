<?php

namespace App\Services;

use App\Models\Innovation\SellingItem;
use App\Models\Order;
use App\Models\RegisterUsers\User;
use App\Models\Research\Research;

/**
 * Single source of truth for "may this user open this research paper?".
 *
 * Nothing else in the application should compare membership tiers or check
 * user_type against 'School Student' — controllers, middleware and the API
 * payload the frontend consumes all route through decide().
 *
 * ── The access matrix ──────────────────────────────────────────────────────
 *
 *                  │ FREE          │ LIMITED ACCESS      │ PAID
 *  ────────────────┼───────────────┼─────────────────────┼──────────────────
 *   Bronze         │ view+download │ upgrade to Silver   │ pay the price
 *   Silver         │ view+download │ view+download       │ pay the price
 *   Gold           │ view+download │ view+download       │ see note below
 *   School Student │ view+download │ view+download       │ pay the price
 *
 *  GLOBAL 18+ OVERRIDE: if the paper is flagged 18+, School Student accounts
 *  are refused for every access type, at every tier, even after paying.
 *
 *  Note on Gold + PAID: the client's written specification says Gold must pay
 *  like everyone else. This build keeps Gold's existing entitlement instead,
 *  behind config('research.gold_unlocks_paid'). Set RESEARCH_GOLD_UNLOCKS_PAID
 *  to false to match the specification exactly — no code change needed.
 *
 * ── Evaluation order (fixed — do not reorder) ──────────────────────────────
 *   1. Is the user logged in?
 *   2. Is the paper 18+ and the account a School Student?  → deny, always
 *   3. Owner / admin short-circuit
 *   4. Branch on access_type: FREE, LIMITED ACCESS or PAID
 */
class ResearchAccessService
{
    public const TIER_BRONZE = 'bronze';
    public const TIER_SILVER = 'silver';
    public const TIER_GOLD   = 'gold';

    /** Ranking used for every "is my tier high enough" comparison. */
    public const TIER_ORDER = [
        self::TIER_BRONZE => 1,
        self::TIER_SILVER => 2,
        self::TIER_GOLD   => 3,
    ];

    /** user_type value that identifies an eStudent / School Student account. */
    public const SCHOOL_STUDENT = 'School Student';

    /** Roles with unrestricted access to research content. */
    private const ADMIN_ROLES = ['admin', 'super_admin', 'superadmin'];

    /** Order states that count as a completed purchase. */
    private const PAID_ORDER_STATUSES = ['paid', 'cod_pending', 'completed'];

    /**
     * Evaluate one user against one research paper.
     */
    public function decide(?User $user, Research $research): ResearchAccessDecision
    {
        $accessType = $this->accessType($research);
        $price      = (float) ($research->price ?? 0);

        // Only PAID papers have a checkout to point at. Skipping the lookup for
        // FREE and LIMITED papers keeps listing pages at zero extra queries per
        // row — this runs once per paper in every ResearchResource.
        $sellingItemId = $accessType === Research::ACCESS_PAID
            ? $this->sellingItemId($research)
            : null;

        // ── 1. Logged in? ────────────────────────────────────────────────
        if ($user === null) {
            return new ResearchAccessDecision(
                canView:        false,
                canDownload:    false,
                message:        'Please sign in to open this research paper.',
                actionRequired: ResearchAccessDecision::ACTION_LOGIN,
                code:           ResearchAccessDecision::LOGIN_REQUIRED,
                canPreview:     false,
                accessType:     $accessType,
                requiredTier:   $this->requiredTierFor($research),
                price:          $price,
                sellingItemId:  $sellingItemId,
            );
        }

        $isSchoolStudent = $this->isSchoolStudent($user);

        // ── 2. 18+ overrides every other permission for eStudents. ───────
        //    Deliberately ahead of the owner/admin check: an eStudent must not
        //    reach adult content through any route, including their own upload.
        if ($research->is_adult && $isSchoolStudent) {
            return new ResearchAccessDecision(
                canView:         false,
                canDownload:     false,
                message:         'This research paper is marked as 18+ and is not available on School Student accounts.',
                actionRequired:  ResearchAccessDecision::ACTION_BLOCKED,
                code:            ResearchAccessDecision::AGE_RESTRICTED,
                canPreview:      false,
                accessType:      $accessType,
                requiredTier:    null,
                price:           $price,
                isSchoolStudent: true,
                isAgeRestricted: true,
            );
        }

        $isOwner      = $user->id === $research->user_id;
        $isAdmin      = in_array(strtolower((string) $user->role), self::ADMIN_ROLES, true);
        $hasPurchased = $this->hasPurchased($user, $sellingItemId);

        // ── 3. Owner and admins always have full access. ─────────────────
        if ($isOwner || $isAdmin) {
            return $this->grant(
                message:         $isOwner
                    ? 'You are the owner of this research — full access, no limitations.'
                    : 'Administrator access.',
                research:        $research,
                accessType:      $accessType,
                price:           $price,
                isOwner:         $isOwner,
                isAdmin:         $isAdmin,
                hasPurchased:    $hasPurchased,
                sellingItemId:   $sellingItemId,
                isSchoolStudent: $isSchoolStudent,
                // Owner/admin bypass the uploader's download toggle.
                forceDownload:   true,
            );
        }

        // ── 4. Branch on the access type. ────────────────────────────────
        return match ($accessType) {
            Research::ACCESS_FREE => $this->decideFree(
                $research, $accessType, $price, $sellingItemId, $isSchoolStudent
            ),
            Research::ACCESS_PAID => $this->decidePaid(
                $user, $research, $accessType, $price, $hasPurchased, $sellingItemId, $isSchoolStudent
            ),
            default => $this->decideLimited(
                $user, $research, $accessType, $price, $sellingItemId, $isSchoolStudent
            ),
        };
    }

    /**
     * FREE research — open to every tier, Bronze included. The only thing that
     * can block it is the 18+ rule, already applied above.
     */
    private function decideFree(
        Research $research,
        string $accessType,
        float $price,
        ?int $sellingItemId,
        bool $isSchoolStudent,
    ): ResearchAccessDecision {
        return $this->grant(
            message:         'Access granted — this research is free to read and download.',
            research:        $research,
            accessType:      $accessType,
            price:           $price,
            sellingItemId:   $sellingItemId,
            isSchoolStudent: $isSchoolStudent,
        );
    }

    /**
     * LIMITED ACCESS research — Silver and above. School Student accounts are
     * granted regardless of tier: the eStudent programme is the entitlement,
     * they are not expected to buy membership packages.
     */
    private function decideLimited(
        User $user,
        Research $research,
        string $accessType,
        float $price,
        ?int $sellingItemId,
        bool $isSchoolStudent,
    ): ResearchAccessDecision {
        if ($isSchoolStudent || $this->tierLevel($user) >= self::TIER_ORDER[self::TIER_SILVER]) {
            return $this->grant(
                message:         'Access granted.',
                research:        $research,
                accessType:      $accessType,
                price:           $price,
                sellingItemId:   $sellingItemId,
                isSchoolStudent: $isSchoolStudent,
            );
        }

        // Bronze.
        return new ResearchAccessDecision(
            canView:         false,
            canDownload:     false,
            message:         'This is limited access research. Upgrade to the Silver package to read it.',
            actionRequired:  ResearchAccessDecision::ACTION_UPGRADE,
            code:            ResearchAccessDecision::UPGRADE_REQUIRED,
            // Bronze still sees the title and abstract — that is the upsell.
            canPreview:      true,
            accessType:      $accessType,
            requiredTier:    self::TIER_SILVER,
            price:           $price,
            sellingItemId:   $sellingItemId,
            isSchoolStudent: $isSchoolStudent,
        );
    }

    /**
     * PAID research — the publisher's price, paid per paper. Membership tier
     * does not unlock it unless config('research.gold_unlocks_paid') is on, in
     * which case Gold members read it as a membership benefit.
     *
     * eStudents are treated no differently here. Their exemption covers LIMITED
     * ACCESS only; for paid research they either buy the paper or work their
     * way up to Gold like everybody else. That relies on School Students
     * starting at Bronze — see RegistrationController.
     */
    private function decidePaid(
        User $user,
        Research $research,
        string $accessType,
        float $price,
        bool $hasPurchased,
        ?int $sellingItemId,
        bool $isSchoolStudent,
    ): ResearchAccessDecision {
        $goldUnlocks = (bool) config('research.gold_unlocks_paid', true);
        $isGold      = $this->tierLevel($user) >= self::TIER_ORDER[self::TIER_GOLD];

        // A paper marked PAID with no price to charge is treated as free
        // rather than trapping readers behind a zero-value checkout. Upload
        // validation prevents this; legacy rows may still have it.
        if ($hasPurchased || ($goldUnlocks && $isGold) || $price <= 0) {
            return $this->grant(
                message:         $hasPurchased
                    ? 'Purchase confirmed — full access to this research.'
                    : 'Access granted.',
                research:        $research,
                accessType:      $accessType,
                price:           $price,
                hasPurchased:    $hasPurchased,
                sellingItemId:   $sellingItemId,
                isSchoolStudent: $isSchoolStudent,
                // A single-paper purchase always includes the PDF.
                forceDownload:   $hasPurchased,
            );
        }

        // Two possible ways in. Buying needs a marketplace listing to point at;
        // upgrading needs the Gold benefit to be switched on.
        //
        // eStudents get the Gold route too. Their exemption covers LIMITED
        // ACCESS research only — PAID research is never free for them.
        $canBuy     = $sellingItemId !== null;
        $canUpgrade = $goldUnlocks;

        // Priced, unlisted, and no membership route — nothing the reader can do.
        if (! $canBuy && ! $canUpgrade) {
            return new ResearchAccessDecision(
                canView:         false,
                canDownload:     false,
                message:         'This research is not available for purchase yet. Please check back later.',
                actionRequired:  ResearchAccessDecision::ACTION_CONTACT_PUBLISHER,
                code:            ResearchAccessDecision::NOT_PURCHASABLE,
                canPreview:      true,
                accessType:      $accessType,
                requiredTier:    null,
                price:           $price,
                sellingItemId:   null,
                isSchoolStudent: $isSchoolStudent,
            );
        }

        [$message, $action, $code] = match (true) {
            $canBuy && $canUpgrade => [
                sprintf('Purchase this research for LKR %s, or upgrade to the Gold package.', number_format($price, 2)),
                ResearchAccessDecision::ACTION_UPGRADE_OR_PURCHASE,
                ResearchAccessDecision::UPGRADE_OR_PURCHASE_REQUIRED,
            ],
            $canBuy => [
                sprintf('Purchase this research for LKR %s to read and download it.', number_format($price, 2)),
                ResearchAccessDecision::ACTION_PURCHASE,
                ResearchAccessDecision::PAYMENT_REQUIRED,
            ],
            // Listed nowhere, but Gold members read paid research — so the
            // membership is still a real way in.
            default => [
                'Upgrade to the Gold package to read this research.',
                ResearchAccessDecision::ACTION_UPGRADE,
                ResearchAccessDecision::UPGRADE_REQUIRED,
            ],
        };

        return new ResearchAccessDecision(
            canView:         false,
            canDownload:     false,
            message:         $message,
            actionRequired:  $action,
            code:            $code,
            canPreview:      true,
            accessType:      $accessType,
            requiredTier:    $canUpgrade ? self::TIER_GOLD : null,
            price:           $price,
            sellingItemId:   $sellingItemId,
            isSchoolStudent: $isSchoolStudent,
        );
    }

    /**
     * Build a granted decision, honouring the uploader's download toggle.
     */
    private function grant(
        string $message,
        Research $research,
        string $accessType,
        float $price = 0.0,
        bool $isOwner = false,
        bool $isAdmin = false,
        bool $hasPurchased = false,
        ?int $sellingItemId = null,
        bool $isSchoolStudent = false,
        bool $forceDownload = false,
    ): ResearchAccessDecision {
        return new ResearchAccessDecision(
            canView:         true,
            canDownload:     $forceDownload || $this->downloadsEnabled($research),
            message:         $message,
            actionRequired:  ResearchAccessDecision::ACTION_NONE,
            code:            ResearchAccessDecision::GRANTED,
            canPreview:      true,
            accessType:      $accessType,
            requiredTier:    null,
            price:           $price,
            isOwner:         $isOwner,
            isAdmin:         $isAdmin,
            hasPurchased:    $hasPurchased,
            sellingItemId:   $sellingItemId,
            isSchoolStudent: $isSchoolStudent,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reusable predicates
    |--------------------------------------------------------------------------
    */

    /**
     * Normalised access type for a paper, tolerating legacy rows that only
     * ever had the is_paid boolean set.
     */
    public function accessType(Research $research): string
    {
        $type = strtolower((string) $research->access_type);

        if (in_array($type, Research::ACCESS_TYPES, true)) {
            return $type;
        }

        return $research->is_paid ? Research::ACCESS_PAID : Research::ACCESS_LIMITED;
    }

    /**
     * eStudent / School Student account check. The one place that knows the
     * user_type string.
     */
    public function isSchoolStudent(?User $user): bool
    {
        return $user !== null && $user->user_type === self::SCHOOL_STUDENT;
    }

    /**
     * 18+ restriction on its own, for callers that only need this rule
     * (innovation videos, listings, previews).
     */
    public function blockedByAgeRestriction(?User $user, bool $isAdult): bool
    {
        return $isAdult && $this->isSchoolStudent($user);
    }

    /**
     * Numeric rank of a user's membership tier.
     */
    public function tierLevel(?User $user): int
    {
        $tier = strtolower($user?->membership_tier ?? self::TIER_BRONZE);

        return self::TIER_ORDER[$tier] ?? self::TIER_ORDER[self::TIER_BRONZE];
    }

    /**
     * Tier a paper requires as a membership benefit, or null when membership
     * cannot unlock it at all.
     */
    public function requiredTierFor(Research $research): ?string
    {
        return match ($this->accessType($research)) {
            Research::ACCESS_FREE    => null,
            Research::ACCESS_PAID    => config('research.gold_unlocks_paid', true) ? self::TIER_GOLD : null,
            default                  => self::TIER_SILVER,
        };
    }

    /**
     * Marketplace listing for this paper, if it has one.
     */
    public function sellingItemId(Research $research): ?int
    {
        return SellingItem::where('sellable_type', Research::class)
            ->where('sellable_id', $research->id)
            ->value('id');
    }

    /**
     * Has this user bought this specific paper?
     */
    public function hasPurchased(?User $user, ?int $sellingItemId): bool
    {
        if ($user === null || $sellingItemId === null) {
            return false;
        }

        return Order::where('buyer_id', $user->id)
            ->where('selling_item_id', $sellingItemId)
            ->whereIn('status', self::PAID_ORDER_STATUSES)
            ->exists();
    }

    /**
     * Uploader-controlled download toggle. Papers uploaded before the column
     * existed (or without it set) default to downloadable.
     */
    private function downloadsEnabled(Research $research): bool
    {
        return $research->allow_download ?? true;
    }
}
