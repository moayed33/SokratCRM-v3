<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CalendarEventSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        if ($users->isEmpty()) {
            $this->call(DemoDataSeeder::class);
            $users = User::all();
        }

        $leads = Lead::all();
        if ($leads->isEmpty()) {
            $this->call(DemoDataSeeder::class);
            $leads = Lead::all();
        }

        $eventsData = [
            // Meetings
            [
                'title' => 'جلسة استشارة قانونية عامة',
                'description' => 'مناقشة المتطلبات القانونية للشركة وتحديد أطر التعامل والخدمات المستقبلية.',
                'type' => 'meeting',
                'duration' => 60,
            ],
            [
                'title' => 'متابعة عقد تأسيس شركة',
                'description' => 'اجتماع لمراجعة صياغة ملحق عقد التأسيس وحصص الشركاء وتوزيع الأرباح.',
                'type' => 'meeting',
                'duration' => 90,
            ],
            [
                'title' => 'اجتماع توقيع اتفاقية عدم الإفصاح (NDA)',
                'description' => 'حضور ممثلي الطرفين لتوقيع اتفاقية سرية المعلومات قبل بدء المفاوضات التجارية.',
                'type' => 'meeting',
                'duration' => 45,
            ],
            [
                'title' => 'جلسة مفاوضات تسوية نزاع شركاء',
                'description' => 'اجتماع مع محامي الطرف الآخر للوصول إلى اتفاق تسوية ودية وتجنب التقاضي.',
                'type' => 'meeting',
                'duration' => 120,
            ],
            [
                'title' => 'اجتماع مناقشة الاستحواذ والاندماج',
                'description' => 'عرض نتائج الفحص النافي للجهالة (Due Diligence) وتقييم المخاطر القانونية للشركة المستهدفة.',
                'type' => 'meeting',
                'duration' => 90,
            ],
            [
                'title' => 'مراجعة بنود العقد التجاري',
                'description' => 'دراسة الشروط الجزائية وضمانات التوريد والمسؤوليات مع القسم القانوني للعميل.',
                'type' => 'meeting',
                'duration' => 60,
            ],
            [
                'title' => 'جلسة عمل لوضع استراتيجية القضية',
                'description' => 'مناقشة خطة الدفاع وتحليل الأدلة والأسانيد الشرعية والقانونية المتاحة.',
                'type' => 'meeting',
                'duration' => 90,
            ],
            [
                'title' => 'اجتماع مراجعة لائحة العمل الداخلية',
                'description' => 'تحديث اللائحة التنفيذية وقواعد العمل لتتوافق مع تعديلات نظام العمل الأخير.',
                'type' => 'meeting',
                'duration' => 60,
            ],
            [
                'title' => 'تقديم عرض خدمات الاستشارات القانونية',
                'description' => 'تقديم العرض الفني والمالي للعميل المحتمل وتوضيح نطاق العمل ونظام الساعات.',
                'type' => 'meeting',
                'duration' => 45,
            ],
            [
                'title' => 'اجتماع تسليم التقرير القانوني النهائي',
                'description' => 'تسليم تقرير مراجعة العقود والتدقيق التنظيمي للإدارة التنفيذية للعميل.',
                'type' => 'meeting',
                'duration' => 60,
            ],

            // Calls
            [
                'title' => 'اتصال هاتفي لمراجعة المستندات',
                'description' => 'التواصل مع العميل للتأكد من استيفاء وثائق الهوية والوكالات الشرعية المطلوبة.',
                'type' => 'call',
                'duration' => 30,
            ],
            [
                'title' => 'مكالمة استكشافية لتقييم الاحتياجات القانونية',
                'description' => 'اتصال أولي لمعرفة طبيعة نشاط الشركة والخدمات القانونية الأكثر ملاءمة لها.',
                'type' => 'call',
                'duration' => 30,
            ],
            [
                'title' => 'اتصال لمتابعة توقيع الموكل',
                'description' => 'تذكير العميل بإعادة إرسال النسخة الموقعة من عقد تقديم الخدمات والوكالة.',
                'type' => 'call',
                'duration' => 15,
            ],
            [
                'title' => 'مكالمة هاتفية بشأن تحديثات القضية',
                'description' => 'إبلاغ العميل بصدور قرار الدائرة القضائية والموعد المحدد للجلسة القادمة.',
                'type' => 'call',
                'duration' => 20,
            ],
            [
                'title' => 'متابعة السداد والتحصيل مع الحسابات',
                'description' => 'اتصال مع المدير المالي للعميل للتأكيد على تحويل الدفعة المقدمة حسب العقد.',
                'type' => 'call',
                'duration' => 15,
            ],
            [
                'title' => 'مكالمة تنسيقية مع الشريك القانوني',
                'description' => 'مناقشة المسودة الأولى للائحة الاعتراضية قبل اعتمادها وتوثيقها.',
                'type' => 'call',
                'duration' => 30,
            ],
            [
                'title' => 'استفسار هاتفي حول ترخيص الفرع الجديد',
                'description' => 'الرد على تساؤلات العميل بشأن متطلبات وزارة التجارة والاستثمار.',
                'type' => 'call',
                'duration' => 25,
            ],
            [
                'title' => 'مكالمة متابعة تنفيذ بنود الاتفاقية',
                'description' => 'التأكد من التزام الطرف الآخر بجدول التوريد والتسليم المحدد.',
                'type' => 'call',
                'duration' => 20,
            ],
            [
                'title' => 'اتصال لتأكيد موعد الاجتماع القادم',
                'description' => 'التأكد من حضور ممثل الشركة القانوني لاجتماع يوم غد وتأكيد المكان.',
                'type' => 'call',
                'duration' => 10,
            ],
            [
                'title' => 'مكالمة تقييم جودة الخدمة القانونية',
                'description' => 'استطلاع رأي العميل حول الخدمات المقدمة وسرعة استجابة الفريق.',
                'type' => 'call',
                'duration' => 15,
            ],

            // Tasks
            [
                'title' => 'إعداد مسودة مذكرات الدفاع',
                'description' => 'كتابة المذكرة الشارحة والأسانيد النظامية تمهيداً لتقديمها في منصة ناجز.',
                'type' => 'task',
                'duration' => 120,
            ],
            [
                'title' => 'صياغة عقد إيجار تجاري',
                'description' => 'إعداد صياغة متكاملة تتضمن الحقوق والتزام الصيانة والالتزامات المالية.',
                'type' => 'task',
                'duration' => 90,
            ],
            [
                'title' => 'مراجعة حقوق الامتياز التجاري (Franchise)',
                'description' => 'فحص افصاح الامتياز التجاري والتأكد من مطابقته لنظام الامتياز التجاري.',
                'type' => 'task',
                'duration' => 90,
            ],
            [
                'title' => 'دراسة ملف قضية عمالية',
                'description' => 'تحليل مطالبات العامل والرد عليها بموجب عقود العمل ومسيرات الرواتب.',
                'type' => 'task',
                'duration' => 120,
            ],
            [
                'title' => 'إعداد لائحة دعوى الملكية الفكرية',
                'description' => 'صياغة الصحيفة وإرفاق شهادات تسجيل العلامة التجارية والانتهاكات الموثقة.',
                'type' => 'task',
                'duration' => 180,
            ],
            [
                'title' => 'تدقيق عقود ومستندات الموردين',
                'description' => 'مراجعة 5 عقود توريد وتحديد الثغرات والشروط عالية المخاطر.',
                'type' => 'task',
                'duration' => 60,
            ],
            [
                'title' => 'متابعة صدور الحكم والتنفيذ',
                'description' => 'تقديم طلب التنفيذ للسندات التنفيذية عبر منصة ناجز ومتابعة إشعار التنفيذ.',
                'type' => 'task',
                'duration' => 45,
            ],
            [
                'title' => 'تأسيس فرع شركة أجنبية',
                'description' => 'تجميع الأوراق والوثائق المترجمة والتوثيق من الجهات المختصة وزيارة وزارة الاستثمار.',
                'type' => 'task',
                'duration' => 240,
            ],
            [
                'title' => 'إعداد دراسة قانونية حول الضرائب والجمارك',
                'description' => 'تحليل الأثر الضريبي للتعديلات الجديدة على أنشطة العميل التجارية.',
                'type' => 'task',
                'duration' => 150,
            ],
            [
                'title' => 'صياغة بند حماية أسرار العمل وعدم المنافسة',
                'description' => 'صياغة بند حماية أسرار العمل وعدم المنافسة للموظفين التنفيذيين.',
                'type' => 'task',
                'duration' => 60,
            ],

            // Reminders
            [
                'title' => 'تذكير: تقديم الإقرار الضريبي للشركة',
                'description' => 'التأكد من رفع الإقرارات الضريبية قبل الموعد النهائي لتجنب الغرامات.',
                'type' => 'reminder',
                'duration' => 15,
            ],
            [
                'title' => 'تذكير: تجديد السجل التجاري',
                'description' => 'تجديد السجل التجاري والتراخيص البلدية للشركة الرئيسية والفروع.',
                'type' => 'reminder',
                'duration' => 15,
            ],
            [
                'title' => 'تذكير: موعد سداد الدفعة الأولى',
                'description' => 'متابعة استلام المستحقات المالية الخاصة بمرحلة صياغة العقود.',
                'type' => 'reminder',
                'duration' => 15,
            ],
            [
                'title' => 'تذكير: انتهاء فترة الاعتراض على الحكم',
                'description' => 'التحقق من رفع اللائحة الاعتراضية قبل انقضاء المهلة النظامية (30 يوماً).',
                'type' => 'reminder',
                'duration' => 30,
            ],
            [
                'title' => 'تذكير: تجديد إقامة الموظفين الأجانب',
                'description' => 'متابعة قسم الموارد البشرية لتجديد الإقامات ورخص العمل.',
                'type' => 'reminder',
                'duration' => 15,
            ],
            [
                'title' => 'تذكير: موعد الجلسة القضائية الثانية',
                'description' => 'تذكير المستشار القانوني بالحضور أونلاين عبر ناجز قبل 15 دقيقة من الموعد.',
                'type' => 'reminder',
                'duration' => 30,
            ],
            [
                'title' => 'تذكير: مراجعة وثيقة التأمين الطبي',
                'description' => 'مراجعة عروض أسعار التأمين الطبي السنوي لموظفي الشركة.',
                'type' => 'reminder',
                'duration' => 30,
            ],
            [
                'title' => 'تذكير: تسليم الأصول الرقمية والمستندات',
                'description' => 'استلام ملف البيانات من العميل لبدء عمليات التدقيق الحسابي والقانوني.',
                'type' => 'reminder',
                'duration' => 20,
            ],
        ];

        // Dates distribution: span across current month and next month
        // e.g. from current month start to next month end
        $now = Carbon::now();
        $startRange = (clone $now)->startOfMonth();
        $endRange = (clone $now)->addMonth()->endOfMonth();
        $totalDays = (int) $startRange->diffInDays($endRange);

        $statuses = ['scheduled', 'completed', 'canceled'];

        foreach ($eventsData as $index => $item) {
            // Distribute start dates evenly across the 2-month window
            $dayOffset = (int) (($index / count($eventsData)) * $totalDays);
            $hour = 8 + ($index % 9); // Business hours: 8:00 to 16:00
            $minute = ($index % 4) * 15; // 0, 15, 30, 45

            $startTime = (clone $startRange)->addDays($dayOffset)->setTime($hour, $minute);
            $endTime = (clone $startTime)->addMinutes($item['duration']);

            // Status determination based on time relative to now
            if ($startTime->isPast()) {
                $status = ($index % 5 === 0) ? 'canceled' : 'completed';
            } else {
                $status = ($index % 7 === 0) ? 'canceled' : 'scheduled';
            }

            // Link ~70% of events to a lead, 30% standalone
            $leadId = ($index % 10 < 7) ? $leads->random()->id : null;
            $userId = $users->random()->id;

            CalendarEvent::factory()
                ->forUser($userId)
                ->forLead($leadId)
                ->state([
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'type' => $item['type'],
                    'status' => $status,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ])
                ->create();
        }
    }
}
