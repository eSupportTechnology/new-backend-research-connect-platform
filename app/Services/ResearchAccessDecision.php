<?php

namespace App\Services;

/**
 * The result of a single research access evaluation.
 *
 * Immutable value object produced by ResearchAccessService::decide(). Every
 * consumer (controllers, middleware, API payloads) reads the same object, so
 * the permission rules never get re-implemented per call site.
 */
class ResearchAccessDecision
{
    /*
    |--------------------------------------------------------------------------
    | Reason codes — why the decision came out the way it did
    |--------------------------------------------------------------------------
    */

    /** Access granted. */
    public const GRANTED = 'GRANTED';

    /** Guest — must sign in before any research can be opened. */
    public const LOGIN_REQUIRED = 'LOGIN_REQUIRED';

    /** 18+ paper on a School Student account. Overrides everything else. */
    public const AGE_RESTRICTED = 'AGE_RESTRICTED';

    /** LIMITED ACCESS paper, membership too low. Only a tier upgrade unlocks it. */
    public const UPGRADE_REQUIRED = 'UPGRADE_REQUIRED';

    /** PAID paper — the publisher's price must be paid. */
    public const PAYMENT_REQUIRED = 'PAYMENT_REQUIRED';

    /** PAID paper — either buy it, or take the membership that includes it. */
    public const UPGRADE_OR_PURCHASE_REQUIRED = 'UPGRADE_OR_PURCHASE_REQUIRED';

    /** PAID paper with no marketplace listing — nobody can buy it yet. */
    public const NOT_PURCHASABLE = 'NOT_PURCHASABLE';

    /** Uploader disabled downloads for this paper (viewing still allowed). */
    public const DOWNLOAD_DISABLED = 'DOWNLOAD_DISABLED';

    /*
    |--------------------------------------------------------------------------
    | Actions — what the user must actually DO next
    |--------------------------------------------------------------------------
    |
    | The UI switches on these rather than on the reason code, so new reason
    | codes never force a frontend change.
    |
    */

    /** Nothing to do — the paper is open. */
    public const ACTION_NONE = 'none';

    /** Sign in / register. */
    public const ACTION_LOGIN = 'login';

    /** Buy a membership package. */
    public const ACTION_UPGRADE = 'upgrade_membership';

    /** Buy this single paper. */
    public const ACTION_PURCHASE = 'purchase_research';

    /** Either of the two above unlocks it. */
    public const ACTION_UPGRADE_OR_PURCHASE = 'upgrade_or_purchase';

    /** Nothing the user can do will unlock it (18+ on a School Student). */
    public const ACTION_BLOCKED = 'blocked';

    /** Waiting on the publisher / admin, not on the user. */
    public const ACTION_CONTACT_PUBLISHER = 'contact_publisher';

    public function __construct(
        public readonly bool    $canView,
        public readonly bool    $canDownload,
        public readonly string  $message,
        public readonly string  $actionRequired,
        public readonly string  $code,
        public readonly bool    $canPreview = false,
        public readonly ?string $accessType = null,
        public readonly ?string $requiredTier = null,
        public readonly float   $price = 0.0,
        public readonly bool    $isOwner = false,
        public readonly bool    $isAdmin = false,
        public readonly bool    $hasPurchased = false,
        public readonly ?int    $sellingItemId = null,
        public readonly bool    $isSchoolStudent = false,
        public readonly bool    $isAgeRestricted = false,
    ) {
    }

    public function allowed(): bool
    {
        return $this->canView;
    }

    public function denied(): bool
    {
        return ! $this->canView;
    }

    /**
     * Should this paper be hidden from listings and search entirely, rather
     * than shown with a lock on it?
     *
     * The 18+ rule is "Not Visible", so an eStudent must never even see the
     * title. Every other denial is a locked-but-visible upsell.
     */
    public function shouldHideFromListings(): bool
    {
        return $this->isAgeRestricted;
    }

    /**
     * HTTP status that best represents this decision.
     */
    public function httpStatus(): int
    {
        return $this->code === self::LOGIN_REQUIRED ? 401 : 403;
    }

    /**
     * Shape handed to the frontend so the UI never re-derives the rules.
     *
     * The first four keys are the contract the business specification asks
     * for; everything after them is what the UI needs to render the right
     * call-to-action.
     */
    public function toArray(): array
    {
        return [
            'can_view'          => $this->canView,
            'can_download'      => $this->canDownload,
            'message'           => $this->message,
            'action_required'   => $this->actionRequired,

            'code'              => $this->code,
            'can_preview'       => $this->canPreview,
            'access_type'       => $this->accessType,
            'required_tier'     => $this->requiredTier,
            'price'             => $this->price,
            'is_owner'          => $this->isOwner,
            'is_admin'          => $this->isAdmin,
            'has_purchased'     => $this->hasPurchased,
            'selling_item_id'   => $this->sellingItemId,
            'is_school_student' => $this->isSchoolStudent,
            'is_age_restricted' => $this->isAgeRestricted,
        ];
    }

    /**
     * Error body returned when a request is refused.
     */
    public function toErrorResponse(): array
    {
        // 18+ on an eStudent account is "Not Visible": the listing queries drop
        // it (see shouldHideFromListings), and a direct hit gets an explicit
        // refusal with no upsell attached — nothing unlocks it.
        return [
            'success'         => false,
            'code'            => $this->code,
            'message'         => $this->message,
            'action_required' => $this->actionRequired,
            'required_tier'   => $this->requiredTier,
            'price'           => $this->price,
            'selling_item_id' => $this->sellingItemId,
        ];
    }
}
