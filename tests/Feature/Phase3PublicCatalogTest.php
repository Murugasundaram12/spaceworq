<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\City;
use App\Models\Provider;
use App\Models\ProviderBankAccount;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceListingConfig;
use App\Models\WorkspaceSubcategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_search_filters_and_hides_unapproved_or_private_provider_data(): void
    {
        $owner = User::factory()->create([
            'user_type' => User::TYPE_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $city = City::where('slug', 'bengaluru')->firstOrFail();
        $catDayPass = WorkspaceCategory::where('code', 'DAY_PASS')->firstOrFail();
        $subHotDesk = WorkspaceSubcategory::where('code', 'HOT_DESK_DAY_PASS')->firstOrFail();
        $wifi = Amenity::where('slug', 'high-speed-wifi')->firstOrFail();

        $verifiedProvider = Provider::create([
            'owner_user_id' => $owner->id,
            'company_name' => 'Verified Operator Pvt Ltd',
            'slug' => 'verified-operator',
            'brand_name' => 'VerifiedSpaces',
            'business_email' => 'secret@operator.in',
            'business_phone' => '9800000001',
            'gstin' => '29ABCDE1234F1Z5',
            'pan' => 'ABCDE1234F',
            'city_id' => $city->id,
            'status' => Provider::STATUS_ACTIVE,
            'kyc_status' => Provider::KYC_VERIFIED,
            'verified_at' => now(),
        ]);

        ProviderBankAccount::create([
            'provider_id' => $verifiedProvider->id,
            'account_holder_name' => 'Verified Operator Pvt Ltd',
            'bank_name' => 'HDFC Bank',
            'account_number_encrypted' => '123456789012',
            'account_last4' => '9012',
            'ifsc_code' => 'HDFC0000123',
        ]);

        $published = Workspace::create([
            'provider_id' => $verifiedProvider->id,
            'code' => 'DP-BLR0001',
            'slug' => 'indiranagar-hub',
            'name' => 'Indiranagar Hub',
            'category_id' => $catDayPass->id,
            'subcategory_id' => $subHotDesk->id,
            'city_id' => $city->id,
            'address_line' => '100ft Road, Indiranagar',
            'landmark' => 'Indiranagar Metro',
            'total_floors' => 2,
            'total_seats' => 40,
            'available_seats' => 20,
            'status' => Workspace::STATUS_ACTIVE,
            'approval_status' => Workspace::APPROVAL_APPROVED,
            'is_published' => true,
            'is_instant_bookable' => true,
            'created_by' => $owner->id,
        ]);
        $published->amenities()->attach([$wifi->id]);
        WorkspaceListingConfig::create([
            'workspace_id' => $published->id,
            'subcategory_id' => $subHotDesk->id,
            'price_per_day' => 499.00,
        ]);

        // Unapproved workspace must stay hidden
        $draft = Workspace::create([
            'provider_id' => $verifiedProvider->id,
            'code' => 'DP-BLR0002',
            'slug' => 'draft-hub',
            'name' => 'Secret Draft Hub',
            'category_id' => $catDayPass->id,
            'subcategory_id' => $subHotDesk->id,
            'city_id' => $city->id,
            'address_line' => 'Hidden Road',
            'total_floors' => 1,
            'total_seats' => 10,
            'available_seats' => 10,
            'status' => Workspace::STATUS_IN_REVIEW,
            'approval_status' => Workspace::APPROVAL_PENDING,
            'is_published' => false,
            'created_by' => $owner->id,
        ]);

        $res = $this->getJson('/api/v1/workspaces/published?city=bengaluru&category=DAY_PASS&amenities[]=high-speed-wifi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Indiranagar Hub');

        $payloadString = json_encode($res->json());
        $this->assertStringNotContainsString('secret@operator.in', $payloadString);
        $this->assertStringNotContainsString('29ABCDE1234F1Z5', $payloadString);
        $this->assertStringNotContainsString('123456789012', $payloadString);
        $this->assertStringNotContainsString('ABCDE1234F', $payloadString);

        $this->getJson("/api/v1/workspaces/published/{$published->id}")->assertOk();
        $this->getJson("/api/v1/workspaces/published/{$draft->id}")->assertNotFound();
    }

    public function test_consultation_and_partner_inquiry_submission_and_reviews(): void
    {
        $this->postJson('/api/v1/consultations', [
            'name' => 'Priya Iyer',
            'company_name' => 'Acme Tech India',
            'phone' => '+91 98765 43210',
            'city_name' => 'Bengaluru',
            'seats_range' => '25-50',
        ])->assertCreated();

        $this->postJson('/api/v1/partner-inquiries', [
            'inquiry_type' => 'BUILDING_OWNER',
            'entity_name' => 'Prestige Tech Park - Block C',
            'city_name' => 'Bengaluru',
            'locality' => 'Outer Ring Road',
            'total_area_sqft' => 35000,
            'floor_plate_sqft' => 12000,
            'partnership_model' => 'Straight Lease',
            'indicative_rent' => '85',
            'contact_name' => 'Ramesh Kulkarni',
            'contact_phone' => '9876543210',
            'contact_email' => 'owner@assetgroup.com',
        ])->assertCreated();

        $this->assertDatabaseHas('consultations', ['company_name' => 'Acme Tech India']);
        $this->assertDatabaseHas('partner_inquiries', ['entity_name' => 'Prestige Tech Park - Block C']);
    }
}
