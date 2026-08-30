<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DonationPurpose;
use App\Models\DonationType;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadPhone;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
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
        ];

        $users = [];

        foreach ($userDefinitions as $definition) {
            $user = User::query()->updateOrCreate(
                ['username' => $definition['username']],
                [
                    'name' => $definition['name'],
                    'email' => $definition['email'],
                    'password' => $passwordHash,
                    'is_active' => true,
                ]
            );

            $group = Group::query()->where('code', $definition['group'])->first();
            if ($group !== null) {
                $user->groups()->syncWithoutDetaching([$group->id]);
            }

            $users[$definition['key']] = $user;
        }

        return $users;
    }

    /**
     * @param array<string, User> $users
     */
    private function seedDemoLeads(array $users): void
    {
        $statusMap = LeadStatus::query()->pluck('id', 'code')->all();
        $donationTypes = DonationType::query()->where('is_active', true)->get();
        $donationPurposes = DonationPurpose::query()->where('is_active', true)->get();

        // 100 leads status breakdown for the 4 canonical statuses:
        // Stage 1 (new):
        // 40 New ('new')
        // 20 No Answer ('no_answer')
        // 5 Not Interested ('not_interested')
        // Stage 2 (donor):
        // 35 Donor ('donor')
        $statusDistribution = array_merge(
            // 65% in "new" stage
            array_fill(0, 40, 'new'),
            array_fill(0, 20, 'no_answer'),
            array_fill(0, 5, 'not_interested'),
            // 35% in "donor" stage
            array_fill(0, 35, 'donor')
        );

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

        $governorates = [
            'القاهرة', 'الجيزة', 'الإسكندرية', 'المنصورة', 'طنطا', 'أسيوط', 'الزقازيق', 'بورسعيد', 'السويس', 'الإسماعيلية',
        ];

        $sources = [
            'موقع إلكتروني', 'توصية عميل', 'حملة فيسبوك', 'إعلان جوجل', 'معرض تجاري', 'تواصل مباشر', 'واتساب',
        ];

        $cycles = ['one_time', 'monthly', 'quarterly', 'semi_annual', 'annual'];
        $values = [250, 500, 1000, 1500, 2000, 3000, 5000, 10000, 25000];

        $responseResponses = [
            'تم التواصل وشرح برامج التبرع وأبدى العميل اهتماماً كبيراً بالمساهمة الشهرية.',
            'تم الاتصال ولم يتم الرد، وسيتم إعادة المحاولة في موعد المتابعة القادم.',
            'أكد المتبرع رغبته في تحويل مبلغ الزكاة وسيقوم بإرسال الإيصال.',
            'طلب العميل إرسال تفاصيل الحالات الحرجة عبر الواتساب للاطلاع عليها.',
            'تم الاتفاق على كفالة طفلين شهرياً وتحديث وسيلة الدفع.',
            'أفاد العميل بأنه غير قادر على التبرع في الوقت الحالي لظروف خاصة.',
        ];

        $relationshipTypes = ['زوج / زوجة', 'ابن / ابنة', 'مندوب العميل', 'محاسب العميل', 'شريك', 'مساعد'];

        $creatorUser = $users['superadmin'];

        for ($i = 0; $i < 100; $i++) {
            $leadNumber = sprintf('%03d', $i + 1);
            $email = "customer{$leadNumber}.demo@" . self::DEMO_EMAIL_DOMAIN;

            $firstName = $firstNames[$i % count($firstNames)];
            $lastName = $lastNames[($i * 3) % count($lastNames)];
            $fullName = "{$firstName} {$lastName}";

            // Phone format: 010, 011, 012, 015 + 8 digits
            $prefixes = ['010', '011', '012', '015'];
            $phonePrefix = $prefixes[$i % count($prefixes)];
            $phoneDigits = sprintf('%08d', 10000000 + ($i * 123457) % 89999999);
            $phone = $phonePrefix . $phoneDigits;

            $statusCode = $statusDistribution[$i];
            $statusId = $statusMap[$statusCode] ?? array_values($statusMap)[0];

            $assignedUser = $assigneesList[$i];

            $daysAgo = (int) floor(($i * 1.8) + ($i % 3));
            $createdAt = Carbon::now()->subDays($daysAgo)->subHours($i % 24)->subMinutes(($i * 7) % 60);

            $nextFollowUpAt = null;
            if (! in_array($statusCode, ['not_interested', 'completed'], true)) {
                $nextFollowUpAt = (clone $createdAt)->addDays(($i % 10) + 2)->setHour(10 + ($i % 6));
            }

            $donationTypeObj = $donationTypes->isNotEmpty() ? $donationTypes[$i % $donationTypes->count()] : null;
            $donationPurposeObj = $donationPurposes->isNotEmpty() ? $donationPurposes[$i % $donationPurposes->count()] : null;
            $donationCycle = $cycles[$i % count($cycles)];
            $donationVal = $values[$i % count($values)];
            $responseDetail = $responseResponses[$i % count($responseResponses)];

            $lead = Lead::query()->updateOrCreate(
                ['email' => $email],
                [
                    'lead_status_id' => $statusId,
                    'name' => $fullName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'governorate' => $governorates[$i % count($governorates)],
                    'address' => 'شارع ' . ($i + 1) . ' - ' . $governorates[$i % count($governorates)],
                    'phone' => $phone,
                    'source' => $sources[$i % count($sources)],
                    'assigned_employee' => $assignedUser->name,
                    'assigned_user_id' => $assignedUser->id,
                    'responding_user_id' => $assignedUser->id,
                    'created_by' => $creatorUser->name,
                    'created_by_user_id' => $creatorUser->id,
                    'donation_type' => $donationTypeObj?->name_ar,
                    'donation_type_id' => $donationTypeObj?->id,
                    'donation_cycle' => $donationCycle,
                    'donation_value' => $donationVal,
                    'donation_purpose' => $donationPurposeObj?->name_ar,
                    'donation_purpose_id' => $donationPurposeObj?->id,
                    'response_details' => $responseDetail,
                    'contact_date' => (clone $createdAt)->addHours(2),
                    'notes' => 'سجل متبرع توضيحي (Demo Customer #' . $leadNumber . ')',
                    'next_follow_up_at' => $nextFollowUpAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );

            // Sync Primary Phone in lead_phones
            LeadPhone::query()->updateOrCreate(
                [
                    'lead_id' => $lead->id,
                    'phone' => $phone,
                ],
                [
                    'is_primary' => true,
                    'label' => 'أساسي',
                ]
            );

            // Add secondary phone for some leads
            if ($i % 3 === 0) {
                $secPrefix = $prefixes[($i + 1) % count($prefixes)];
                $secDigits = sprintf('%08d', 20000000 + ($i * 765432) % 79999999);
                $secPhone = $secPrefix . $secDigits;

                LeadPhone::query()->updateOrCreate(
                    [
                        'lead_id' => $lead->id,
                        'phone' => $secPhone,
                    ],
                    [
                        'is_primary' => false,
                        'label' => $i % 2 === 0 ? 'واتساب' : 'عمل',
                    ]
                );
            }


            // Initial status history
            LeadStatusHistory::query()->firstOrCreate(
                [
                    'lead_id' => $lead->id,
                    'to_status_id' => $statusId,
                ],
                [
                    'from_status_id' => null,
                    'changed_by_user_id' => $creatorUser->id,
                    'changed_by' => $creatorUser->name,
                    'changed_at' => $createdAt,
                    'note' => 'إنشاء سجل العميل المتبرع الأولي',
                ]
            );
        }
    }
}
