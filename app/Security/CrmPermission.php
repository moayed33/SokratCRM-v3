<?php

declare(strict_types=1);

namespace App\Security;

enum CrmPermission: string
{
    case DASHBOARD_VIEW = 'dashboard.view';
    case SETTINGS_ACCESS = 'settings.access';
    case NOTIFICATIONS_MANAGE = 'notifications.manage';

    case LEADS_VIEW = 'leads.view';
    case LEADS_SCOPE_ALL = 'leads.scope.all';
    case LEADS_SCOPE_GROUP = 'leads.scope.group';
    case LEADS_ASSIGN = 'leads.assign';
    case LEADS_CREATE = 'leads.create';
    case LEADS_UPDATE = 'leads.update';
    case LEADS_DELETE = 'leads.delete';
    case LEADS_IMPORT = 'leads.import';
    case LEADS_EXPORT = 'leads.export';
    case LEADS_FOLLOWUPS_VIEW = 'leads.followups.view';
    case LEADS_FOLLOWUPS_CREATE = 'leads.followups.create';

    case TASKS_VIEW = 'tasks.view';

    case QUOTATIONS_VIEW = 'quotations.view';
    case QUOTATIONS_CREATE = 'quotations.create';

    case CAMPAIGNS_VIEW = 'campaigns.view';
    case CAMPAIGNS_CREATE = 'campaigns.create';
    case CAMPAIGNS_REPORTS = 'campaigns.reports';
    case REPORTS_VIEW = 'reports.view';

    case USERS_VIEW = 'users.view';
    case USERS_CREATE = 'users.create';
    case USERS_UPDATE = 'users.update';
    case USERS_ACTIVATE = 'users.activate';
    case USERS_RESET_PASSWORD = 'users.reset_password';

    case GROUPS_VIEW = 'groups.view';
    case GROUPS_CREATE = 'groups.create';
    case GROUPS_UPDATE = 'groups.update';
    case GROUPS_DELETE = 'groups.delete';
    case GROUPS_ASSIGN_PERMISSIONS = 'groups.assign_permissions';

    case VOIP_VIEW = 'voip.view';
    case VOIP_RECORDINGS = 'voip.recordings';
    case VOIP_LIVE_PANEL = 'voip.live_panel';
    case VOIP_SETTINGS = 'voip.settings';

    case CALENDAR_VIEW = 'calendar.view';
    case CALENDAR_MANAGE = 'calendar.manage';

    case BRANCHES_VIEW = 'branches.view';
    case BRANCHES_CREATE = 'branches.create';
    case BRANCHES_UPDATE = 'branches.update';
    case BRANCHES_DELETE = 'branches.delete';

    public function module(): string
    {
        return explode('.', $this->value, 2)[0];
    }

    public function label(): string
    {
        return match ($this) {
            self::DASHBOARD_VIEW => 'عرض لوحة التحكم',
            self::SETTINGS_ACCESS => 'الدخول إلى الإعدادات',
            self::NOTIFICATIONS_MANAGE => 'إدارة قواعد الإشعارات',
            self::LEADS_VIEW => 'عرض العملاء المحتملين',
            self::LEADS_SCOPE_ALL => 'نطاق العملاء: جميع العملاء',
            self::LEADS_SCOPE_GROUP => 'نطاق العملاء: مجموعات المستخدم',
            self::LEADS_ASSIGN => 'إسناد العملاء لمستخدم آخر',
            self::LEADS_CREATE => 'إضافة العملاء',
            self::LEADS_UPDATE => 'تعديل العملاء',
            self::LEADS_DELETE => 'حذف العملاء',
            self::LEADS_IMPORT => 'استيراد العملاء',
            self::LEADS_EXPORT => 'تصدير العملاء',
            self::LEADS_FOLLOWUPS_VIEW => 'عرض متابعات العملاء',
            self::LEADS_FOLLOWUPS_CREATE => 'تسجيل متابعات العملاء',
            self::TASKS_VIEW => 'عرض المهام والمتابعات',
            self::QUOTATIONS_VIEW => 'عرض عروض الأسعار',
            self::QUOTATIONS_CREATE => 'إنشاء عروض الأسعار',
            self::CAMPAIGNS_VIEW => 'عرض الحملات',
            self::CAMPAIGNS_CREATE => 'إنشاء الحملات',
            self::CAMPAIGNS_REPORTS => 'عرض تقارير الحملات',
            self::REPORTS_VIEW => 'عرض التقارير',
            self::USERS_VIEW => 'عرض المستخدمين',
            self::USERS_CREATE => 'إضافة المستخدمين',
            self::USERS_UPDATE => 'تعديل المستخدمين ومجموعاتهم',
            self::USERS_ACTIVATE => 'تفعيل وتعطيل المستخدمين',
            self::USERS_RESET_PASSWORD => 'تغيير كلمات مرور المستخدمين',
            self::GROUPS_VIEW => 'عرض المجموعات',
            self::GROUPS_CREATE => 'إضافة المجموعات',
            self::GROUPS_UPDATE => 'تعديل المجموعات',
            self::GROUPS_DELETE => 'حذف المجموعات',
            self::GROUPS_ASSIGN_PERMISSIONS => 'إسناد الصلاحيات للمجموعات',
            self::VOIP_VIEW => 'عرض سجل مكالمات السنترال والاحصائيات',
            self::VOIP_RECORDINGS => 'الاستماع للمكالمات المسجلة',
            self::VOIP_LIVE_PANEL => 'عرض لوحة المراقبة المباشرة للسنترال',
            self::VOIP_SETTINGS => 'إدارة ربط السنترال (VoIP)',
            self::CALENDAR_VIEW => 'عرض التقويم والأحداث',
            self::CALENDAR_MANAGE => 'إدارة التقويم والأحداث',
            self::BRANCHES_VIEW => 'عرض الفروع',
            self::BRANCHES_CREATE => 'إضافة الفروع',
            self::BRANCHES_UPDATE => 'تعديل الفروع',
            self::BRANCHES_DELETE => 'حذف الفروع',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $permission): string => $permission->value,
            self::cases(),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function moduleLabels(): array
    {
        return [
            'dashboard' => 'لوحة التحكم',
            'settings' => 'الإعدادات',
            'notifications' => 'الإشعارات',
            'leads' => 'العملاء والمتابعات',
            'tasks' => 'المهام',
            'quotations' => 'عروض الأسعار',
            'campaigns' => 'الحملات',
            'reports' => 'التقارير',
            'users' => 'المستخدمون',
            'groups' => 'المجموعات والصلاحيات',
            'voip' => 'اتصالات السنترال (VoIP)',
            'calendar' => 'التقويم والأحداث',
            'branches' => 'الفروع',
        ];
    }
}
