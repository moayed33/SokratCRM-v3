<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CalendarEvent;
use App\Models\Campaign;
use App\Models\CollectionActivity;
use App\Models\CollectionCase;
use App\Models\CrmOption;
use App\Models\Donation;
use App\Models\DonationPurpose;
use App\Models\DonationType;
use App\Models\Group;
use App\Models\InstantDonationMethod;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadFormField;
use App\Models\LeadPhone;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\NotificationRule;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Models\VoipExtensionAssignment;
use App\Security\CrmPermission;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ComprehensiveDummyDataSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Demo@123456';

    public function run(): void
    {
        DB::transaction(function (): void {
            $branches = $this->seedBranches();
            $groups = $this->seedGroupsAndPermissions();
            $users = $this->seedUsersHierarchy($branches, $groups);
            $stagesAndStatuses = $this->seedPipelineStagesAndStatuses();
            $donationLookups = $this->seedDonationLookups();
            $instantMethods = $this->seedInstantDonationMethods();
            $leads = $this->seedLeads($branches, $users, $stagesAndStatuses, $donationLookups);
            $this->seedFollowupsAndHistories($leads, $users, $stagesAndStatuses);
            $this->seedDonationsLedger($leads, $users, $donationLookups, $instantMethods);
            $this->seedCollectionWorkflow($leads, $users, $branches, $donationLookups);
            $this->seedCampaigns($branches, $users, $leads);
            $this->seedCalendarEvents($branches, $users, $leads);
        });
    }

    private function seedBranches(): array
    {
        $branchDefs = [
            ['code' => 'cairo_main', 'name_ar' => 'فرع القاهرة الرئيسي', 'name_en' => 'Cairo Main Branch'],
            ['code' => 'giza', 'name_ar' => 'فرع الجيزة والدقي', 'name_en' => 'Giza & Dokki Branch'],
            ['code' => 'alex', 'name_ar' => 'فرع الإسكندرية وسموحة', 'name_en' => 'Alexandria Branch'],
            ['code' => 'mansoura', 'name_ar' => 'فرع الدلتا والمنصورة', 'name_en' => 'Mansoura & Delta Branch'],
        ];

        $branches = [];
        foreach ($branchDefs as $def) {
            $branches[$def['code']] = Branch::query()->firstOrCreate(
                ['code' => $def['code']],
                ['name_ar' => $def['name_ar'], 'name_en' => $def['name_en'], 'is_active' => true]
            );
        }

        return $branches;
    }

    private function seedGroupsAndPermissions(): array
    {
        foreach (CrmPermission::values() as $code) {
            Permission::query()->firstOrCreate(
                ['code' => $code],
                ['module' => explode('.', $code, 2)[0] ?? 'crm', 'name_ar' => $code, 'description' => $code]
            );
        }

        $allPermIds = Permission::pluck('id')->all();

        // 1. Super Admin
        $superGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'سوبر أدمن', 'description' => 'وصول كامل ومطلق لكل وظائف وفروع النظام.', 'is_system' => true, 'is_active' => true]
        );
        $superGroup->permissions()->sync($allPermIds);

        // 2. Branch Admin
        $branchAdminGroup = Group::query()->firstOrCreate(
            ['code' => 'branch-admin'],
            ['name' => 'أدمن الفرع', 'description' => 'إدارة كاملة لعمليات وموظفي وتحصيلات الفرع (Branch Admin)', 'is_system' => true, 'is_active' => true]
        );
        $branchAdminPerms = Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_SCOPE_ALL->value,
            CrmPermission::LEADS_SCOPE_GROUP->value,
            CrmPermission::LEADS_ASSIGN->value,
            CrmPermission::LEADS_CREATE->value,
            CrmPermission::LEADS_UPDATE->value,
            CrmPermission::LEADS_DELETE->value,
            CrmPermission::LEADS_IMPORT->value,
            CrmPermission::LEADS_EXPORT->value,
            CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
            CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
            CrmPermission::TASKS_VIEW->value,
            CrmPermission::CAMPAIGNS_VIEW->value,
            CrmPermission::CAMPAIGNS_CREATE->value,
            CrmPermission::CAMPAIGNS_REPORTS->value,
            CrmPermission::REPORTS_VIEW->value,
            CrmPermission::REPORTS_EMPLOYEES_VIEW->value,
            CrmPermission::USERS_VIEW->value,
            CrmPermission::USERS_CREATE->value,
            CrmPermission::USERS_UPDATE->value,
            CrmPermission::USERS_ACTIVATE->value,
            CrmPermission::USERS_RESET_PASSWORD->value,
            CrmPermission::VOIP_VIEW->value,
            CrmPermission::VOIP_RECORDINGS->value,
            CrmPermission::VOIP_LIVE_PANEL->value,
            CrmPermission::CALENDAR_VIEW->value,
            CrmPermission::CALENDAR_MANAGE->value,
            CrmPermission::COLLECTIONS_VIEW->value,
            CrmPermission::COLLECTIONS_COLLECT->value,
            CrmPermission::COLLECTIONS_ASSIGN->value,
            CrmPermission::COLLECTIONS_MANAGE->value,
            CrmPermission::COLLECTIONS_COMPLETE->value,
            CrmPermission::COLLECTIONS_CANCEL->value,
            CrmPermission::COLLECTIONS_REPORTS->value,
            CrmPermission::COLLECTIONS_METHODS_MANAGE->value,
            CrmPermission::BRANCHES_VIEW->value,
            CrmPermission::BRANCHES_UPDATE->value,
        ])->pluck('id')->all();
        $branchAdminGroup->permissions()->sync($branchAdminPerms);

        // 3. Manager
        $leaderGroup = Group::query()->firstOrCreate(
            ['code' => 'manager'],
            ['name' => 'مدير', 'description' => 'إشراف وإدارة فرق المبيعات والتحصيل والمتابعات والتقارير التشغيلية (Manager)', 'is_system' => true, 'is_active' => true]
        );
        $leaderPerms = Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_SCOPE_GROUP->value,
            CrmPermission::LEADS_ASSIGN->value,
            CrmPermission::LEADS_CREATE->value,
            CrmPermission::LEADS_UPDATE->value,
            CrmPermission::LEADS_EXPORT->value,
            CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
            CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
            CrmPermission::TASKS_VIEW->value,
            CrmPermission::CAMPAIGNS_VIEW->value,
            CrmPermission::CAMPAIGNS_REPORTS->value,
            CrmPermission::REPORTS_VIEW->value,
            CrmPermission::REPORTS_EMPLOYEES_VIEW->value,
            CrmPermission::VOIP_VIEW->value,
            CrmPermission::VOIP_RECORDINGS->value,
            CrmPermission::CALENDAR_VIEW->value,
            CrmPermission::CALENDAR_MANAGE->value,
            CrmPermission::COLLECTIONS_VIEW->value,
            CrmPermission::COLLECTIONS_ASSIGN->value,
            CrmPermission::COLLECTIONS_MANAGE->value,
            CrmPermission::COLLECTIONS_COMPLETE->value,
            CrmPermission::COLLECTIONS_CANCEL->value,
            CrmPermission::COLLECTIONS_REPORTS->value,
        ])->pluck('id')->all();
        $leaderGroup->permissions()->sync($leaderPerms);

        // 4. Collector
        $collectorGroup = Group::query()->firstOrCreate(
            ['code' => 'collector'],
            ['name' => 'محصل', 'description' => 'تنفيذ حالات التحصيل المسندة وتأكيد استلام التبرعات (Collector)', 'is_system' => true, 'is_active' => true]
        );
        $collectorPerms = Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::COLLECTIONS_VIEW->value,
            CrmPermission::COLLECTIONS_COLLECT->value,
            CrmPermission::COLLECTIONS_COMPLETE->value,
            CrmPermission::CALENDAR_VIEW->value,
            CrmPermission::TASKS_VIEW->value,
            CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
        ])->pluck('id')->all();
        $collectorGroup->permissions()->sync($collectorPerms);

        // 5. Employee
        $agentGroup = Group::query()->firstOrCreate(
            ['code' => 'employee'],
            ['name' => 'موظف', 'description' => 'التعامل اليومي مع العملاء والمتابعات والمهام والتقويم (Employee)', 'is_system' => true, 'is_active' => true]
        );
        $agentPerms = Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_CREATE->value,
            CrmPermission::LEADS_UPDATE->value,
            CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
            CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
            CrmPermission::TASKS_VIEW->value,
            CrmPermission::CALENDAR_VIEW->value,
            CrmPermission::VOIP_VIEW->value,
        ])->pluck('id')->all();
        $agentGroup->permissions()->sync($agentPerms);
        return [
            'super' => $superGroup,
            'branch_admin' => $branchAdminGroup,
            'leader' => $leaderGroup,
            'agent' => $agentGroup,
            'col_mgr' => $leaderGroup,
            'collector' => $collectorGroup,
        ];
    }

    private function seedUsersHierarchy(array $branches, array $groups): array
    {
        $pass = Hash::make(self::DEMO_PASSWORD);

        // 1. Super Admin (admin + demo.superadmin)
        $admin = User::query()->where('username', 'admin')->first();
        if ($admin) {
            $this->safeSetExtension($admin->id, '101');
            $admin->update([
                'password' => Hash::make('Admin@123'),
                'branch_id' => $branches['cairo_main']->id,
                'voip_extension' => '101',
                'is_active' => true,
            ]);
            $admin->groups()->sync([$groups['super']->id]);
        }

        $superUser = User::query()->where('username', 'demo.superadmin')->first();
        $superUserId = $superUser?->id;
        if ($superUserId) {
            $this->safeSetExtension($superUserId, '102');
        }
        $superUser = User::query()->updateOrCreate(
            ['username' => 'demo.superadmin'],
            [
                'name' => 'م. سكرات الألفي (Super Admin)',
                'email' => 'superadmin@sokratcrm.test',
                'password' => $pass,
                'branch_id' => $branches['cairo_main']->id,
                'voip_extension' => '102',
                'mobile_phone' => '01010000001',
                'is_active' => true,
            ]
        );
        $superUser->groups()->sync([$groups['super']->id]);
        // 2. Team Leaders (Sales)
        $this->safeSetExtensionByUsername('tarek.leader', '103');
        $leader1 = User::query()->updateOrCreate(
            ['username' => 'tarek.leader'],
            [
                'name' => 'طارق المنشاوي (قائد فريق مبيعات)',
                'email' => 'tarek.leader@sokratcrm.test',
                'password' => $pass,
                'branch_id' => $branches['cairo_main']->id,
                'voip_extension' => '103',
                'mobile_phone' => '01020000002',
                'manager_id' => $superUser->id,
                'is_active' => true,
            ]
        );
        $leader1->groups()->sync([$groups['leader']->id]);

        $this->safeSetExtensionByUsername('sara.leader', '111');
        $leader2 = User::query()->updateOrCreate(
            ['username' => 'sara.leader'],
            [
                'name' => 'سارة الشناوي (قائدة فريق الدلتا)',
                'email' => 'sara.leader@sokratcrm.test',
                'password' => $pass,
                'branch_id' => $branches['alex']->id,
                'voip_extension' => '111',
                'mobile_phone' => '01030000003',
                'manager_id' => $superUser->id,
                'is_active' => true,
            ]
        );
        $leader2->groups()->sync([$groups['leader']->id]);

        // 3. Sales Agents (Assigned to leaders)
        $agents = [];
        $agentDefs = [
            ['username' => 'ahmed.agent', 'name' => 'أحمد حسام الدين', 'ext' => '201', 'mgr' => $leader1, 'branch' => 'cairo_main'],
            ['username' => 'nour.agent', 'name' => 'نور الهدى إبراهيم', 'ext' => '202', 'mgr' => $leader1, 'branch' => 'cairo_main'],
            ['username' => 'omar.agent', 'name' => 'عمر عبد العزيز', 'ext' => '203', 'mgr' => $leader1, 'branch' => 'giza'],
            ['username' => 'mona.agent', 'name' => 'منى الصياد', 'ext' => '204', 'mgr' => $leader2, 'branch' => 'alex'],
            ['username' => 'khaled.agent', 'name' => 'خالد عبد الوهاب', 'ext' => '205', 'mgr' => $leader2, 'branch' => 'mansoura'],
        ];

        foreach ($agentDefs as $def) {
            $this->safeSetExtensionByUsername($def['username'], $def['ext']);
            $u = User::query()->updateOrCreate(
                ['username' => $def['username']],
                [
                    'name' => $def['name'],
                    'email' => $def['username'].'@sokratcrm.test',
                    'password' => $pass,
                    'branch_id' => $branches[$def['branch']]->id,
                    'voip_extension' => $def['ext'],
                    'mobile_phone' => '01050000' . $def['ext'],
                    'manager_id' => $def['mgr']->id,
                    'is_active' => true,
                ]
            );
            $u->groups()->sync([$groups['agent']->id]);
            $agents[] = $u;
        }

        // 4. Collection Managers
        $this->safeSetExtensionByUsername('nader.collection_mgr', '200');
        $colMgr = User::query()->updateOrCreate(
            ['username' => 'nader.collection_mgr'],
            [
                'name' => 'نادر الجيار (مدير التحصيل الميداني)',
                'email' => 'nader.col@sokratcrm.test',
                'password' => $pass,
                'branch_id' => $branches['cairo_main']->id,
                'voip_extension' => '200',
                'mobile_phone' => '01060000006',
                'collection_zone' => 'القاهرة الكبرى والجيزة',
                'manager_id' => $superUser->id,
                'is_active' => true,
            ]
        );
        $colMgr->groups()->sync([$groups['col_mgr']->id]);

        // 5. Field Collectors (Assigned to Collection Manager)
        $collectors = [];
        $collectorDefs = [
            ['username' => 'mostafa.collector', 'name' => 'مصطفى كمال (محصل مدينة نصر ومصر الجديدة)', 'zone' => 'مدينة نصر ومصر الجديدة والتجمع', 'branch' => 'cairo_main'],
            ['username' => 'youssef.collector', 'name' => 'يوسف الشافعي (محصل الجيزة والمهندسين)', 'zone' => 'الدقي والمهندسين وفيصل والهرم', 'branch' => 'giza'],
            ['username' => 'hassan.collector', 'name' => 'حسن عبد الفتاح (محصل الإسكندرية)', 'zone' => 'سموحة وسيدي جابر ومحرم بك', 'branch' => 'alex'],
        ];

        foreach ($collectorDefs as $def) {
            $col = User::query()->updateOrCreate(
                ['username' => $def['username']],
                [
                    'name' => $def['name'],
                    'email' => $def['username'].'@sokratcrm.test',
                    'password' => $pass,
                    'branch_id' => $branches[$def['branch']]->id,
                    'collection_zone' => $def['zone'],
                    'mobile_phone' => '010700000' . ($def['branch'] === 'alex' ? '3' : '1'),
                    'manager_id' => $colMgr->id,
                    'is_active' => true,
                ]
            );
            $col->groups()->sync([$groups['collector']->id]);
            $collectors[] = $col;
        }

        return [
            'super' => $superUser,
            'leaders' => [$leader1, $leader2],
            'agents' => $agents,
            'col_mgr' => $colMgr,
            'collectors' => $collectors,
            'all_staff' => array_merge([$superUser, $leader1, $leader2, $colMgr], $agents, $collectors),
        ];
    }

    private function safeSetExtension(int $userId, string $ext): void
    {
        User::query()->where('voip_extension', $ext)->where('id', '!=', $userId)->update(['voip_extension' => null]);
    }

    private function safeSetExtensionByUsername(string $username, string $ext): void
    {
        $existing = User::query()->where('username', $username)->first();
        $userId = $existing?->id ?? 0;
        User::query()->where('voip_extension', $ext)->where('id', '!=', $userId)->update(['voip_extension' => null]);
    }


    private function seedPipelineStagesAndStatuses(): array
    {
        $stageDefs = [
            ['code' => 'new', 'name_ar' => 'عملاء جدد', 'color' => '#3478f6', 'pos' => 1, 'is_primary' => true, 'icon' => 'bi-stars'],
            ['code' => 'no_answer', 'name_ar' => 'لم يتم الرد / للمعاودة', 'color' => '#e59b16', 'pos' => 2, 'is_primary' => true, 'icon' => 'bi-telephone-x'],
            ['code' => 'not_interested', 'name_ar' => 'غير مهتم', 'color' => '#dc2637', 'pos' => 3, 'is_primary' => true, 'icon' => 'bi-slash-circle'],
            ['code' => 'donor', 'name_ar' => 'متبرعون رسميون', 'color' => '#16a34a', 'pos' => 4, 'is_primary' => true, 'icon' => 'bi-heart-fill'],
            ['code' => 'vip_donors', 'name_ar' => 'كبار المتبرعين (VIP)', 'color' => '#8b5cf6', 'pos' => 5, 'is_primary' => false, 'icon' => 'bi-award-fill'],
        ];

        $stages = [];
        $statuses = [];

        foreach ($stageDefs as $def) {
            $stage = PipelineStage::query()->updateOrCreate(
                ['code' => $def['code']],
                [
                    'name_ar' => $def['name_ar'],
                    'color' => $def['color'],
                    'position' => $def['pos'],
                    'is_primary' => $def['is_primary'],
                    'icon' => $def['icon'],
                    'is_active' => true,
                ]
            );
            $stages[$def['code']] = $stage;

            $status = LeadStatus::query()->updateOrCreate(
                ['code' => $def['code']],
                [
                    'pipeline_stage_id' => $stage->id,
                    'name_ar' => $def['name_ar'],
                    'color' => $def['color'],
                    'position' => $def['pos'],
                    'is_terminal' => in_array($def['code'], ['not_interested', 'donor', 'vip_donors'], true),
                ]
            );
            $statuses[$def['code']] = $status;
        }

        return ['stages' => $stages, 'statuses' => $statuses];
    }

    private function seedDonationLookups(): array
    {
        $typeDefs = [
            ['name_ar' => 'كفالة أيتام', 'name_en' => 'Orphan Sponsorship', 'pos' => 1],
            ['name_ar' => 'زكاة مال وصدقات', 'name_en' => 'Zakat & Charity', 'pos' => 2],
            ['name_ar' => 'حفر آبار مياه نظيفة', 'name_en' => 'Clean Water Wells', 'pos' => 3],
            ['name_ar' => 'تجهيز مستشفيات وعمليات', 'name_en' => 'Medical Equipment & Surgeries', 'pos' => 4],
            ['name_ar' => 'كراتين إطعام ومواد غذائية', 'name_en' => 'Food Packages', 'pos' => 5],
            ['name_ar' => 'صدقة جارية عامة', 'name_en' => 'General Sadaqah Jariyah', 'pos' => 6],
        ];

        $types = [];
        foreach ($typeDefs as $d) {
            $types[] = DonationType::query()->updateOrCreate(
                ['name_ar' => $d['name_ar']],
                ['name_en' => $d['name_en'], 'position' => $d['pos'], 'is_active' => true]
            );
        }

        $purposeDefs = [
            ['name_ar' => 'مستشفى 57357 لعلاج الأطفال', 'name_en' => 'Children Cancer Hospital'],
            ['name_ar' => 'معهد الأورام القومي', 'name_en' => 'National Cancer Institute'],
            ['name_ar' => 'مبادرة حياة كريمة للقرى', 'name_en' => 'Decent Life Rural Initiative'],
            ['name_ar' => 'مشروع سقيا الماء بالصعيد', 'name_en' => 'Upper Egypt Water Project'],
            ['name_ar' => 'رعاية الأسر الأشد احتياجاً', 'name_en' => 'Neediest Families Support'],
        ];

        $purposes = [];
        foreach ($purposeDefs as $p) {
            $purposes[] = DonationPurpose::query()->updateOrCreate(
                ['name_ar' => $p['name_ar']],
                ['name_en' => $p['name_en'], 'is_active' => true]
            );
        }

        return ['types' => $types, 'purposes' => $purposes];
    }

    private function seedInstantDonationMethods(): array
    {
        $methods = [
            ['code' => 'instapay', 'name_ar' => 'إنستاباي (InstaPay)', 'name_en' => 'InstaPay Transfer', 'pos' => 10],
            ['code' => 'vodafone_cash', 'name_ar' => 'فودافون كاش', 'name_en' => 'Vodafone Cash Wallet', 'pos' => 20],
            ['code' => 'fawry', 'name_ar' => 'فوري وخدمات الدفع الإلكتروني', 'name_en' => 'Fawry Network', 'pos' => 30],
            ['code' => 'bank_transfer', 'name_ar' => 'تحويل بنكي مباشر (CIB / الأهلي / مصر)', 'name_en' => 'Direct Bank Wire', 'pos' => 40],
            ['code' => 'orange_cash', 'name_ar' => 'أورنج كاش', 'name_en' => 'Orange Cash Wallet', 'pos' => 50],
        ];

        $results = [];
        foreach ($methods as $m) {
            $results[] = InstantDonationMethod::query()->updateOrCreate(
                ['code' => $m['code']],
                ['name_ar' => $m['name_ar'], 'name_en' => $m['name_en'], 'position' => $m['pos'], 'is_active' => true]
            );
        }

        return $results;
    }

    private function seedLeads(array $branches, array $users, array $stagesAndStatuses, array $donationLookups): array
    {
        $egyptianFirstNames = ['أحمد', 'محمد', 'محمود', 'مصطفى', 'إبراهيم', 'طارق', 'خالد', 'سامح', 'ياسر', 'هشام', 'عمرو', 'كريم', 'حسام', 'عادل', 'ماجد', 'وائل', 'تامر', 'أشرف', 'مروان', 'زياد', 'فاطمة', 'مريم', 'ياسمين', 'سارة', 'نور', 'منى', 'آية', 'رانيا', 'دعاء', 'هدى', 'سميرة', 'إيمان', 'أميرة', 'ندى', 'هالة'];
        $egyptianLastNames = ['الشرافي', 'المنشاوي', 'الشناوي', 'الألفي', 'الجيار', 'الباز', 'الشافعي', 'عثمان', 'البدري', 'السعيد', 'غنيم', 'فهمي', 'الصاوي', 'الحداد', 'النجار', 'رضوان', 'التهامي', 'سليمان', 'مرسي', 'زهران', 'عفيفي', 'القاضي', 'الديب', 'الصياد', 'عطية', 'خفاجة', 'الدسوقي', 'المهدي', 'السيد', 'بركات'];
        $companies = ['مجموعة العز للصناعات', 'شركة النيل للتجارة والاستثمار', 'مؤسسة الأهرام للحلول الهندسية', 'دلتا للمقاولات العامة', 'الفرسان للتوريدات الطبية', 'الشروق للخدمات اللوجستية', 'الفهد للبرمجيات والنظم', 'النور للتوزيع والاستيراد', 'المروة للملابس الجاهزة', 'الأندلس للأغذية والمشروبات', null, null, null, null];
        $governorates = ['القاهرة', 'الجيزة', 'الإسكندرية', 'الدقهلية', 'القليوبية', 'الغربية', 'الشرقية', 'البحيرة'];
        $sources = ['إعلان فيسبوك', 'إعلان إنستجرام', 'ترشيح من متبرع سابق', 'اتصال مباشر بالكول سنتر', 'موقع المؤسسة الإلكتروني', 'زيارة المعرض السنوي', 'حملة رسائل واتساب'];

        $statuses = $stagesAndStatuses['statuses'];
        $allStaff = $users['all_staff'];
        $branchList = array_values($branches);
        $types = $donationLookups['types'];
        $purposes = $donationLookups['purposes'];

        $leads = [];
        $totalToCreate = 120;

        for ($i = 1; $i <= $totalToCreate; $i++) {
            $fName = $egyptianFirstNames[array_rand($egyptianFirstNames)];
            $lName = $egyptianLastNames[array_rand($egyptianLastNames)];
            $fullName = "{$fName} {$lName}";
            $company = $companies[array_rand($companies)];
            $gov = $governorates[array_rand($governorates)];
            $source = $sources[array_rand($sources)];
            $branch = $branchList[array_rand($branchList)];
            $assignedRep = $allStaff[array_rand($allStaff)];

            // Status distribution: 30% donor/vip, 30% new, 20% no_answer, 20% not_interested
            $randStatus = rand(1, 100);
            if ($randStatus <= 25) {
                $statusKey = 'donor';
            } elseif ($randStatus <= 35) {
                $statusKey = 'vip_donors';
            } elseif ($randStatus <= 65) {
                $statusKey = 'new';
            } elseif ($randStatus <= 85) {
                $statusKey = 'no_answer';
            } else {
                $statusKey = 'not_interested';
            }

            $status = $statuses[$statusKey] ?? $statuses['new'];
            $isDonor = in_array($statusKey, ['donor', 'vip_donors'], true);

            $dType = $types[array_rand($types)];
            $dPurpose = $purposes[array_rand($purposes)];
            $cycle = $isDonor ? ['monthly', 'quarterly', 'semi_annual', 'annual', 'one_time'][rand(0, 4)] : null;
            $donationVal = $isDonor ? ($statusKey === 'vip_donors' ? rand(10, 50) * 1000 : [300, 500, 750, 1000, 1500, 2000, 3500, 5000][rand(0, 7)]) : null;

            $disinterest = $statusKey === 'not_interested'
                ? ['الظروف المادية الحالية لا تسمح بالتبرع', 'يتبرع لجهات أخرى محددة', 'طلب عدم التواصل مستقبلاً', 'رقم خاطئ / غير مناسب'][rand(0, 3)]
                : null;

            $nextFollowup = null;
            if ($statusKey === 'new' || $statusKey === 'no_answer') {
                // Mix of overdue, today, upcoming
                $mod = rand(1, 3);
                if ($mod === 1) $nextFollowup = now()->subDays(rand(1, 4))->setTime(rand(10, 17), 30);
                elseif ($mod === 2) $nextFollowup = now()->setTime(rand(10, 18), 0);
                else $nextFollowup = now()->addDays(rand(1, 8))->setTime(rand(10, 17), 0);
            }

            $primaryPhone = '01' . [0, 1, 2, 5][rand(0, 3)] . rand(10000000, 99999999);
            $createdAt = now()->subDays(rand(1, 60));

            $lead = Lead::query()->create([
                'name' => $fullName,
                'first_name' => $fName,
                'last_name' => $lName,
                'company_name' => $company,
                'phone' => $primaryPhone,
                'email' => rand(0, 10) > 4 ? "lead_{$i}@donor-example.com" : null,
                'source' => $source,
                'branch_id' => $branch->id,
                'governorate' => $gov,
                'address' => "شارع الجمهورية - عمارة الأمل رقم " . rand(1, 50) . " - {$gov}",
                'lead_status_id' => $status->id,
                'assigned_user_id' => $assignedRep->id,
                'responding_user_id' => $assignedRep->id,
                'assigned_employee' => $assignedRep->name,
                'created_by' => $users['super']->name,
                'created_by_user_id' => $users['super']->id,
                'donation_type' => $dType->name_ar,
                'donation_type_id' => $dType->id,
                'donation_purpose' => $dPurpose->name_ar,
                'donation_purpose_id' => $dPurpose->id,
                'donation_cycle' => $cycle,
                'donation_value' => $donationVal,
                'disinterest_reason' => $disinterest,
                'contact_date' => $isDonor ? $createdAt->copy()->addDays(rand(1, 5)) : null,
                'next_follow_up_at' => $nextFollowup,
                'response_details' => $isDonor ? "تم التواصل والاتفاق على التبرع بـ {$donationVal} ج.م بدورة ({$cycle})." : "تم استلام الطلب وتعيين مسؤول التواصل.",
                'created_at' => $createdAt,
                'updated_at' => now(),
            ]);

            // Add secondary phone if random
            if (rand(1, 10) > 6) {
                LeadPhone::query()->create([
                    'lead_id' => $lead->id,
                    'phone' => '01' . [0, 1, 2, 5][rand(0, 3)] . rand(10000000, 99999999),
                    'label' => ['واتساب', 'العمل', 'منزل'][rand(0, 2)],
                    'is_primary' => false,
                ]);
            }

            $leads[] = $lead;
        }

        return $leads;
    }

    private function seedFollowupsAndHistories(array $leads, array $users, array $stagesAndStatuses): void
    {
        $staff = $users['all_staff'];
        $statuses = $stagesAndStatuses['statuses'];

        $notesPool = [
            'تم الاتصال بالعميل وأبدى رغبة كبيرة في المشاركة في كفالة الأيتام.',
            'العميل طلب إرسال تفاصيل الحسابات البنكية ومندوب التحصيل على واتساب.',
            'تمت المقابلة الشخصية في مقر الشركة وتم تسليم بروشور المشاريع الخيرية.',
            'تم تأكيد استلام التبرع الشهري وتحديث دورة التبرع.',
            'العميل كان في اجتماع وطلب إعادة الاتصال مساءً بعد الساعة 6.',
            'مكالمة هاتفية مثمرة لشرح حملة سقيا الماء الجارية وتحديد موعد حفر البئر.',
            'تم التواصل عبر واتساب وإرسال صور مشاريع الآبار المنفذة بنجاح.',
            'اعتذر العميل لظروف السفر وسيتم التواصل معه الشهر القادم.',
        ];

        foreach ($leads as $lead) {
            $followupCount = rand(1, 4);
            for ($f = 1; $f <= $followupCount; $f++) {
                $agent = $staff[array_rand($staff)];
                $channel = ['call', 'call', 'meeting', 'whatsapp', 'call'][rand(0, 4)];
                $fuDate = now()->subDays(rand(0, 45))->subHours(rand(1, 10));

                $targetStatus = $lead->status;
                if ($f === 1 && $lead->status->code !== 'new') {
                    $targetStatus = $statuses['new'];
                }

                LeadFollowup::query()->create([
                    'lead_id' => $lead->id,
                    'user_id' => $agent->id,
                    'employee_name' => $agent->name,
                    'communication_type' => $channel,
                    'outcome' => $notesPool[array_rand($notesPool)],
                    'from_status_id' => $statuses['new']->id,
                    'to_status_id' => $targetStatus->id,
                    'next_follow_up_at' => $lead->next_follow_up_at,
                    'followed_up_at' => $fuDate,
                    'created_at' => $fuDate,
                    'updated_at' => $fuDate,
                ]);

                LeadStatusHistory::query()->create([
                    'lead_id' => $lead->id,
                    'from_status_id' => $statuses['new']->id,
                    'to_status_id' => $targetStatus->id,
                    'changed_by' => $agent->name,
                    'changed_by_user_id' => $agent->id,
                    'note' => 'تحديث تلقائي عبر سجل المتابعة.',
                    'changed_at' => $fuDate,
                    'created_at' => $fuDate,
                    'updated_at' => $fuDate,
                ]);
            }
        }
    }

    private function seedDonationsLedger(array $leads, array $users, array $donationLookups, array $instantMethods): void
    {
        $staff = $users['all_staff'];
        $types = $donationLookups['types'];

        $donorLeads = array_filter($leads, static fn (Lead $l) => in_array($l->status?->code, ['donor', 'vip_donors'], true));

        foreach ($donorLeads as $lead) {
            $numDonations = rand(1, 3);
            for ($d = 1; $d <= $numDonations; $d++) {
                $agent = $staff[array_rand($staff)];
                $method = $instantMethods[array_rand($instantMethods)];
                $dType = $types[array_rand($types)];
                $amount = $lead->donation_value ?: [500, 1000, 1500, 2500, 5000][rand(0, 4)];
                $donatedDate = now()->subDays(rand(1, 40));

                Donation::query()->create([
                    'lead_id' => $lead->id,
                    'donation_type_id' => $dType->id,
                    'donation_type' => $dType->name_ar,
                    'amount' => $amount,
                    'cycle' => $lead->donation_cycle ?: 'one_time',
                    'donation_way' => 'instant',
                    'instant_donation_method_id' => $method->id,
                    'receipt_original_name' => "receipt_voucher_{$lead->id}_{$d}.pdf",
                    'donated_at' => $donatedDate,
                    'recorded_by_user_id' => $agent->id,
                    'created_at' => $donatedDate,
                    'updated_at' => $donatedDate,
                ]);
            }
        }
    }

    private function seedCollectionWorkflow(array $leads, array $users, array $branches, array $donationLookups): void
    {
        $collectors = $users['collectors'];
        $colMgr = $users['col_mgr'];
        $types = $donationLookups['types'];
        $branchList = array_values($branches);

        $collectionStatuses = [
            CollectionCase::STATUS_PENDING,
            CollectionCase::STATUS_ASSIGNED,
            CollectionCase::STATUS_SCHEDULED,
            CollectionCase::STATUS_COLLECTED,
            CollectionCase::STATUS_COLLECTED,
            CollectionCase::STATUS_FAILED,
        ];

        foreach (array_slice($leads, 0, 35) as $idx => $lead) {
            $status = $collectionStatuses[$idx % count($collectionStatuses)];
            $collector = $collectors[$idx % count($collectors)];
            $dType = $types[array_rand($types)];
            $branch = $branchList[array_rand($branchList)];
            $expected = [500, 1000, 1500, 2000, 3000, 5000][$idx % 6];
            $dueAt = now()->addDays(($idx % 12) - 4)->setTime(11 + ($idx % 6), 0);
            $isClosed = $status === CollectionCase::STATUS_COLLECTED;

            $case = CollectionCase::query()->create([
                'lead_id' => $lead->id,
                'branch_id' => $branch->id,
                'donation_type_id' => $dType->id,
                'donation_type' => $dType->name_ar,
                'expected_amount' => $expected,
                'cycle' => ['monthly', 'quarterly', 'one_time'][$idx % 3],
                'status' => $status,
                'due_at' => $dueAt,
                'collection_address' => $lead->address ?: 'شارع الثورة - مصر الجديدة - القاهرة',
                'notes' => 'يرجى التواصل بالهاتف قبل الحضور بنصف ساعة لاستلام المبلغ.',
                'assigned_collector_user_id' => $status === CollectionCase::STATUS_PENDING ? null : $collector->id,
                'created_by_user_id' => $colMgr->id,
                'assigned_by_user_id' => $status === CollectionCase::STATUS_PENDING ? null : $colMgr->id,
                'completed_by_user_id' => $isClosed ? $collector->id : null,
                'completed_at' => $isClosed ? $dueAt->copy()->addHours(1) : null,
                'created_at' => now()->subDays(10 + $idx),
                'updated_at' => now(),
            ]);

            CollectionActivity::query()->create([
                'collection_case_id' => $case->id,
                'actor_user_id' => $colMgr->id,
                'action' => 'created',
                'from_status' => null,
                'to_status' => CollectionCase::STATUS_PENDING,
                'notes' => 'تم إنشاء حالة التحصيل وتجهيز خط السير.',
                'created_at' => now()->subDays(10 + $idx),
            ]);

            if ($status !== CollectionCase::STATUS_PENDING) {
                CollectionActivity::query()->create([
                    'collection_case_id' => $case->id,
                    'actor_user_id' => $colMgr->id,
                    'action' => 'assigned',
                    'from_status' => CollectionCase::STATUS_PENDING,
                    'to_status' => $status,
                    'notes' => "تم إسناد الحالة إلى المحصل ({$collector->name}).",
                    'new_collector_user_id' => $collector->id,
                    'created_at' => now()->subDays(8 + $idx),
                ]);
            }
        }
    }

    private function seedCampaigns(array $branches, array $users, array $leads): void
    {
        $campaignDefs = [
            [
                'name' => 'حملة سقيا الماء وحفر آبار الصعيد 2026',
                'cost' => 150000.00,
                'starts_at' => now()->subMonths(2)->startOfMonth(),
                'ends_at' => now()->addMonths(1)->endOfMonth(),
                'branch' => 'cairo_main',
            ],
            [
                'name' => 'حملة رعاية وتجهيز كفالات الأيتام السنوية',
                'cost' => 95000.00,
                'starts_at' => now()->subMonth()->startOfMonth(),
                'ends_at' => now()->addMonths(2)->endOfMonth(),
                'branch' => 'alex',
            ],
            [
                'name' => 'حملة التجهيزات الطبية وغرف العمليات الجراحية',
                'cost' => 250000.00,
                'starts_at' => now()->subMonths(3)->startOfMonth(),
                'ends_at' => now()->subDays(5)->endOfDay(), // ended
                'branch' => 'giza',
            ],
            [
                'name' => 'حملة الشتاء الدافئ وتوزيع البطاطين والملابس',
                'cost' => 75000.00,
                'starts_at' => now()->subMonths(4)->startOfMonth(),
                'ends_at' => now()->subMonths(1)->endOfMonth(), // ended
                'branch' => 'mansoura',
            ],
        ];

        $super = $users['super'];
        $staff = $users['all_staff'];

        foreach ($campaignDefs as $idx => $def) {
            $branch = $branches[$def['branch']] ?? null;
            $campaign = Campaign::query()->updateOrCreate(
                ['name' => $def['name']],
                [
                    'branch_id' => $branch?->id,
                    'cost' => $def['cost'],
                    'starts_at' => $def['starts_at'],
                    'ends_at' => $def['ends_at'],
                    'created_by_user_id' => $super->id,
                ]
            );
            // Attach staff members
            $attachedStaff = array_slice($staff, $idx * 2, 4);
            if ($attachedStaff !== []) {
                $campaign->users()->sync(array_map(static fn ($u) => $u->id, $attachedStaff));
            }

            // Attach donor and new leads
            $attachedLeads = array_slice($leads, $idx * 15, 20);
            if ($attachedLeads !== []) {
                $campaign->leads()->sync(array_map(static fn ($l) => $l->id, $attachedLeads));
            }
        }
    }

    private function seedCalendarEvents(array $branches, array $users, array $leads): void
    {
        $allStaff = $users['all_staff'];
        $branchList = array_values($branches);

        $eventTitles = [
            ['title' => 'اجتماع مراجعة تبرعات كبار الشخصيات', 'type' => 'meeting', 'dur' => 60],
            ['title' => 'مكالمة متابعة مع رئيس مجلس إدارة شركة النيل', 'type' => 'call', 'dur' => 30],
            ['title' => 'مراجعة وتحديث خطة التحصيل الأسبوعية', 'type' => 'task', 'dur' => 45],
            ['title' => 'تذكير: إرسال الإيصالات المالية المعتمدة', 'type' => 'reminder', 'dur' => 15],
            ['title' => 'زيارة ميدانية لموقع حفر بئر مياه الفيوم', 'type' => 'meeting', 'dur' => 120],
            ['title' => 'جلسة تقييم أداء مسؤولي التواصل المبيعات', 'type' => 'meeting', 'dur' => 90],
        ];

        for ($e = 1; $e <= 25; $e++) {
            $def = $eventTitles[array_rand($eventTitles)];
            $staff = $allStaff[array_rand($allStaff)];
            $lead = $leads[array_rand($leads)];
            $branch = $branchList[array_rand($branchList)];

            $startTime = now()->addDays(rand(-7, 14))->setTime(rand(9, 17), [0, 30][rand(0, 1)]);
            $endTime = (clone $startTime)->addMinutes($def['dur']);

            CalendarEvent::query()->create([
                'branch_id' => $branch->id,
                'user_id' => $staff->id,
                'lead_id' => rand(1, 10) > 3 ? $lead->id : null,
                'title' => $def['title'],
                'description' => 'تفاصيل الحدث: ' . $def['title'] . ' ومتابعة التوصيات التنفيذية.',
                'start_time' => $startTime,
                'end_time' => $endTime,
                'type' => $def['type'],
                'status' => $startTime->isPast() ? 'completed' : 'scheduled',
                'reminder_minutes_before' => 15,
            ]);
        }
    }
}
