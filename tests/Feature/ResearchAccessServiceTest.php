<?php

namespace Tests\Feature;

use App\Models\Innovation\SellingItem;
use App\Models\Order;
use App\Models\RegisterUsers\User;
use App\Models\Research\Research;
use App\Services\ResearchAccessDecision as Code;
use App\Services\ResearchAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Locks the FREE / LIMITED ACCESS / PAID × tier × 18+ access matrix.
 *
 * Every row of the client's specification has a test here. If a rule changes,
 * a test must change with it — that is the point.
 */
class ResearchAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResearchAccessService $access;

    protected function setUp(): void
    {
        parent::setUp();
        $this->access = app(ResearchAccessService::class);
    }

    private function user(string $tier, ?string $userType = null): User
    {
        return User::create([
            'first_name'      => 'Test',
            'last_name'       => 'User',
            'email'           => uniqid('u') . '@example.test',
            'password'        => 'secret1234',
            'role'            => 'GENERAL_USER',
            'user_type'       => $userType,
            'membership_tier' => $tier,
        ]);
    }

    private function student(string $tier = 'bronze'): User
    {
        return $this->user($tier, ResearchAccessService::SCHOOL_STUDENT);
    }

    private function research(
        string $accessType = Research::ACCESS_FREE,
        bool $isAdult = false,
        ?string $ownerId = null,
        float $price = 1000,
    ): Research {
        return Research::create([
            'user_id'        => $ownerId ?? $this->user('bronze')->id,
            'title'          => 'Paper',
            'abstract'       => 'Abstract',
            'document_url'   => 'research/documents/paper.pdf',
            'category'       => 'Science',
            'research_type'  => 'Journal',
            'research_level' => 'Undergraduate',
            'first_name'     => 'A',
            'last_name'      => 'B',
            'access_type'    => $accessType,
            'is_adult'       => $isAdult,
            'price'          => $accessType === Research::ACCESS_PAID ? $price : null,
            'status'         => 'approved',
        ]);
    }

    /**
     * A PAID paper is only buyable once the publisher lists it on the
     * marketplace — that listing is what the checkout link points at.
     */
    private function listOnMarketplace(Research $research): SellingItem
    {
        return SellingItem::create([
            'user_id'       => $research->user_id,
            'sellable_type' => Research::class,
            'sellable_id'   => $research->id,
            'title'         => $research->title,
            'thumbnail'     => 'research/thumbnails/x.png',
            'category'      => $research->category,
            'is_paid'       => true,
            'price'         => $research->price,
            'type'          => 'digital',
            'status'        => 'active',
        ]);
    }

    private function assertFullAccess(Code $decision): void
    {
        $this->assertTrue($decision->canView, 'expected view access');
        $this->assertTrue($decision->canDownload, 'expected download access');
        $this->assertSame(Code::GRANTED, $decision->code);
        $this->assertSame(Code::ACTION_NONE, $decision->actionRequired);
    }

    public static function allTiers(): array
    {
        return ['bronze' => ['bronze'], 'silver' => ['silver'], 'gold' => ['gold']];
    }

    public static function everyAccessType(): array
    {
        return [
            'free'    => [Research::ACCESS_FREE],
            'limited' => [Research::ACCESS_LIMITED],
            'paid'    => [Research::ACCESS_PAID],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | A. FREE research — every tier reads and downloads
    |--------------------------------------------------------------------------
    */

    /** @dataProvider allTiers */
    public function test_free_research_is_open_to_every_tier(string $tier): void
    {
        $this->assertFullAccess($this->access->decide($this->user($tier), $this->research(Research::ACCESS_FREE)));
    }

    public function test_free_research_is_open_to_school_students(): void
    {
        $this->assertFullAccess($this->access->decide($this->student(), $this->research(Research::ACCESS_FREE)));
    }

    /*
    |--------------------------------------------------------------------------
    | B. LIMITED ACCESS research — Silver and above, plus School Students
    |--------------------------------------------------------------------------
    */

    public function test_bronze_cannot_open_limited_access_research(): void
    {
        $decision = $this->access->decide($this->user('bronze'), $this->research(Research::ACCESS_LIMITED));

        $this->assertFalse($decision->canView);
        $this->assertFalse($decision->canDownload);
        $this->assertSame(Code::UPGRADE_REQUIRED, $decision->code);
        $this->assertSame(Code::ACTION_UPGRADE, $decision->actionRequired);
        $this->assertSame('silver', $decision->requiredTier);
        // Bronze still sees the abstract — that is what sells the upgrade.
        $this->assertTrue($decision->canPreview);
    }

    public function test_silver_opens_limited_access_research(): void
    {
        $this->assertFullAccess($this->access->decide($this->user('silver'), $this->research(Research::ACCESS_LIMITED)));
    }

    public function test_gold_opens_limited_access_research(): void
    {
        $this->assertFullAccess($this->access->decide($this->user('gold'), $this->research(Research::ACCESS_LIMITED)));
    }

    public function test_school_student_opens_limited_access_research_without_upgrading(): void
    {
        $decision = $this->access->decide($this->student(), $this->research(Research::ACCESS_LIMITED));

        $this->assertFullAccess($decision);
        $this->assertTrue($decision->isSchoolStudent);
    }

    /*
    |--------------------------------------------------------------------------
    | C. PAID research
    |--------------------------------------------------------------------------
    */

    public function test_bronze_must_pay_for_paid_research(): void
    {
        $research = $this->research(Research::ACCESS_PAID);
        $this->listOnMarketplace($research);

        $decision = $this->access->decide($this->user('bronze'), $research);

        $this->assertFalse($decision->canView);
        $this->assertFalse($decision->canDownload);
        $this->assertSame(1000.0, $decision->price);
        $this->assertNotNull($decision->sellingItemId, 'the UI needs somewhere to send the buyer');
    }

    public function test_silver_must_pay_for_paid_research(): void
    {
        $research = $this->research(Research::ACCESS_PAID);
        $this->listOnMarketplace($research);

        $this->assertFalse($this->access->decide($this->user('silver'), $research)->canView);
    }

    public function test_school_student_must_pay_for_paid_research(): void
    {
        $research = $this->research(Research::ACCESS_PAID);
        $this->listOnMarketplace($research);

        $decision = $this->access->decide($this->student(), $research);

        $this->assertFalse($decision->canView);
        // eStudents have no membership upsell — purchase is the only path.
        $this->assertSame(Code::ACTION_PURCHASE, $decision->actionRequired);
        $this->assertNull($decision->requiredTier);
    }

    public function test_gold_opens_paid_research_when_the_membership_benefit_is_enabled(): void
    {
        config(['research.gold_unlocks_paid' => true]);

        $research = $this->research(Research::ACCESS_PAID);
        $this->listOnMarketplace($research);

        $this->assertFullAccess($this->access->decide($this->user('gold'), $research));
    }

    /**
     * The client specification's own rule: every tier pays, Gold included.
     * Flipping one config value must be enough to satisfy it.
     */
    public function test_gold_must_pay_when_the_membership_benefit_is_disabled(): void
    {
        config(['research.gold_unlocks_paid' => false]);

        $research = $this->research(Research::ACCESS_PAID);
        $this->listOnMarketplace($research);

        $decision = $this->access->decide($this->user('gold'), $research);

        $this->assertFalse($decision->canView);
        $this->assertSame(Code::ACTION_PURCHASE, $decision->actionRequired);
        $this->assertNull($decision->requiredTier);
    }

    public function test_a_completed_order_unlocks_paid_research_for_any_tier(): void
    {
        config(['research.gold_unlocks_paid' => false]);

        $buyer    = $this->user('bronze');
        $research = $this->research(Research::ACCESS_PAID);
        $item     = $this->listOnMarketplace($research);

        Order::create([
            'order_id_string' => 'ORD-TEST-1',
            'buyer_id'        => $buyer->id,
            'seller_id'       => $research->user_id,
            'selling_item_id' => $item->id,
            'quantity'        => 1,
            'amount'          => 1000,
            'status'          => 'paid',
        ]);

        $decision = $this->access->decide($buyer->fresh(), $research);

        $this->assertFullAccess($decision);
        $this->assertTrue($decision->hasPurchased);
    }

    public function test_a_paid_paper_the_publisher_never_listed_offers_no_broken_checkout(): void
    {
        config(['research.gold_unlocks_paid' => false]);

        $decision = $this->access->decide($this->user('bronze'), $this->research(Research::ACCESS_PAID));

        $this->assertFalse($decision->canView);
        $this->assertSame(Code::NOT_PURCHASABLE, $decision->code);
        $this->assertSame(Code::ACTION_CONTACT_PUBLISHER, $decision->actionRequired);
        $this->assertNull($decision->sellingItemId);
    }

    public function test_paid_research_with_no_price_is_not_a_dead_end(): void
    {
        $research = $this->research(Research::ACCESS_PAID);
        $research->forceFill(['price' => 0])->saveQuietly();

        $this->assertFullAccess($this->access->decide($this->user('bronze'), $research->fresh()));
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Global 18+ override
    |--------------------------------------------------------------------------
    */

    /** @dataProvider everyAccessType */
    public function test_18_plus_blocks_school_students_for_every_access_type(string $accessType): void
    {
        // Gold eStudent — the only blocking factor is the 18+ flag.
        $decision = $this->access->decide($this->student('gold'), $this->research($accessType, isAdult: true));

        $this->assertFalse($decision->canView);
        $this->assertFalse($decision->canDownload);
        $this->assertFalse($decision->canPreview);
        $this->assertSame(Code::AGE_RESTRICTED, $decision->code);
        $this->assertSame(Code::ACTION_BLOCKED, $decision->actionRequired);
        $this->assertNull($decision->requiredTier, 'no upsell should be offered for age-restricted content');
        $this->assertTrue($decision->shouldHideFromListings());
    }

    /** @dataProvider allTiers */
    public function test_18_plus_does_not_restrict_non_students(string $tier): void
    {
        $this->assertFullAccess(
            $this->access->decide($this->user($tier), $this->research(Research::ACCESS_FREE, isAdult: true))
        );
    }

    public function test_18_plus_blocks_a_school_student_who_owns_the_paper(): void
    {
        $student  = $this->student('gold');
        $research = $this->research(Research::ACCESS_FREE, isAdult: true, ownerId: $student->id);

        $decision = $this->access->decide($student, $research);

        $this->assertFalse($decision->canView);
        $this->assertSame(Code::AGE_RESTRICTED, $decision->code);
    }

    /*
    |--------------------------------------------------------------------------
    | Edge cases
    |--------------------------------------------------------------------------
    */

    public function test_guest_must_sign_in(): void
    {
        $decision = $this->access->decide(null, $this->research(Research::ACCESS_LIMITED));

        $this->assertFalse($decision->canView);
        $this->assertSame(Code::LOGIN_REQUIRED, $decision->code);
        $this->assertSame(Code::ACTION_LOGIN, $decision->actionRequired);
        $this->assertSame(401, $decision->httpStatus());
    }

    public function test_owner_always_reads_their_own_paid_paper(): void
    {
        $owner    = $this->user('bronze');
        $research = $this->research(Research::ACCESS_PAID, ownerId: $owner->id);

        $decision = $this->access->decide($owner, $research);

        $this->assertFullAccess($decision);
        $this->assertTrue($decision->isOwner);
    }

    public function test_admin_reads_everything(): void
    {
        $admin = $this->user('bronze');
        $admin->forceFill(['role' => 'super_admin'])->save();

        $decision = $this->access->decide($admin->fresh(), $this->research(Research::ACCESS_PAID));

        $this->assertFullAccess($decision);
        $this->assertTrue($decision->isAdmin);
    }

    public function test_legacy_rows_without_an_access_type_fall_back_to_the_is_paid_boolean(): void
    {
        $research = $this->research(Research::ACCESS_LIMITED);
        // Simulate a row whose access_type never got a meaningful value.
        $research->forceFill(['access_type' => '', 'is_paid' => true])->saveQuietly();

        $this->assertSame(Research::ACCESS_PAID, $this->access->accessType($research->fresh()));
    }

    public function test_saving_keeps_is_paid_in_step_with_access_type(): void
    {
        $research = $this->research(Research::ACCESS_PAID);
        $this->assertTrue($research->is_paid);

        $research->access_type = Research::ACCESS_FREE;
        $research->save();

        $this->assertFalse($research->fresh()->is_paid);
        $this->assertNull($research->fresh()->price);
    }

    public function test_decision_payload_carries_the_specified_contract(): void
    {
        $payload = $this->access
            ->decide($this->user('bronze'), $this->research(Research::ACCESS_LIMITED))
            ->toArray();

        $this->assertArrayHasKey('can_view', $payload);
        $this->assertArrayHasKey('can_download', $payload);
        $this->assertArrayHasKey('message', $payload);
        $this->assertArrayHasKey('action_required', $payload);
    }
}
