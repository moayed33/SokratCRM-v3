@extends('settings.layout')

@section('title', __('crm.settings'))
@section('heading', __('crm.system_settings'))
@section('subheading', __('crm.manage_access_from_one_place'))

@section('content')
<section class="grid stats-grid">
    <article class="stat-card">
        <span>{{ __('crm.total_users') }}</span>
        <b>{{ number_format($usersCount) }}</b>
    </article>
    <article class="stat-card">
        <span>{{ __('crm.active_users') }}</span>
        <b>{{ number_format($activeUsersCount) }}</b>
    </article>
    <article class="stat-card">
        <span>{{ __('crm.groups') }}</span>
        <b>{{ number_format($groupsCount) }}</b>
    </article>
    <article class="stat-card">
        <span>{{ __('crm.defined_permissions') }}</span>
        <b>{{ number_format($permissionsCount) }}</b>
    </article>
</section>

<section class="panel" style="margin-top:18px">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.access_management') }}</h2>
            <p>{{ __('crm.permissions_inherited_notice') }}</p>
        </div>
    </div>
    <div class="grid stats-grid">
        @can('branches.view')
            <a class="stat-card action-card" href="{{ route('v2.settings.branches.index') }}">
                <span>{{ __('crm.branches') }}</span>
                <b style="font-size:20px">{{ __('crm.manage_branches') }}</b>
            </a>
        @endcan
        <a class="stat-card action-card" href="{{ route('v2.settings.stages.index') }}">
            <span>{{ __('crm.custom_pipeline_stages') }}</span>
            <b style="font-size:20px">{{ __('crm.customize_pipeline_stages') }}</b>
        </a>
        <a class="stat-card action-card" href="{{ route('v2.settings.fields.index') }}">
            <span>{{ __('crm.lead_fields') }}</span>
            <b style="font-size:20px">{{ __('crm.lead_fields_manage') }}</b>
        </a>
        @can('users.view')
            <a class="stat-card action-card" href="{{ route('v2.settings.users.index') }}">
                <span>{{ __('crm.users') }}</span>
                <b style="font-size:20px">{{ __('crm.manage_accounts') }}</b>
            </a>
        @endcan
        @can('groups.view')
            <a class="stat-card action-card" href="{{ route('v2.settings.groups.index') }}">
                <span>{{ __('crm.groups') }}</span>
                <b style="font-size:20px">{{ __('crm.manage_groups') }}</b>
            </a>
            <a class="stat-card action-card" href="{{ route('v2.settings.permissions.index') }}">
                <span>{{ __('crm.permissions_matrix') }}</span>
                <b style="font-size:20px">{{ __('crm.access_distribution') }}</b>
            </a>
        @endcan
        @can('notifications.manage')
            <a class="stat-card action-card" href="{{ route('v2.settings.notifications.index') }}">
                <span>{{ __('crm.notifications') }}</span>
                <b style="font-size:20px">{{ __('crm.manage_notification_rules') }}</b>
            </a>
        @endcan
    </div>
</section>

<section class="panel" style="margin-top:18px">
    <div class="panel-head">
        <div>
            <h2><i class="bi bi-translate"></i> {{ __('crm.system_language') }}</h2>
            <p>{{ __('crm.language_settings_desc') }}</p>
        </div>
    </div>
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px;">
        <a
            class="stat-card action-card"
            href="{{ route('v2.lang.switch', 'ar') }}"
            style="border-radius: 14px; border: 2px solid {{ app()->getLocale() === 'ar' ? 'var(--red)' : 'var(--line)' }}; background: {{ app()->getLocale() === 'ar' ? '#fff5f6' : '#fff' }}; text-decoration: none; padding: 18px;"
        >
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <span style="font-size: 22px;">🇸🇦</span>
                @if (app()->getLocale() === 'ar')
                    <span class="badge active">{{ __('crm.active_now') }}</span>
                @endif
            </div>
            <strong style="display: block; font-size: 17px; color: var(--ink);">{{ __('crm.arabic') }}</strong>
            <small style="display: block; margin-top: 5px; color: var(--muted);">{{ __('crm.arabic_desc') }}</small>
        </a>

        <a
            class="stat-card action-card"
            href="{{ route('v2.lang.switch', 'en') }}"
            style="border-radius: 14px; border: 2px solid {{ app()->getLocale() === 'en' ? 'var(--red)' : 'var(--line)' }}; background: {{ app()->getLocale() === 'en' ? '#fff5f6' : '#fff' }}; text-decoration: none; padding: 18px;"
        >
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <span style="font-size: 22px;">🇬🇧</span>
                @if (app()->getLocale() === 'en')
                    <span class="badge active">{{ __('crm.active_now') }}</span>
                @endif
            </div>
            <strong style="display: block; font-size: 17px; color: var(--ink);">{{ __('crm.english') }}</strong>
            <small style="display: block; margin-top: 5px; color: var(--muted);">{{ __('crm.english_desc') }}</small>
        </a>
    </div>
</section>
@endsection
