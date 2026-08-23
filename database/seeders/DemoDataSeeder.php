<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Demo@123456';
    public const DEMO_EMAIL_DOMAIN = 'example.test';

    public function run(): void
    {
        DB::transaction(function (): void {
            $users = $this->seedDemoUsers();
            $this->seedDemoLeads($users);
        });
    }

    /**
     * @return array<string, User>
     */
    private function seedDemoUsers(): array
    {
        $passwordHash = Hash::make(self::DEMO_PASSWORD);

        $userDefinitions = [
            [
                'key' => 'superadmin',
                'name' => 'Super Admin Demo',
                'username' => 'demo.superadmin',
                'email' => 'superadmin.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => Group::SUPER_ADMIN_CODE,
            ],
            [
                'key' => 'admin1',
                'name' => 'Admin Demo 1',
                'username' => 'demo.admin1',
                'email' => 'admin1.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => Group::SUPER_ADMIN_CODE,
            ],
            [
                'key' => 'admin2',
                'name' => 'Admin Demo 2',
                'username' => 'demo.admin2',
                'email' => 'admin2.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => Group::SUPER_ADMIN_CODE,
            ],
            [
                'key' => 'manager1',
                'name' => 'Manager Demo 1',
                'username' => 'demo.manager1',
                'email' => 'manager1.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => 'sales-manager',
            ],
            [
                'key' => 'manager2',
                'name' => 'Manager Demo 2',
                'username' => 'demo.manager2',
                'email' => 'manager2.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => 'sales-manager',
            ],
            [
                'key' => 'teamleader1',
                'name' => 'Team Leader Demo 1',
                'username' => 'demo.teamleader1',
                'email' => 'teamleader1.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => 'sales-manager',
            ],
            [
                'key' => 'teamleader2',
                'name' => 'Team Leader Demo 2',
                'username' => 'demo.teamleader2',
                'email' => 'teamleader2.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => 'sales-manager',
            ],
            [
                'key' => 'teamleader3',
                'name' => 'Team Leader Demo 3',
                'username' => 'demo.teamleader3',
                'email' => 'teamleader3.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => 'sales-manager',
            ],
            [
                'key' => 'agent1',
                'name' => 'Sales Agent Demo 1',
                'username' => 'demo.agent1',
                'email' => 'agent1.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => 'sales-agent',
            ],
            [
                'key' => 'agent2',
                'name' => 'Sales Agent Demo 2',
                'username' => 'demo.agent2',
                'email' => 'agent2.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => 'sales-agent',
            ],
            [
                'key' => 'agent3',
                'name' => 'Sales Agent Demo 3',
                'username' => 'demo.agent3',
                'email' => 'agent3.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => 'sales-agent',
            ],
            [
                'key' => 'agent4',
                'name' => 'Sales Agent Demo 4',
                'username' => 'demo.agent4',
                'email' => 'agent4.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => 'sales-agent',
            ],
            [
                'key' => 'agent5',
                'name' => 'Sales Agent Demo 5',
                'username' => 'demo.agent5',
                'email' => 'agent5.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => 'sales-agent',
            ],
            [
                'key' => 'support1',
                'name' => 'Support Demo 1',
                'username' => 'demo.support1',
                'email' => 'support1.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => 'read-only',
            ],
            [
                'key' => 'support2',
                'name' => 'Support Demo 2',
                'username' => 'demo.support2',
                'email' => 'support2.demo@' . self::DEMO_EMAIL_DOMAIN,
                'group' => 'read-only',
            ],
        ];

        $groups = Group::query()->pluck('id', 'code');
        $usersMap = [];

        foreach ($userDefinitions as $def) {
            /** @var User $user */
            $user = User::query()->updateOrCreate(
                ['email' => $def['email']],
                [
                    'name' => $def['name'],
                    'username' => $def['username'],
                    'password' => $passwordHash,
                    'is_active' => true,
                ]
            );

            if (isset($groups[$def['group']])) {
                $user->groups()->syncWithoutDetaching([$groups[$def['group']]]);
            }

            $usersMap[$def['key']] = $user;
        }

        return $usersMap;
    }

    /**
     * @param array<string, User> $users
     */
    private function seedDemoLeads(array $users): void
    {
        $statusMap = LeadStatus::query()->pluck('id', 'code')->all();

        // 100 leads status breakdown:
        // 25 New ('new')
        // 20 Contacted / No Answer ('no_answer')
        // 15 Interested / Qualified ('interested')
        // 15 Follow-up / Meeting ('meeting')
        // 10 Negotiation ('quotation': 5, 'discussion': 5)
        // 10 Converted / Contract / Execution ('contract_closed': 5, 'execution': 5)
        // 5 Not Interested / Lost ('not_interested')
        $statusDistribution = array_merge(
            array_fill(0, 25, 'new'),
            array_fill(0, 20, 'no_answer'),
            array_fill(0, 15, 'interested'),
            array_fill(0, 15, 'meeting'),
            array_fill(0, 5, 'quotation'),
            array_fill(0, 5, 'discussion'),
            array_fill(0, 5, 'contract_closed'),
            array_fill(0, 5, 'execution'),
            array_fill(0, 5, 'not_interested')
        );

        // Assignees distribution (100 total unevenly across Sales Agents, Team Leaders, Managers):
        // Agent 1: 18
        // Agent 2: 16
        // Agent 3: 14
        // Agent 4: 12
        // Agent 5: 11
        // Team Leader 1: 9
        // Team Leader 2: 8
        // Team Leader 3: 6
        // Manager 1: 4
        // Manager 2: 2
        $assigneesList = array_merge(
            array_fill(0, 18, $users['agent1']),
            array_fill(0, 16, $users['agent2']),
            array_fill(0, 14, $users['agent3']),
            array_fill(0, 12, $users['agent4']),
            array_fill(0, 11, $users['agent5']),
            array_fill(0, 9, $users['teamleader1']),
            array_fill(0, 8, $users['teamleader2']),
            array_fill(0, 6, $users['teamleader3']),
            array_fill(0, 4, $users['manager1']),
            array_fill(0, 2, $users['manager2'])
        );

        $firstNames = [
            'أحمد', 'محمد', 'محمود', 'مصطفى', 'إبراهيم', 'علي', 'السيد', 'عمر', 'طارق', 'كريم',
            'سامح', 'حازم', 'خالد', 'شريف', 'وائل', 'رامي', 'يوسف', 'حسن', 'حسين', 'عمرو',
            'سارة', 'مريم', 'منى', 'رانيا', 'ياسمين', 'نورهان', 'أمل', 'ريم', 'داليا', 'هبة',
            'فاطمة', 'شيرين', 'آية', 'نهى', 'دينا', 'مي', 'أسماء', 'هناء', 'رباب', 'غادة',
            'أشرف', 'أيمن', 'تامر', 'وليد', 'إيهاب', 'عادل', 'ماهر', 'مجدي', 'سامي', 'مدحت',
        ];

        $lastNames = [
            'الشرافي', 'المنسي', 'عبد الرحمن', 'حسن', 'محمود', 'عبد العزيز', 'مصطفى', 'سليمان', 'بدوي', 'القاضي',
            'فؤاد', 'سالم', 'الصاوي', 'الخضري', 'زكي', 'توفيق', 'عبد الله', 'زهران', 'صبري', 'شرف',
            'العوضي', 'الشاعر', 'النارسي', 'فاروق', 'عثمان', 'رضوان', 'فهمي', 'الجزار', 'منصور', 'الباز',
            'العدوي', 'غانم', 'حماد', 'بركات', 'خليل', 'أبو النصر', 'سعيد', 'راشد', 'مبارك', 'الشافعي',
        ];

        $companyPrefixes = [
            'شركة', 'مجموعة', 'مؤسسة', 'مركز', 'شركة النيل لـ', 'الشركة المصرية لـ', 'القمة لـ', 'أفق لـ',
        ];

        $companyNouns = [
            'الحلول البرمجية', 'التوريدات العامة', 'الاستثمار العقاري', 'الصناعات الغذائية', 'الخدمات اللوجستية',
            'التطوير التكنولوجي', 'المقاولات العامة', 'الحلول المتكاملة', 'الإعلام والتسوق', 'الاستشارات الإدارية',
            'الاتصالات وتقنية المعلومات', 'الهندسة الحديثة', 'الاستيراد والتصدير', 'التوزيع والخدمات', 'الشحن الدولي',
            'إدارة المنشآت', 'التصنيع الدقيق', 'التسويق الرقمي', 'حلول الطاقة', 'الرعاية الصحية',
        ];

        $governorates = [
            'القاهرة', 'الجيزة', 'الإسكندرية', 'المنصورة', 'طنطا', 'أسيوط', 'الزقازيق', 'بورسعيد', 'السويس', 'الإسماعيلية',
        ];

        $activities = [
            'تكنولوجيا المعلومات', 'المقاولات والبناء', 'التجارة والتوزيع', 'الاستثمار العقاري', 'الصناعات الغذائية',
            'الخدمات اللوجستية', 'الاستشارات الهندسية', 'التسويق والإعلام', 'الاستيراد والتصدير', 'الرعاية الصحية',
        ];

        $sources = [
            'موقع إلكتروني', 'توصية عميل', 'حملة فيسبوك', 'إعلان جوجل', 'معرض تجاري', 'تواصل مباشر', 'لينكد إن',
        ];

        // Max 20 chars for solution_type (varchar(20))
        $solutionTypes = [
            'CRM سحابي',
            'إدارة مبيعات',
            'نظام ERP',
            'حسابات ومخازن',
            'إدارة عملاء',
            'كول سنتر',
        ];

        $jobTitles = [
            'مدير عام', 'مدير المشتريات', 'مدير تكنولوجيا المعلومات', 'مدير المبيعات', 'الرئيس التنفيذي',
            'صاحب الشركة', 'مدير العمليات', 'مدير التخطيط',
        ];

        $disinterestReasons = [
            'ارتفاع السعر مقارنة بالميزانية',
            'عدم الحاجة للنظام في الوقت الحالي',
            'التعاقد مع شركة منافسة مؤخراً',
            'تأجيل المشروع للربع القادم',
            'تغيير نشاط الشركة',
        ];

        $creatorUser = $users['superadmin'];

        for ($i = 0; $i < 100; $i++) {
            $leadNumber = sprintf('%03d', $i + 1);
            $email = "customer{$leadNumber}.demo@" . self::DEMO_EMAIL_DOMAIN;

            $firstName = $firstNames[$i % count($firstNames)];
            $lastName = $lastNames[($i * 3) % count($lastNames)];
            $fullName = "{$firstName} {$lastName}";

            $companyName = $companyPrefixes[$i % count($companyPrefixes)] . ' ' . $companyNouns[($i * 7) % count($companyNouns)];

            // Phone format: 010, 011, 012, 015 + 8 digits
            $prefixes = ['010', '011', '012', '015'];
            $phonePrefix = $prefixes[$i % count($prefixes)];
            $phoneDigits = sprintf('%08d', 10000000 + ($i * 123457) % 89999999);
            $phone = $phonePrefix . $phoneDigits;

            $statusCode = $statusDistribution[$i];
            $statusId = $statusMap[$statusCode];

            $assignedUser = $assigneesList[$i];

            // Spread created_at across last 180 days (6 months)
            // Evenly staggered back in time
            $daysAgo = (int) floor(($i * 1.8) + ($i % 3));
            $createdAt = Carbon::now()->subDays($daysAgo)->subHours($i % 24)->subMinutes(($i * 7) % 60);

            $nextFollowUpAt = null;
            if (! in_array($statusCode, ['not_interested', 'execution'], true)) {
                $nextFollowUpAt = (clone $createdAt)->addDays(($i % 10) + 2)->setHour(10 + ($i % 6));
            }

            $disinterestReason = null;
            if ($statusCode === 'not_interested') {
                $disinterestReason = $disinterestReasons[$i % count($disinterestReasons)];
            }

            $quotationSent = in_array($statusCode, ['quotation', 'discussion', 'contract_closed', 'execution'], true);

            Lead::query()->updateOrCreate(
                ['email' => $email],
                [
                    'lead_status_id' => $statusId,
                    'name' => $fullName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'company_name' => $companyName,
                    'activity' => $activities[$i % count($activities)],
                    'governorate' => $governorates[$i % count($governorates)],
                    'address' => 'شارع ' . ($i + 1) . ' - ' . $governorates[$i % count($governorates)],
                    'users_count' => ($i % 15) + 2,
                    'branches_count' => ($i % 5) + 1,
                    'job_title' => $jobTitles[$i % count($jobTitles)],
                    'disinterest_reason' => $disinterestReason,
                    'solution_type' => $solutionTypes[$i % count($solutionTypes)],
                    'lines_count' => ($i % 8) + 1,
                    'extensions' => 'داخلي ' . (($i % 20) + 101),
                    'departments' => 'المبيعات والحسابات',
                    'phone' => $phone,
                    'source' => $sources[$i % count($sources)],
                    'quotation_sent' => $quotationSent,
                    'assigned_employee' => $assignedUser->name,
                    'assigned_user_id' => $assignedUser->id,
                    'created_by' => $creatorUser->name,
                    'created_by_user_id' => $creatorUser->id,
                    'notes' => 'بيانات عميل توضيحي (Demo Customer #' . $leadNumber . ')',
                    'next_follow_up_at' => $nextFollowUpAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );
        }
    }
}
