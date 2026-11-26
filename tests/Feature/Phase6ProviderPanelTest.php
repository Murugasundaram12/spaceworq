<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Provider;
use App\Models\ProviderBankAccount;
use App\Models\ProviderRole;
use App\Models\ProviderUser;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceSubcategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase6ProviderPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;

    private Provider $providerA;

    private User $ownerB;

    private Provider $providerB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $city = City::where('slug', 'bengaluru')->firstOrFail();

        $this->ownerA = User::factory()->create([
            'user_type' => User::TYPE_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $this->providerA = Provider::create([
            'owner_user_id' => $this->ownerA->id,
            'company_name' => 'Alpha Workspaces Pvt Ltd',
            'slug' => 'alpha-workspaces',
            'brand_name' => 'AlphaWorq',
            'business_email' => 'ops@alphaworq.test',
            'business_phone' => '9800000201',
            'city_id' => $city->id,
            'status' => Provider::STATUS_ACTIVE,
            'kyc_status' => Provider::KYC_VERIFIED,
            'verified_at' => now(),
        ]);

        $roleA = ProviderRole::create([
            'provider_id' => $this->providerA->id,
            'name' => 'Provider Owner',
            'permissions' => ProviderRole::CANONICAL_PERMISSIONS,
            'is_system' => true,
        ]);

        ProviderUser::create([
            'provider_id' => $this->providerA->id,
            'user_id' => $this->ownerA->id,
            'provider_role_id' => $roleA->id,
            'role_name' => 'Provider Owner',
            'is_owner' => true,
            'status' => ProviderUser::STATUS_ACTIVE,
        ]);

        $this->ownerB = User::factory()->create([
            'user_type' => User::TYPE_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $this->providerB = Provider::create([
            'owner_user_id' => $this->ownerB->id,
            'company_name' => 'Beta Workspaces Pvt Ltd',
            'slug' => 'beta-workspaces',
            'brand_name' => 'BetaWorq',
            'business_email' => 'ops@betaworq.test',
            'business_phone' => '9800000202',
            'city_id' => $city->id,
            'status' => Provider::STATUS_ACTIVE,
            'kyc_status' => Provider::KYC_VERIFIED,
            'verified_at' => now(),
        ]);

        $roleB = ProviderRole::create([
            'provider_id' => $this->providerB->id,
            'name' => 'Provider Owner',
            'permissions' => ProviderRole::CANONICAL_PERMISSIONS,
            'is_system' => true,
        ]);

        ProviderUser::create([
            'provider_id' => $this->providerB->id,
            'user_id' => $this->ownerB->id,
            'provider_role_id' => $roleB->id,
            'role_name' => 'Provider Owner',
            'is_owner' => true,
            'status' => ProviderUser::STATUS_ACTIVE,
        ]);
    }

    public function test_provider_workspace_crud_image_cap_xss_sanitization_and_tenant_isolation(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->ownerA);

        $city = City::where('slug', 'bengaluru')->firstOrFail();
        $cat = WorkspaceCategory::where('code', WorkspaceCategory::CODE_DAY_PASS)->firstOrFail();
        $sub = WorkspaceSubcategory::where('code', 'HOT_DESK_DAY_PASS')->firstOrFail();

        $wsRes = $this->postJson('/api/v1/provider/workspaces', [
            'name' => 'Alpha Indiranagar Hub',
            'category_id' => $cat->id,
            'subcategory_id' => $sub->id,
            'city_id' => $city->id,
            'address_line' => '100ft Road, Indiranagar',
            'description_html' => '<p>Great hub</p><script>alert("xss")</script>',
            'total_floors' => 2,
            'total_seats' => 50,
            'available_seats' => 50,
            'is_instant_bookable' => true,
            'pricing' => [
                'price_per_day' => 599,
            ],
        ]);

        $wsRes->assertCreated()
            ->assertJsonPath('data.status', Workspace::STATUS_DRAFT)
            ->assertJsonPath('data.is_published', false);

        $this->assertStringNotContainsString('<script>', (string) $wsRes->json('data.description_html'));

        $workspaceId = $wsRes->json('data.id');

        // Provider cannot self-publish / force ACTIVE before admin approval
        $this->patchJson("/api/v1/provider/workspaces/{$workspaceId}", [
            'status' => Workspace::STATUS_ACTIVE,
        ])->assertStatus(422);

        // Submit for review succeeds
        $this->postJson("/api/v1/provider/workspaces/{$workspaceId}/submit-review")
            ->assertOk()
            ->assertJsonPath('data.status', Workspace::STATUS_IN_REVIEW);

        // Upload up to 5 images, 6th must fail
        for ($i = 1; $i <= 5; $i++) {
            $this->post("/api/v1/provider/workspaces/{$workspaceId}/images", [
                'image' => UploadedFile::fake()->image("photo{$i}.jpg"),
            ], ['Accept' => 'application/json'])->assertCreated();
        }

        $this->post("/api/v1/provider/workspaces/{$workspaceId}/images", [
            'image' => UploadedFile::fake()->image('photo6.jpg'),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        // Provider B cannot access or mutate Provider A's workspace
        Sanctum::actingAs($this->ownerB);
        $this->getJson("/api/v1/provider/workspaces/{$workspaceId}")->assertStatus(403);
        $this->patchJson("/api/v1/provider/workspaces/{$workspaceId}", ['name' => 'Hijacked'])->assertStatus(403);
    }

    public function test_provider_staff_rbac_templates_reports_kyc_and_masked_bank_account(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->ownerA);

        $city = City::where('slug', 'bengaluru')->firstOrFail();
        $cat = WorkspaceCategory::where('code', WorkspaceCategory::CODE_DAY_PASS)->firstOrFail();
        $sub = WorkspaceSubcategory::where('code', 'HOT_DESK_DAY_PASS')->firstOrFail();

        $workspace = Workspace::create([
            'provider_id' => $this->providerA->id,
            'code' => 'DP-BLR8888',
            'slug' => 'alpha-layout-hub',
            'name' => 'Alpha Layout Hub',
            'category_id' => $cat->id,
            'subcategory_id' => $sub->id,
            'city_id' => $city->id,
            'address_line' => 'MG Road',
            'total_floors' => 1,
            'total_seats' => 30,
            'available_seats' => 30,
            'status' => Workspace::STATUS_ACTIVE,
            'approval_status' => Workspace::APPROVAL_APPROVED,
            'is_published' => true,
            'is_instant_bookable' => true,
            'created_by' => $this->ownerA->id,
        ]);

        // Create restricted staff role (only layoutDesigner, NO roleManagement or workspaceManagement)
        $roleRes = $this->postJson('/api/v1/provider/roles', [
            'name' => 'Layout Architect',
            'permissions' => ['providers.dashboard.view', 'providers.layoutDesigner.view', 'providers.templateMapping.view'],
        ])->assertCreated();

        $staffRes = $this->postJson('/api/v1/provider/staff', [
            'first_name' => 'Kiran',
            'last_name' => 'Rao',
            'email' => 'kiran.layout@alphaworq.test',
            'provider_role_id' => $roleRes->json('data.id'),
            'workspace_id' => $workspace->id,
        ])->assertCreated();

        $staffUser = User::where('email', 'kiran.layout@alphaworq.test')->firstOrFail();

        // Masked bank account & private KYC upload
        $bankRes = $this->postJson('/api/v1/provider/bank-accounts', [
            'account_holder_name' => 'Alpha Workspaces Pvt Ltd',
            'bank_name' => 'ICICI Bank',
            'account_number' => '987654321045',
            'ifsc_code' => 'ICIC0000456',
            'is_primary' => true,
        ])->assertCreated();

        $bankRes->assertJsonPath('data.account_last4', '1045')
            ->assertJsonMissingPath('data.account_number')
            ->assertJsonMissingPath('data.account_number_encrypted');

        $this->assertEquals('987654321045', ProviderBankAccount::first()->account_number_encrypted);

        $docRes = $this->post('/api/v1/provider/documents', [
            'document_type' => 'GST_CERTIFICATE',
            'document' => UploadedFile::fake()->create('gst.pdf', 120, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated();

        // Acting as restricted staff member: can create/clone layout template, denied role creation
        Sanctum::actingAs($staffUser);

        $tplRes = $this->postJson('/api/v1/provider/templates', [
            'name' => 'Bay Floor 1',
            'elements' => [
                ['type' => 'desk', 'label' => 'D1', 'x' => 10, 'y' => 10, 'width' => 100, 'height' => 60, 'seats' => 8],
                ['type' => 'meeting', 'label' => 'MR1', 'x' => 120, 'y' => 10, 'width' => 140, 'height' => 100, 'seats' => 6],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.elements_count', 2)
            ->assertJsonPath('data.total_seats', 14);

        $this->postJson("/api/v1/provider/templates/{$tplRes->json('data.id')}/clone")
            ->assertCreated()
            ->assertJsonPath('data.name', 'Copy of Bay Floor 1');

        // Restricted staff denied managing roles (403)
        $this->postJson('/api/v1/provider/roles', [
            'name' => 'Escalated Role',
            'permissions' => ['providers.users.view'],
        ])->assertStatus(403);

        // Owner exports CSV reports
        Sanctum::actingAs($this->ownerA);
        $this->get('/api/v1/provider/reports/instant-bookings/export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->get('/api/v1/provider/reports/reserve-requests/export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $docId = $docRes->json('data.id');
        Sanctum::actingAs($this->ownerB);
        $this->get("/api/v1/provider/documents/{$docId}/download")->assertStatus(403);
    }
}
