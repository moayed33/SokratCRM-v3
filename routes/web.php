<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignReportController;
use App\Http\Controllers\DailyTaskController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadFollowupController;
use App\Http\Controllers\LeadTransferController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\Settings\BranchController;
use App\Http\Controllers\Settings\GroupController;
use App\Http\Controllers\Settings\LeadFieldController;
use App\Http\Controllers\Settings\NotificationRuleController;
use App\Http\Controllers\Settings\OptionSetController;
use App\Http\Controllers\Settings\PermissionController;
use App\Http\Controllers\Settings\PipelineStageController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\TaskStatusController;
use App\Http\Controllers\TwilioNotificationStatusController;
use App\Http\Controllers\VoipController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', static function () {
    return redirect()->route(
        Auth::check() ? 'dashboard' : 'login',
    );
});

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1')
    ->name('login.post');
Route::get('/lang/{locale}', static function (string $locale) {
    if (in_array($locale, ['ar', 'en'], true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('v2.lang.switch');

Route::post('/webhooks/twilio/notification-status', TwilioNotificationStatusController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.twilio.notification-status');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::match(['get', 'post'], '/switch-branch', [BranchController::class, 'switchBranch'])
        ->name('v2.branch.switch');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('v2.notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('v2.notifications.unread-count');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('v2.notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->name('v2.notifications.read');
    Route::post('/notifications/{notification}/snooze', [NotificationController::class, 'snooze'])
        ->name('v2.notifications.snooze');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])
        ->name('v2.notifications.destroy');

    Route::get('/my/notifications', [NotificationPreferenceController::class, 'edit'])
        ->name('v2.notifications.preferences.edit');
    Route::patch('/my/notifications', [NotificationPreferenceController::class, 'update'])
        ->name('v2.notifications.preferences.update');
    Route::post('/my/notifications/test', [NotificationPreferenceController::class, 'test'])
        ->middleware('throttle:5,60')
        ->name('v2.notifications.preferences.test');
    Route::post('/my/notifications/push-subscriptions', [PushSubscriptionController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('v2.notifications.push.store');
    Route::delete('/my/notifications/push-subscriptions', [PushSubscriptionController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('v2.notifications.push.destroy');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('can:dashboard.view')
        ->name('dashboard');

    Route::get('/voip/live', [VoipController::class, 'livePanel'])
        ->middleware('can:voip.live_panel')
        ->name('v2.voip.live');

    Route::get('/voip/recordings/{mediaId}', [VoipController::class, 'streamRecording'])
        ->middleware('can:voip.recordings')
        ->name('v2.voip.recordings.stream');

    Route::get('/leads/{lead}/calls', [VoipController::class, 'leadCalls'])
        ->middleware('can:voip.view')
        ->whereNumber('lead')
        ->name('v2.leads.calls');

    Route::get('/voip/extensions/{extension}/stats', [VoipController::class, 'extensionStats'])
        ->middleware('can:voip.view')
        ->name('v2.voip.stats');

    Route::middleware('can:calendar.view')->group(function (): void {
        Route::get('/calendar', [CalendarController::class, 'index'])
            ->name('v2.calendar.index');
        Route::get('/calendar/events', [CalendarController::class, 'events'])
            ->name('v2.calendar.events');
        Route::get('/calendar/reminders', [CalendarController::class, 'reminders'])
            ->name('v2.calendar.reminders');
        Route::get('/calendar/events/{event}', [CalendarController::class, 'show'])
            ->whereNumber('event')
            ->name('v2.calendar.show');
    });

    Route::middleware('can:calendar.manage')->group(function (): void {
        Route::post('/calendar/events', [CalendarController::class, 'store'])
            ->name('v2.calendar.store');
        Route::patch('/calendar/events/{event}', [CalendarController::class, 'update'])
            ->whereNumber('event')
            ->name('v2.calendar.update');
        Route::patch('/calendar/events/{event}/reschedule', [CalendarController::class, 'reschedule'])
            ->whereNumber('event')
            ->name('v2.calendar.reschedule');
        Route::patch('/calendar/leads/{lead}/reschedule', [CalendarController::class, 'rescheduleLead'])
            ->whereNumber('lead')
            ->name('v2.calendar.lead.reschedule');
        Route::post('/calendar/events/{event}/sync', [CalendarController::class, 'syncExternal'])
            ->whereNumber('event')
            ->name('v2.calendar.sync');
        Route::delete('/calendar/events/{event}', [CalendarController::class, 'destroy'])
            ->whereNumber('event')
            ->name('v2.calendar.destroy');
    });
    Route::middleware('can:leads.view')->group(function (): void {
        Route::get('/leads', [LeadController::class, 'index'])
            ->name('v2.leads');
        Route::get('/leads/kanban', [DashboardController::class, 'kanban'])
            ->name('v2.leads.kanban');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])
            ->whereNumber('lead')
            ->name('v2.leads.show');
    });

    Route::middleware('can:leads.create')->group(function (): void {
        Route::get('/leads/create', [LeadController::class, 'create'])
            ->name('v2.leads.create');
        Route::post('/leads', [LeadController::class, 'store'])
            ->name('v2.leads.store');
    });

    Route::middleware('can:leads.update')->group(function (): void {
        Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])
            ->whereNumber('lead')
            ->name('v2.leads.edit');
        Route::patch('/leads/{lead}', [LeadController::class, 'update'])
            ->whereNumber('lead')
            ->name('v2.leads.update');
    });

    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])
        ->whereNumber('lead')
        ->middleware('can:leads.delete')
        ->name('v2.leads.destroy');

    Route::middleware('can:leads.followups.view')->group(function (): void {
        Route::get(
            '/leads/{lead}/followups',
            [LeadFollowupController::class, 'index'],
        )
            ->whereNumber('lead')
            ->name('v2.leads.followups.index');
    });

    Route::post(
        '/leads/{lead}/followups',
        [LeadFollowupController::class, 'store'],
    )
        ->whereNumber('lead')
        ->middleware('can:leads.followups.create')
        ->name('v2.leads.followups.store');

    Route::middleware('can:leads.import')->group(function (): void {
        Route::get('/leads/import', [LeadTransferController::class, 'importIndex'])
            ->name('v2.leads.import');
        Route::get(
            '/leads/import/template',
            [LeadTransferController::class, 'importTemplate'],
        )->name('v2.leads.import.template');
        Route::post(
            '/leads/import/preview',
            [LeadTransferController::class, 'importPreview'],
        )->name('v2.leads.import.preview');
        Route::post(
            '/leads/import/confirm',
            [LeadTransferController::class, 'importConfirm'],
        )->name('v2.leads.import.confirm');
    });

    Route::middleware('can:leads.export')->group(function (): void {
        Route::post(
            '/leads/export-selected',
            [LeadController::class, 'exportSelected'],
        )->name('v2.leads.export-selected');
        Route::get('/leads/export', [LeadTransferController::class, 'exportIndex'])
            ->name('v2.leads.export');
        Route::post(
            '/leads/export/download',
            [LeadTransferController::class, 'exportDownload'],
        )->name('v2.leads.export.download');
    });

    Route::get(
        '/leads/{lead}/quotation-preview',
        [LeadController::class, 'quotationPreview'],
    )
        ->whereNumber('lead')
        ->middleware('can:quotations.view')
        ->name('v2.leads.quotation.preview');

    Route::middleware('can:tasks.view')->group(function (): void {
        Route::get(
            '/followups',
            static fn () => view('placeholder', ['title' => 'كل المتابعات']),
        )->name('v2.followups');
        Route::get(
            '/tasks/upcoming',
            static fn () => redirect()->route('v2.tasks.daily', ['scope' => 'upcoming']),
        )->name('v2.tasks.upcoming');
        Route::get(
            '/tasks/meetings',
            static fn () => view('placeholder', ['title' => 'المقابلات']),
        )->name('v2.tasks.meetings');
        Route::get(
            '/tasks/vip',
            static fn () => view('placeholder', ['title' => 'عملاء VIP']),
        )->name('v2.tasks.vip');
        Route::get(
            '/tasks/daily',
            [DailyTaskController::class, 'index'],
        )->name('v2.tasks.daily');
        Route::post(
            '/tasks/leads/{lead}/reschedule',
            [DailyTaskController::class, 'reschedule'],
        )->whereNumber('lead')->name('v2.tasks.reschedule');
        Route::post(
            '/tasks/leads/{lead}/quick-followup',
            [DailyTaskController::class, 'quickFollowup'],
        )->whereNumber('lead')->name('v2.tasks.quick_followup');

        Route::get(
            '/tasks/status/{status}',
            [TaskStatusController::class, 'show'],
        )->where(
            'status',
            'new|no-answer|no_answer|not-interested|not_interested|donor',
        )->name('v2.tasks.status');

        Route::get('/tasks/followups/{scope}', static function (string $scope) {
            return redirect()->route('v2.tasks.daily', ['scope' => $scope]);
        })->where('scope', 'today|upcoming|overdue')
            ->name('v2.followups.scope');

        Route::get('/tasks/meetings/{scope}', static function (string $scope) {
            $scopes = [
                'today' => 'مقابلات اليوم',
                'upcoming' => 'المقابلات القادمة',
                'overdue' => 'المقابلات المتأخرة',
            ];

            return view('placeholder', ['title' => $scopes[$scope]]);
        })->where('scope', 'today|upcoming|overdue')
            ->name('v2.meetings.scope');
    });

    Route::middleware('can:reports.view')->group(function (): void {
        Route::get(
            '/reports/leads',
            static fn () => view('placeholder', ['title' => 'تقرير العملاء']),
        )->name('v2.reports.leads');
        Route::get(
            '/reports/tasks',
            static fn () => view('placeholder', ['title' => 'تقرير المهام']),
        )->name('v2.reports.tasks');
        Route::get(
            '/reports/employees',
            static fn () => view('placeholder', ['title' => 'أداء الموظفين']),
        )->name('v2.reports.employees');
    });

    Route::get('/campaigns', [CampaignController::class, 'index'])
        ->middleware('can:campaigns.view')
        ->name('v2.campaigns.index');
    Route::get('/campaigns/create', [CampaignController::class, 'create'])
        ->middleware('can:campaigns.create')
        ->name('v2.campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])
        ->middleware('can:campaigns.create')
        ->name('v2.campaigns.store');
    Route::get('/campaigns/{campaign}/edit', [CampaignController::class, 'edit'])
        ->whereNumber('campaign')
        ->middleware('can:campaigns.create')
        ->name('v2.campaigns.edit');
    Route::patch('/campaigns/{campaign}', [CampaignController::class, 'update'])
        ->whereNumber('campaign')
        ->middleware('can:campaigns.create')
        ->name('v2.campaigns.update');
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])
        ->whereNumber('campaign')
        ->middleware('can:campaigns.create')
        ->name('v2.campaigns.destroy');
    Route::get('/campaigns/reports', CampaignReportController::class)
        ->middleware('can:campaigns.reports')
        ->name('v2.campaigns.reports');

    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])
        ->whereNumber('campaign')
        ->middleware('can:campaigns.view')
        ->name('v2.campaigns.show');
    Route::patch(
        '/campaigns/{campaign}/leads/assign',
        [CampaignController::class, 'assignLeads'],
    )->whereNumber('campaign')
        ->middleware('can:campaigns.create')
        ->name('v2.campaigns.leads.assign');

    Route::middleware('can:quotations.view')->group(function (): void {
        Route::get('/quotations', [QuotationController::class, 'index'])
            ->name('v2.quotations.index');
        Route::get(
            '/quotations/{quotation}',
            [QuotationController::class, 'show'],
        )->whereNumber('quotation')
            ->name('v2.quotations.show');
    });
    Route::middleware('can:quotations.create')->group(function (): void {
        Route::get('/quotations/create', [QuotationController::class, 'create'])
            ->name('v2.quotations.create');
        Route::post('/quotations', [QuotationController::class, 'store'])
            ->name('v2.quotations.store');
    });

    Route::prefix('settings')
        ->name('v2.settings')
        ->middleware('can:settings.access')
        ->group(function (): void {
            Route::get('/', [SettingsController::class, 'index'])
                ->name('');

            Route::get('/lead-fields', [LeadFieldController::class, 'index'])
                ->name('.fields.index');
            Route::get('/lead-fields/create', [LeadFieldController::class, 'create'])
                ->name('.fields.create');
            Route::post('/lead-fields', [LeadFieldController::class, 'store'])
                ->name('.fields.store');
            Route::get('/lead-fields/{field}/edit', [LeadFieldController::class, 'edit'])
                ->whereNumber('field')
                ->name('.fields.edit');
            Route::patch('/lead-fields/{field}', [LeadFieldController::class, 'update'])
                ->whereNumber('field')
                ->name('.fields.update');
            Route::patch('/lead-fields/{field}/toggle', [LeadFieldController::class, 'toggleActive'])
                ->whereNumber('field')
                ->name('.fields.toggle');
            Route::patch('/lead-fields/{field}/move', [LeadFieldController::class, 'move'])
                ->whereNumber('field')
                ->name('.fields.move');
            Route::delete('/lead-fields/{field}', [LeadFieldController::class, 'destroy'])
                ->whereNumber('field')
                ->name('.fields.destroy');

            Route::get('/option-sets', [OptionSetController::class, 'index'])
                ->name('.option-sets.index');
            Route::get('/option-sets/{set}/edit', [OptionSetController::class, 'edit'])
                ->where('set', '[a-z][a-z0-9_]*')
                ->name('.option-sets.edit');
            Route::put('/option-sets/{set}', [OptionSetController::class, 'update'])
                ->where('set', '[a-z][a-z0-9_]*')
                ->name('.option-sets.update');
            Route::post('/option-sets', [OptionSetController::class, 'store'])
                ->name('.option-sets.store');
            Route::delete('/option-sets/{set}', [OptionSetController::class, 'destroy'])
                ->where('set', '[a-z][a-z0-9_]*')
                ->name('.option-sets.destroy');

            Route::get('/branches', [BranchController::class, 'index'])
                ->middleware('can:branches.view')
                ->name('.branches.index');
            Route::get('/branches/create', [BranchController::class, 'create'])
                ->middleware('can:branches.create')
                ->name('.branches.create');
            Route::post('/branches', [BranchController::class, 'store'])
                ->middleware('can:branches.create')
                ->name('.branches.store');
            Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])
                ->whereNumber('branch')
                ->middleware('can:branches.update')
                ->name('.branches.edit');
            Route::patch('/branches/{branch}', [BranchController::class, 'update'])
                ->whereNumber('branch')
                ->middleware('can:branches.update')
                ->name('.branches.update');
            Route::patch('/branches/{branch}/toggle', [BranchController::class, 'toggleActive'])
                ->whereNumber('branch')
                ->middleware('can:branches.update')
                ->name('.branches.toggle');
            Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])
                ->whereNumber('branch')
                ->middleware('can:branches.delete')
                ->name('.branches.destroy');
            Route::get('/stages', [PipelineStageController::class, 'index'])
                ->name('.stages.index');
            Route::post('/stages', [PipelineStageController::class, 'store'])
                ->name('.stages.store');
            Route::patch('/stages/{stage}', [PipelineStageController::class, 'update'])
                ->whereNumber('stage')
                ->name('.stages.update');
            Route::delete('/stages/{stage}', [PipelineStageController::class, 'destroy'])
                ->whereNumber('stage')
                ->name('.stages.destroy');
            Route::post('/stages/donation-types', [PipelineStageController::class, 'storeDonationType'])
                ->name('.stages.donation-types.store');
            Route::patch('/stages/donation-types/{type}', [PipelineStageController::class, 'toggleDonationType'])
                ->whereNumber('type')
                ->name('.stages.donation-types.toggle');
            Route::post('/stages/donation-purposes', [PipelineStageController::class, 'storeDonationPurpose'])
                ->name('.stages.donation-purposes.store');
            Route::patch('/stages/donation-purposes/{purpose}', [PipelineStageController::class, 'toggleDonationPurpose'])
                ->whereNumber('purpose')
                ->name('.stages.donation-purposes.toggle');

            Route::get('/users', [UserController::class, 'index'])
                ->middleware('can:users.view')
                ->name('.users.index');
            Route::get('/users/create', [UserController::class, 'create'])
                ->middleware('can:users.create')
                ->name('.users.create');
            Route::post('/users', [UserController::class, 'store'])
                ->middleware('can:users.create')
                ->name('.users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])
                ->middleware('can:users.update')
                ->name('.users.edit');
            Route::patch('/users/{user}', [UserController::class, 'update'])
                ->middleware('can:users.update')
                ->name('.users.update');
            Route::patch(
                '/users/{user}/status',
                [UserController::class, 'updateStatus'],
            )->middleware('can:users.activate')
                ->name('.users.status');
            Route::patch(
                '/users/{user}/password',
                [UserController::class, 'resetPassword'],
            )->middleware('can:users.reset_password')
                ->name('.users.password');

            Route::get('/groups', [GroupController::class, 'index'])
                ->middleware('can:groups.view')
                ->name('.groups.index');
            Route::get('/groups/create', [GroupController::class, 'create'])
                ->middleware('can:groups.create')
                ->name('.groups.create');
            Route::post('/groups', [GroupController::class, 'store'])
                ->middleware('can:groups.create')
                ->name('.groups.store');
            Route::get('/groups/{group}/edit', [GroupController::class, 'edit'])
                ->middleware('can:groups.update')
                ->name('.groups.edit');
            Route::patch('/groups/{group}', [GroupController::class, 'update'])
                ->middleware('can:groups.update')
                ->name('.groups.update');
            Route::delete('/groups/{group}', [GroupController::class, 'destroy'])
                ->middleware('can:groups.delete')
                ->name('.groups.destroy');

            Route::get('/permissions', [PermissionController::class, 'index'])
                ->middleware('can:groups.view')
                ->name('.permissions.index');
            Route::put('/permissions', [PermissionController::class, 'update'])
                ->middleware('can:groups.assign_permissions')
                ->name('.permissions.update');

            Route::middleware('can:notifications.manage')->group(function (): void {
                Route::get('/notifications', [NotificationRuleController::class, 'index'])
                    ->name('.notifications.index');
                Route::get('/notifications/create', [NotificationRuleController::class, 'create'])
                    ->name('.notifications.create');
                Route::post('/notifications', [NotificationRuleController::class, 'store'])
                    ->name('.notifications.store');
                Route::get('/notifications/{notificationRule}/edit', [NotificationRuleController::class, 'edit'])
                    ->name('.notifications.edit');
                Route::put('/notifications/{notificationRule}', [NotificationRuleController::class, 'update'])
                    ->name('.notifications.update');
                Route::post('/notifications/{notificationRule}/duplicate', [NotificationRuleController::class, 'duplicate'])
                    ->name('.notifications.duplicate');
                Route::patch('/notifications/{notificationRule}/toggle', [NotificationRuleController::class, 'toggle'])
                    ->name('.notifications.toggle');
                Route::delete('/notifications/{notificationRule}', [NotificationRuleController::class, 'destroy'])
                    ->name('.notifications.destroy');
            });
            Route::get('/voip', [VoipController::class, 'settings'])
                ->middleware('can:voip.settings')
                ->name('.voip');
            Route::post('/voip/pair', [VoipController::class, 'pair'])
                ->middleware('can:voip.settings')
                ->name('.voip.pair');
            Route::post('/voip/disconnect', [VoipController::class, 'disconnect'])
                ->middleware('can:voip.settings')
                ->name('.voip.disconnect');
        });
});
