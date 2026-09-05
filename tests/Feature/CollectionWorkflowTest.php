<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CollectionCase;
use App\Models\Donation;
use App\Models\DonationType;
use App\Models\Group;
use App\Models\Lead;
use App\Models\InstantDonationMethod;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollectionWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_collection_conversion_assignment_and_completion_are_scoped_and_atomic(): void
    {
        Storage::fake('local');

        $branch = Branch::query()->create(['name_ar' => 'القاهرة', 'code' => 'cairo', 'is_active' => true]);
        $otherBranch = Branch::query()->create(['name_ar' => 'الإسكندرية', 'code' => 'alex', 'is_active' => true]);
        $agent = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
        $manager = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
        $collector = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
        $otherManager = User::factory()->create(['branch_id' => $otherBranch->id, 'is_active' => true]);

        $this->givePermissions($agent, 'collection-test-agent', [
            CrmPermission::LEADS_VIEW,
            CrmPermission::LEADS_UPDATE,
            CrmPermission::LEADS_FOLLOWUPS_VIEW,
            CrmPermission::LEADS_FOLLOWUPS_CREATE,
        ]);
        $this->givePermissions($manager, 'collection-test-manager', [
            CrmPermission::COLLECTIONS_VIEW,
            CrmPermission::COLLECTIONS_ASSIGN,
            CrmPermission::COLLECTIONS_MANAGE,
            CrmPermission::COLLECTIONS_CANCEL,
        ]);
        $this->givePermissions($collector, 'collection-test-collector', [
            CrmPermission::COLLECTIONS_VIEW,
            CrmPermission::COLLECTIONS_COLLECT,
            CrmPermission::COLLECTIONS_COMPLETE,
        ]);
        $this->givePermissions($otherManager, 'collection-test-other-manager', [
            CrmPermission::COLLECTIONS_VIEW,
            CrmPermission::COLLECTIONS_ASSIGN,
            CrmPermission::COLLECTIONS_MANAGE,
        ]);

        $newStage = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            ['name_ar' => 'جديد', 'position' => 1, 'color' => '#2563eb', 'is_primary' => true, 'is_active' => true],
        );
        $newStatus = LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            ['pipeline_stage_id' => $newStage->id, 'name_ar' => 'جديد', 'position' => 1, 'color' => '#2563eb', 'is_terminal' => false],
        );
        $donorStage = PipelineStage::query()->firstOrCreate(
            ['code' => 'donor'],
            ['name_ar' => 'متبرع', 'position' => 2, 'color' => '#16a34a', 'is_primary' => true, 'is_active' => true],
        );
        $donorStatus = LeadStatus::query()->firstOrCreate(
            ['code' => 'donor'],
            ['pipeline_stage_id' => $donorStage->id, 'name_ar' => 'متبرع', 'position' => 2, 'color' => '#16a34a', 'is_terminal' => false],
        );
        $donationType = DonationType::query()->create([
            'name_ar' => 'صدقة',
            'name_en' => 'Charity',
            'is_active' => true,
            'position' => 10,
        ]);
        $lead = Lead::query()->create([
            'name' => 'متبرع التحصيل',
            'phone' => '01012345678',
            'address' => 'مدينة نصر',
            'branch_id' => $branch->id,
            'lead_status_id' => $newStatus->id,
            'assigned_user_id' => $agent->id,
            'created_by_user_id' => $agent->id,
            'created_by' => $agent->name,
        ]);

        $govCairo = \App\Models\Governorate::query()->firstOrCreate(['code' => 'cairo'], ['name_ar' => 'القاهرة', 'is_active' => true]);
        $subNasrCity = \App\Models\GovernorateSubregion::query()->firstOrCreate(['code' => 'nasr_city', 'governorate_id' => $govCairo->id], ['name_ar' => 'مدينة نصر', 'is_active' => true]);

        $conversion = $this->actingAs($agent)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $donorStatus->id,
            'communication_type' => 'call',
            'outcome' => 'طلب مندوب للتحصيل',
            'donation_type_id' => $donationType->id,
            'donation_value' => 750,
            'donation_cycle' => 'monthly',
            'donation_way' => 'collection',
            'collection_due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'governorate_id' => $govCairo->id,
            'subregion_id' => $subNasrCity->id,
            'collection_address' => 'مدينة نصر، شارع الطيران',
            'collection_notes' => 'الاتصال قبل الوصول',
        ]);

        $conversion->assertSessionHasNoErrors();
        $collectionCase = CollectionCase::query()->firstOrFail();
        $this->assertSame(CollectionCase::STATUS_PENDING, $collectionCase->status);
        $this->assertNull($collectionCase->assigned_collector_user_id);
        $this->assertSame('750.00', (string) $collectionCase->expected_amount);
        $this->assertSame($donorStatus->id, $lead->fresh()->lead_status_id);
        $this->assertSame(0, Donation::query()->count());

        $this->actingAs($manager)
            ->get(route('v2.collections.index'))
            ->assertOk()
            ->assertSee($lead->name);
        $this->actingAs($otherManager)
            ->get(route('v2.collections.show', $collectionCase))
            ->assertForbidden();

        $assignment = $this->actingAs($manager)->patch(route('v2.collections.assign', $collectionCase), [
            'assigned_collector_user_id' => $collector->id,
        ]);
        $assignment->assertSessionHasNoErrors();
        $this->assertSame(CollectionCase::STATUS_ASSIGNED, $collectionCase->fresh()->status);
        $this->assertSame($collector->id, $collectionCase->fresh()->assigned_collector_user_id);

        $this->actingAs($collector)
            ->get(route('v2.collections.show', $collectionCase))
            ->assertOk()
            ->assertSee($lead->phone)
            ->assertDontSee('سجل المتابعات');
        $this->actingAs($collector)
            ->get(route('v2.leads.show', $lead))
            ->assertForbidden();

        $completion = $this->actingAs($collector)->post(route('v2.collections.complete', $collectionCase), [
            'donation_receipt' => UploadedFile::fake()->image('receipt.jpg'),
            'notes' => 'تم الاستلام بالكامل',
        ]);
        $completion->assertSessionHasNoErrors();

        $collectionCase->refresh();
        $donation = Donation::query()->where('collection_case_id', $collectionCase->id)->firstOrFail();
        $this->assertSame(CollectionCase::STATUS_COLLECTED, $collectionCase->status);
        $this->assertSame('collection', $donation->donation_way);
        $this->assertSame('750.00', (string) $donation->amount);
        $this->assertNotNull($donation->receipt_path);
        Storage::disk('local')->assertExists($donation->receipt_path);
        $this->assertSame(1, Donation::query()->count());

        // Verify a follow-up record was created for the collection event
        $collectionFollowup = LeadFollowup::query()
            ->where('lead_id', $collectionCase->lead_id)
            ->where('communication_type', 'collection')
            ->first();
        $this->assertNotNull($collectionFollowup, 'Collection completion must create a follow-up record');
        $this->assertStringContainsString('تم تحصيل', $collectionFollowup->outcome);
        $this->assertSame($donation->lead_followup_id, $collectionFollowup->id);
        $this->assertSame(3, $collectionCase->activities()->count());

        $this->actingAs($collector)
            ->get(route('v2.collections.receipt', $collectionCase))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    }

    public function test_collector_zone_can_be_assigned_and_shown_with_matching_indicator_for_collection_managers(): void
    {
        $branch = Branch::query()->create(['name_ar' => 'الجيزة', 'code' => 'giza', 'is_active' => true]);
        $manager = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
        $collector = User::factory()->create([
            'branch_id' => $branch->id,
            'name' => 'محمود الدقي',
            'collection_zone' => 'الدقي والمهندسين',
            'is_active' => true,
        ]);

        $this->givePermissions($manager, 'zone-manager-group', [
            CrmPermission::COLLECTIONS_VIEW,
            CrmPermission::COLLECTIONS_ASSIGN,
            CrmPermission::COLLECTIONS_MANAGE,
        ]);
        $this->givePermissions($collector, 'zone-collector-group', [
            CrmPermission::COLLECTIONS_VIEW,
            CrmPermission::COLLECTIONS_COLLECT,
            CrmPermission::COLLECTIONS_COMPLETE,
        ]);

        $donationType = DonationType::query()->firstOrCreate(
            ['name_ar' => 'عمليات جراحية'],
            ['is_active' => true, 'position' => 1]
        );

        $status = LeadStatus::query()->firstOrCreate(
            ['code' => 'donor'],
            ['name_ar' => 'متبرع', 'position' => 4, 'is_active' => true]
        );

        $lead = Lead::query()->create([
            'name' => 'متبرع في الدقي',
            'phone' => '01012345678',
            'governorate' => 'الجيزة',
            'address' => 'شارع مصدق، الدقي',
            'branch_id' => $branch->id,
            'lead_status_id' => $status->id,
            'created_by' => 'Call Center',
        ]);

        $case = CollectionCase::query()->create([
            'lead_id' => $lead->id,
            'branch_id' => $branch->id,
            'donation_type_id' => $donationType->id,
            'donation_type' => $donationType->name_ar,
            'expected_amount' => 1500,
            'cycle' => 'monthly',
            'due_at' => now()->addDays(1),
            'collection_address' => 'شارع مصدق، الدقي',
            'status' => CollectionCase::STATUS_PENDING,
            'created_by_user_id' => $manager->id,
        ]);

        // Manager opens the collection details
        $response = $this->actingAs($manager)->get(route('v2.collections.show', $case));
        $response->assertOk();
        $response->assertSee('الدقي والمهندسين');
        $response->assertSee('منطقة متطابقة');

        // Manager assigns the collector
        $assignRes = $this->actingAs($manager)->patch(route('v2.collections.assign', $case), [
            'assigned_collector_user_id' => $collector->id,
            'notes' => 'يرجى التواصل قبل التوجه',
        ]);
        $assignRes->assertSessionHasNoErrors();

        $case->refresh();
        $this->assertSame(CollectionCase::STATUS_ASSIGNED, $case->status);
        $this->assertSame($collector->id, $case->assigned_collector_user_id);
        $this->assertSame('1500.00', (string) $case->expected_amount);
        $this->assertSame('شارع مصدق، الدقي', $case->collection_address);

        // Collector opens their assigned case on mobile
        $collectorRes = $this->actingAs($collector)->get(route('v2.collections.show', $case));
        $collectorRes->assertOk();
        $collectorRes->assertSee('capture="environment"', false);
        $collectorRes->assertSee('sip:01012345678');
        $collectorRes->assertSee('https://wa.me/01012345678', false);
        $collectorRes->assertSee('google.com/maps', false);
        $collectorRes->assertSee('شارع مصدق، الدقي');
        $collectorRes->assertSee('1,500.00');
    }

    public function test_instant_donation_methods_can_be_configured_with_accounts_and_numbers(): void
    {
        $manager = User::factory()->create(['is_active' => true]);
        $this->givePermissions($manager, 'method-manager-role', [
            CrmPermission::COLLECTIONS_METHODS_MANAGE,
        ]);

        // 1. Create a new payment method with accounts
        $this->actingAs($manager)
            ->post(route('v2.collections.methods.store'), [
                'code' => 'instapay_test',
                'name_ar' => 'إنستاباي تجريبي',
                'name_en' => 'InstaPay Test',
                'position' => 15,
                'accounts' => "01011112222 (الحساب الرئيسي)\ncharity@instapay (IPA)",
            ])
            ->assertRedirect();

        $method = InstantDonationMethod::query()->where('code', 'instapay_test')->firstOrFail();
        $this->assertSame(['01011112222 (الحساب الرئيسي)', 'charity@instapay (IPA)'], $method->accounts);
        $this->assertSame(['01011112222 (الحساب الرئيسي)', 'charity@instapay (IPA)'], $method->getAccountsList());

        // 2. Update existing payment method accounts
        $this->actingAs($manager)
            ->patch(route('v2.collections.methods.update', $method), [
                'name_ar' => 'إنستاباي محدث',
                'name_en' => 'InstaPay Updated',
                'position' => 10,
                'is_active' => '1',
                'accounts' => "01099998888 (المحفظة الثانية)\n01234567890 (حساب الأيتام)",
            ])
            ->assertRedirect();

        $method->refresh();
        $this->assertSame('إنستاباي محدث', $method->name_ar);
        $this->assertSame(['01099998888 (المحفظة الثانية)', '01234567890 (حساب الأيتام)'], $method->accounts);
    }

    public function test_recording_instant_donation_with_configured_account_saves_account_on_donation(): void
    {
        $agent = User::factory()->create(['is_active' => true]);
        $this->givePermissions($agent, 'followup-donation-agent', [
            CrmPermission::LEADS_VIEW,
            CrmPermission::LEADS_UPDATE,
            CrmPermission::LEADS_FOLLOWUPS_CREATE,
        ]);

        $method = InstantDonationMethod::query()->firstOrCreate(
            ['code' => 'instapay'],
            [
                'name_ar' => 'إنستاباي',
                'name_en' => 'InstaPay',
                'is_active' => true,
                'position' => 10,
                'accounts' => ['01012345678 (إنستاباي الرئيسي)', 'charity@instapay'],
            ],
        );
        $method->update(['accounts' => ['01012345678 (إنستاباي الرئيسي)', 'charity@instapay']]);

        $donorStage = PipelineStage::query()->firstOrCreate(
            ['code' => 'donor'],
            ['name_ar' => 'متبرع', 'position' => 2, 'color' => '#16a34a', 'is_primary' => true, 'is_active' => true],
        );
        $donorStatus = LeadStatus::query()->firstOrCreate(
            ['code' => 'donor'],
            ['pipeline_stage_id' => $donorStage->id, 'name_ar' => 'متبرع', 'position' => 2, 'color' => '#16a34a', 'is_terminal' => true],
        );
        $newStage = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            ['name_ar' => 'جديد', 'position' => 1, 'color' => '#2563eb', 'is_primary' => true, 'is_active' => true],
        );
        $newStatus = LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            ['pipeline_stage_id' => $newStage->id, 'name_ar' => 'جديد', 'position' => 1, 'color' => '#2563eb', 'is_terminal' => false],
        );
        $type = DonationType::query()->firstOrCreate(['name_ar' => 'كفالة أيتام'], ['is_active' => true]);

        $lead = Lead::query()->create([
            'name' => 'متبرع إنستاباي',
            'phone' => '01009988776',
            'lead_status_id' => $newStatus->id,
            'assigned_user_id' => $agent->id,
        ]);

        // Log a followup with instant donation on Instapay and select account "01012345678 (إنستاباي الرئيسي)"
        $response = $this->actingAs($agent)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $donorStatus->id,
            'communication_type' => 'call',
            'outcome' => 'تم التبرع بنجاح عبر إنستاباي',
            'make_donation' => 1,
            'donation_type_id' => $type->id,
            'donation_value' => '1200',
            'donation_cycle' => 'monthly',
            'donation_way' => 'instant',
            'instant_donation_method_id' => $method->id,
            'instant_donation_account' => '01012345678 (إنستاباي الرئيسي)',
        ]);
        $response->assertSessionHasNoErrors();

        $donation = Donation::query()->where('lead_id', $lead->id)->firstOrFail();
        $this->assertSame('instant', $donation->donation_way);
        $this->assertSame($method->id, $donation->instant_donation_method_id);
        $this->assertSame('01012345678 (إنستاباي الرئيسي)', $donation->instant_donation_account);
        $this->assertSame('1200.00', (string) $donation->amount);
    }

    public function test_user_with_collections_view_sees_collections_in_sidebar_and_active_state(): void
    {
        $branch = Branch::query()->create(['name_ar' => 'القاهرة', 'code' => 'cairo_nav', 'is_active' => true]);
        $manager = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
        $this->givePermissions($manager, 'col-nav-manager', [
            CrmPermission::DASHBOARD_VIEW,
            CrmPermission::COLLECTIONS_VIEW,
        ]);

        // 1. Visit dashboard -> see Collections in sidebar
        $response = $this->actingAs($manager)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee(route('v2.collections.index'));

        // 2. Visit collections -> sidebar link is active
        $responseCol = $this->actingAs($manager)->get(route('v2.collections.index'));
        $responseCol->assertOk();
        $responseCol->assertSee('crm-link link active', false);
        $responseCol->assertSee(route('v2.collections.index'));
    }

    public function test_user_without_collections_view_does_not_see_collections_in_sidebar(): void
    {
        $branch = Branch::query()->create(['name_ar' => 'القاهرة', 'code' => 'cairo_nav2', 'is_active' => true]);
        $agent = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
        $this->givePermissions($agent, 'col-nav-agent', [
            CrmPermission::DASHBOARD_VIEW,
            CrmPermission::LEADS_VIEW,
        ]);

        $response = $this->actingAs($agent)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee(route('v2.collections.index'));
    }

    public function test_dashboard_topbar_does_not_contain_collections_button(): void
    {
        $branch = Branch::query()->create(['name_ar' => 'القاهرة', 'code' => 'cairo_nav3', 'is_active' => true]);
        $manager = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
        $this->givePermissions($manager, 'col-nav-manager2', [
            CrmPermission::DASHBOARD_VIEW,
            CrmPermission::COLLECTIONS_VIEW,
            CrmPermission::LEADS_VIEW,
            CrmPermission::LEADS_CREATE,
        ]);

        $response = $this->actingAs($manager)->get(route('dashboard'));
        $response->assertOk();

        // Topbar actions should only contain add_lead, all_leads, profile dropdown
        $content = $response->getContent();
        $topbarActionsPos = strpos($content, 'class="topbar-actions"');
        $headerEndPos = strpos($content, '</header>', $topbarActionsPos);
        $this->assertNotFalse($topbarActionsPos);
        $this->assertNotFalse($headerEndPos);

        $topbarHtml = substr($content, $topbarActionsPos, $headerEndPos - $topbarActionsPos);
        $this->assertStringNotContainsString(route('v2.collections.index'), $topbarHtml);
    }

    public function test_onsite_collector_escalates_to_call_center_and_call_center_confirms_collect_now(): void
    {
        Storage::fake('local');
        $branch = Branch::query()->create(['name_ar' => 'فرع القاهرة', 'code' => 'cairo_esc', 'is_active' => true]);

        $gov = \App\Models\Governorate::query()->firstOrCreate(['code' => 'cairo'], ['name_ar' => 'القاهرة', 'is_active' => true]);
        $sub = \App\Models\GovernorateSubregion::query()->firstOrCreate(['code' => 'nasr_city', 'governorate_id' => $gov->id], ['name_ar' => 'مدينة نصر', 'is_active' => true]);

        $colMgr = User::factory()->create(['branch_id' => $branch->id, 'name' => 'مسؤول التحصيل نادر', 'is_active' => true]);
        $callCenterAgent = User::factory()->create(['branch_id' => $branch->id, 'name' => 'موظف الكول سنتر أحمد', 'is_active' => true]);
        $collector = User::factory()->create([
            'branch_id' => $branch->id,
            'manager_id' => $colMgr->id,
            'collection_subregion_id' => $sub->id,
            'name' => 'المحصل مصطفى',
            'is_active' => true,
        ]);

        $this->givePermissions($callCenterAgent, 'agent-esc-role', [
            CrmPermission::LEADS_VIEW,
            CrmPermission::COLLECTIONS_VIEW,
            CrmPermission::COLLECTIONS_ESCALATIONS_RESPOND,
        ]);
        $this->givePermissions($colMgr, 'mgr-esc-role', [
            CrmPermission::COLLECTIONS_VIEW,
            CrmPermission::COLLECTIONS_MANAGE,
        ]);
        $this->givePermissions($collector, 'collector-esc-role', [
            CrmPermission::COLLECTIONS_VIEW,
            CrmPermission::COLLECTIONS_COLLECT,
            CrmPermission::COLLECTIONS_COMPLETE,
        ]);

        $donationType = DonationType::query()->firstOrCreate(
            ['name_ar' => 'كفالة أيتام'],
            ['is_active' => true, 'position' => 1]
        );
        $status = LeadStatus::query()->firstOrCreate(
            ['code' => 'donor'],
            ['name_ar' => 'متبرع', 'position' => 4, 'is_active' => true]
        );

        $lead = Lead::query()->create([
            'name' => 'المتبرع كريم',
            'phone' => '01099998888',
            'governorate_id' => $gov->id,
            'subregion_id' => $sub->id,
            'address' => 'شارع الطيران، مدينة نصر',
            'branch_id' => $branch->id,
            'assigned_user_id' => $callCenterAgent->id,
            'lead_status_id' => $status->id,
        ]);

        $case = CollectionCase::query()->create([
            'lead_id' => $lead->id,
            'branch_id' => $branch->id,
            'donation_type_id' => $donationType->id,
            'donation_type' => $donationType->name_ar,
            'expected_amount' => 2000,
            'cycle' => 'monthly',
            'due_at' => now(),
            'collection_address' => 'شارع الطيران، مدينة نصر',
            'governorate_id' => $gov->id,
            'subregion_id' => $sub->id,
            'status' => CollectionCase::STATUS_ASSIGNED,
            'assigned_collector_user_id' => $collector->id,
            'created_by_user_id' => $callCenterAgent->id,
        ]);

        // 1. Collector is on-site and cannot reach donor -> triggers escalation
        $escRes = $this->actingAs($collector)->post(route('v2.collections.escalate', $case), [
            'collector_note' => 'أنا أمام العمارة والهاتف مغلق وجرس الباب لا يستجيب',
        ]);
        $escRes->assertRedirect(route('v2.collections.show', $case));

        $case->refresh();
        $this->assertSame(CollectionCase::STATUS_AWAITING_CALL_CENTER, $case->status);
        $this->assertCount(1, $case->escalations);

        $escalation = $case->escalations->first();
        $this->assertSame(\App\Models\CollectionEscalation::STATUS_PENDING, $escalation->status);
        $this->assertSame($collector->id, $escalation->collector_user_id);
        $this->assertSame($callCenterAgent->id, $escalation->call_center_user_id);
        $this->assertSame($colMgr->id, $escalation->collection_manager_user_id);
        $this->assertSame('أنا أمام العمارة والهاتف مغلق وجرس الباب لا يستجيب', $escalation->collector_note);

        // 2. Call Center Agent receives handoff and visits escalation page
        $viewRes = $this->actingAs($callCenterAgent)->get(route('v2.collections.escalations.show', $escalation));
        $viewRes->assertOk();
        $viewRes->assertSee('المتبرع كريم');
        $viewRes->assertSee('01099998888');
        $viewRes->assertSee('أنا أمام العمارة والهاتف مغلق');

        // 3. Call Center Agent calls donor, donor is ready, agent resolves with "Collect Now"
        $resolveRes = $this->actingAs($callCenterAgent)->patch(route('v2.collections.escalations.resolve', $escalation), [
            'decision' => 'collect_now',
            'response_note' => 'تواصلت مع المتبرع ونزل للمحصل في مدخل العمارة',
        ]);
        $resolveRes->assertRedirect(route('v2.collections.show', $case));

        $escalation->refresh();
        $case->refresh();
        $this->assertSame(\App\Models\CollectionEscalation::STATUS_COLLECT_NOW, $escalation->status);
        $this->assertSame(CollectionCase::STATUS_ASSIGNED, $case->status);
        $this->assertSame('تواصلت مع المتبرع ونزل للمحصل في مدخل العمارة', $escalation->response_note);

        // 4. Collector completes the collection on site with receipt photo
        $file = UploadedFile::fake()->image('receipt.jpg');
        $completeRes = $this->actingAs($collector)->post(route('v2.collections.complete', $case), [
            'donation_receipt' => $file,
            'notes' => 'تم استلام التبرع بنجاح',
        ]);
        $completeRes->assertSessionHasNoErrors();

        $case->refresh();
        $this->assertSame(CollectionCase::STATUS_COLLECTED, $case->status);
        $this->assertNotNull($case->completed_at);
    }

    public function test_onsite_collector_escalates_and_call_center_reschedules_collection(): void
    {
        $branch = Branch::query()->create(['name_ar' => 'فرع الجيزة', 'code' => 'giza_esc', 'is_active' => true]);

        $gov = \App\Models\Governorate::query()->firstOrCreate(['code' => 'giza'], ['name_ar' => 'الجيزة', 'is_active' => true]);
        $sub = \App\Models\GovernorateSubregion::query()->firstOrCreate(['code' => 'dokki', 'governorate_id' => $gov->id], ['name_ar' => 'الدقي', 'is_active' => true]);

        $colMgr = User::factory()->create(['branch_id' => $branch->id, 'name' => 'مسؤول التحصيل', 'is_active' => true]);
        $callCenterAgent = User::factory()->create(['branch_id' => $branch->id, 'name' => 'موظفة الكول سنتر سارة', 'is_active' => true]);
        $collector = User::factory()->create([
            'branch_id' => $branch->id,
            'manager_id' => $colMgr->id,
            'collection_subregion_id' => $sub->id,
            'name' => 'محصل الدقي',
            'is_active' => true,
        ]);

        $this->givePermissions($callCenterAgent, 'agent-esc-role2', [
            CrmPermission::LEADS_VIEW,
            CrmPermission::COLLECTIONS_VIEW,
            CrmPermission::COLLECTIONS_ESCALATIONS_RESPOND,
        ]);
        $this->givePermissions($collector, 'collector-esc-role2', [
            CrmPermission::COLLECTIONS_VIEW,
            CrmPermission::COLLECTIONS_COLLECT,
        ]);

        $donationType = DonationType::query()->firstOrCreate(
            ['name_ar' => 'إطعام'],
            ['is_active' => true, 'position' => 1]
        );
        $status = LeadStatus::query()->firstOrCreate(
            ['code' => 'donor'],
            ['name_ar' => 'متبرع', 'position' => 4, 'is_active' => true]
        );

        $lead = Lead::query()->create([
            'name' => 'المتبرع سعيد',
            'phone' => '01055554444',
            'governorate_id' => $gov->id,
            'subregion_id' => $sub->id,
            'address' => 'شارع مصدق، الدقي',
            'branch_id' => $branch->id,
            'assigned_user_id' => $callCenterAgent->id,
            'lead_status_id' => $status->id,
        ]);

        $case = CollectionCase::query()->create([
            'lead_id' => $lead->id,
            'branch_id' => $branch->id,
            'donation_type_id' => $donationType->id,
            'donation_type' => $donationType->name_ar,
            'expected_amount' => 500,
            'cycle' => 'one_time',
            'due_at' => now(),
            'collection_address' => 'شارع مصدق، الدقي',
            'governorate_id' => $gov->id,
            'subregion_id' => $sub->id,
            'status' => CollectionCase::STATUS_ASSIGNED,
            'assigned_collector_user_id' => $collector->id,
        ]);

        // 1. Collector escalates
        $this->actingAs($collector)->post(route('v2.collections.escalate', $case), [
            'collector_note' => 'المتبرع غير موجود في البيت',
        ]);

        $escalation = $case->escalations()->firstOrFail();

        // 2. Call center agent reschedules for tomorrow
        $newDate = now()->addDays(2)->startOfHour();
        $resolveRes = $this->actingAs($callCenterAgent)->patch(route('v2.collections.escalations.resolve', $escalation), [
            'decision' => 'rescheduled',
            'due_at' => $newDate->format('Y-m-d H:i:s'),
            'response_note' => 'المتبرع مسافر وسيعود بعد يومين لاستلام التحصيل',
        ]);
        $resolveRes->assertRedirect(route('v2.collections.show', $case));

        $escalation->refresh();
        $case->refresh();
        $this->assertSame(\App\Models\CollectionEscalation::STATUS_RESCHEDULED, $escalation->status);
        $this->assertSame(CollectionCase::STATUS_SCHEDULED, $case->status);
        $this->assertSame('المتبرع مسافر وسيعود بعد يومين لاستلام التحصيل', $escalation->response_note);
    }

    public function test_converting_lead_without_address_into_collection_donor_persists_address_on_lead_and_case(): void
    {
        $branch = Branch::query()->create(['name_ar' => 'فرع القاهرة', 'code' => 'cairo_no_addr', 'is_active' => true]);
        $agent = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
        $this->givePermissions($agent, 'agent-no-addr', [
            CrmPermission::LEADS_VIEW,
            CrmPermission::LEADS_UPDATE,
            CrmPermission::LEADS_FOLLOWUPS_VIEW,
            CrmPermission::LEADS_FOLLOWUPS_CREATE,
        ]);

        $newStatus = LeadStatus::query()->firstOrCreate(['code' => 'new'], ['name_ar' => 'جديد', 'position' => 1]);
        $donorStatus = LeadStatus::query()->firstOrCreate(['code' => 'donor'], ['name_ar' => 'متبرع', 'position' => 4]);
        $donationType = DonationType::query()->firstOrCreate(['name_ar' => 'كفالة أيتام'], ['is_active' => true]);

        $gov = \App\Models\Governorate::query()->firstOrCreate(['code' => 'giza'], ['name_ar' => 'الجيزة', 'is_active' => true]);
        $sub = \App\Models\GovernorateSubregion::query()->firstOrCreate(['code' => 'dokki', 'governorate_id' => $gov->id], ['name_ar' => 'الدقي', 'is_active' => true]);

        // Lead with NO initial address or governorate (like Lead #569)
        $lead = Lead::query()->create([
            'name' => 'عميل بدون عنوان سابق',
            'phone' => '01011112222',
            'address' => null,
            'governorate_id' => null,
            'subregion_id' => null,
            'branch_id' => $branch->id,
            'lead_status_id' => $newStatus->id,
            'assigned_user_id' => $agent->id,
            'created_by_user_id' => $agent->id,
        ]);

        // Converting to donor via collection requiring address
        $response = $this->actingAs($agent)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $donorStatus->id,
            'communication_type' => 'call',
            'outcome' => 'تم الاتفاق على التحصيل وكتابة العنوان',
            'donation_type_id' => $donationType->id,
            'donation_value' => 1000,
            'donation_cycle' => 'one_time',
            'donation_way' => 'collection',
            'collection_due_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'governorate_id' => $gov->id,
            'subregion_id' => $sub->id,
            'collection_address' => '10 شارع مصدق، الدور 4، شقة 8',
        ]);

        $response->assertSessionHasNoErrors();

        $lead->refresh();
        $this->assertSame($donorStatus->id, $lead->lead_status_id);
        $this->assertSame($gov->id, $lead->governorate_id);
        $this->assertSame($sub->id, $lead->subregion_id);
        $this->assertSame('10 شارع مصدق، الدور 4، شقة 8', $lead->address);

        $case = CollectionCase::query()->where('lead_id', $lead->id)->firstOrFail();
        $this->assertSame($gov->id, $case->governorate_id);
        $this->assertSame($sub->id, $case->subregion_id);
        $this->assertSame('10 شارع مصدق، الدور 4، شقة 8', $case->collection_address);
        $this->assertSame('1000.00', (string) $case->expected_amount);
    }

    public function test_followup_view_prefills_lead_existing_governorate_and_subregion(): void
    {
        $branch = Branch::query()->create(['name_ar' => 'فرع الدقي', 'code' => 'dokki_prefill', 'is_active' => true]);
        $agent = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
        $this->givePermissions($agent, 'agent-prefill', [
            CrmPermission::LEADS_VIEW,
            CrmPermission::LEADS_FOLLOWUPS_VIEW,
        ]);

        $gov = \App\Models\Governorate::query()->firstOrCreate(['code' => 'giza'], ['name_ar' => 'الجيزة', 'is_active' => true]);
        $sub = \App\Models\GovernorateSubregion::query()->firstOrCreate(['code' => 'agouza', 'governorate_id' => $gov->id], ['name_ar' => 'العجوزة', 'is_active' => true]);
        $status = LeadStatus::query()->firstOrCreate(['code' => 'new'], ['name_ar' => 'جديد', 'position' => 1]);

        $lead = Lead::query()->create([
            'name' => 'عميل لديه عنوان سابق',
            'phone' => '01033334444',
            'address' => 'شارع النيل، العجوزة',
            'governorate_id' => $gov->id,
            'subregion_id' => $sub->id,
            'branch_id' => $branch->id,
            'lead_status_id' => $status->id,
            'assigned_user_id' => $agent->id,
        ]);

        $response = $this->actingAs($agent)->get(route('v2.leads.followups.index', $lead));
        $response->assertOk();
        $response->assertSee('collection_governorate_id');
        $response->assertSee('collection_subregion_id');
        $response->assertSee('شارع النيل، العجوزة');
    }
    /** @param list<CrmPermission> $permissions */
    private function givePermissions(User $user, string $code, array $permissions): void
    {
        $group = Group::query()->where('code', $code)->first()
            ?? Group::query()->firstOrCreate(
                ['code' => $code],
                ['name' => $code, 'is_system' => false],
            );
        $codes = collect($permissions)->map(fn (CrmPermission $permission): string => $permission->value)->all();
        $ids = Permission::query()->whereIn('code', $codes)->pluck('id')->all();
        if (count($ids) < count($codes)) {
            $now = now();
            $permData = [];
            foreach ($permissions as $permission) {
                $permData[] = [
                    'code' => $permission->value,
                    'module' => $permission->module(),
                    'name_ar' => $permission->label(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            Permission::query()->insertOrIgnore($permData);
            $ids = Permission::query()->whereIn('code', $codes)->pluck('id')->all();
        }
        $group->permissions()->syncWithoutDetaching($ids);
        $user->groups()->syncWithoutDetaching([$group->id]);
        $user->unsetRelation('groups');
    }
}
