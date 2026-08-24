@extends('settings.layout')

@section('title', __('crm.stages_settings_title'))
@section('heading', __('crm.stages_settings_heading'))
@section('subheading', __('crm.stages_settings_subheading'))

@section('content')
<section class="grid stats-grid" style="margin-bottom: 20px;">
    <article class="stat-card">
        <span>{{ __('crm.total_current_stages') }}</span>
        <b>{{ $totalStagesCount }}</b>
    </article>
    <article class="stat-card">
        <span>{{ __('crm.primary_stages_count') }}</span>
        <b>{{ $primaryStagesCount ?? $stages->where('is_primary', true)->count() }}</b>
    </article>
    <article class="stat-card">
        <span>{{ __('crm.custom_stages_count') }}</span>
        <b>{{ $customStagesCount ?? $stages->where('is_primary', false)->count() }}</b>
    </article>
    <article class="stat-card">
        <span>{{ __('crm.approved_donation_types') }}</span>
        <b>{{ $donationTypes->where('is_active', true)->count() }}</b>
    </article>
</section>

<!-- STAGES PANEL -->
<section class="panel">
    <div class="panel-head">
        <div>
            <h2><i class="bi bi-diagram-3"></i> {{ __('crm.customer_pipeline_stages') }}</h2>
            <p>{{ __('crm.pipeline_stages_rule_desc') }}</p>
        </div>
        <div>
            <button type="button" class="btn primary" onclick="openAddStageModal()">
                <i class="bi bi-plus-lg"></i> {{ __('crm.add_additional_stage') }}
            </button>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:60px">{{ __('crm.order_col') }}</th>
                    <th>{{ __('crm.stage_name_col') }}</th>
                    <th>{{ __('crm.type_col') }}</th>
                    <th>{{ __('crm.color_col') }}</th>
                    <th>{{ __('crm.leads_count_col') }}</th>
                    <th>{{ __('crm.status_th') }}</th>
                    <th style="width:160px">{{ __('crm.actions_th') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stages as $stage)
                    <tr>
                        <td>
                            <span class="badge" style="font-size:14px; font-weight:bold">{{ $stage->position }}</span>
                        </td>
                        <td>
                            <strong style="font-size:15px">{{ (app()->getLocale() === 'en' && !empty($stage->name_en)) ? $stage->name_en : $stage->name_ar }}</strong>
                            @if ($stage->description_ar)
                                <small style="display:block; color:var(--muted); margin-top:3px">{{ (app()->getLocale() === 'en' && !empty($stage->description_en)) ? $stage->description_en : $stage->description_ar }}</small>
                            @endif
                        </td>
                        <td>
                            @if ($stage->isPrimary())
                                <span class="badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd">
                                    <i class="bi bi-shield-check"></i> {{ __('crm.primary_stage_badge') }}
                                </span>
                            @else
                                <span class="badge" style="background:#f3e8ff; color:#7e22ce; border:1px solid #e9d5ff">
                                    <i class="bi bi-plus-circle"></i> {{ __('crm.additional_stage_badge') }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px">
                                <span style="display:inline-block; width:22px; height:22px; border-radius:6px; background:{{ $stage->color ?? '#64748b' }}; border:1px solid rgba(0,0,0,0.1)"></span>
                                <code style="font-size:13px">{{ $stage->color ?? '—' }}</code>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $stage->leads_count > 0 ? 'active' : '' }}" style="font-size:13px">
                                {{ number_format($stage->leads_count) }} {{ __('crm.lead_unit') }}
                            </span>
                        </td>
                        <td>
                            @if ($stage->is_active)
                                <span class="badge active">{{ __('crm.stage_active_badge') }}</span>
                            @else
                                <span class="badge inactive">{{ __('crm.stage_inactive_badge') }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <button type="button" class="btn small soft" onclick="openEditModal({{ json_encode($stage) }})">
                                    <i class="bi bi-pencil-square"></i> {{ __('crm.edit') }}
                                </button>

                                @if (! $stage->isPrimary())
                                    @if ($stage->leads_count === 0)
                                        <form method="POST" action="{{ route('v2.settings.stages.destroy', $stage) }}" onsubmit="return confirm(@json(__('crm.confirm_delete_stage')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn small danger">
                                                <i class="bi bi-trash"></i> {{ __('crm.delete') }}
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn small danger" style="opacity:0.6; cursor:not-allowed" title="{{ __('crm.cannot_delete_stage_has_leads') }}" onclick="alert(@json(__('crm.cannot_delete_stage_has_leads')))">
                                            <i class="bi bi-trash"></i> {{ __('crm.delete') }}
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<!-- DONATION TYPES & PURPOSES SECTION -->
<div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; margin-top: 20px;">
    <!-- DONATION TYPES -->
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2><i class="bi bi-tags"></i> {{ __('crm.donation_types_section') }}</h2>
                <p>{{ __('crm.donation_types_desc') }}</p>
            </div>
            <button type="button" class="btn small primary" onclick="document.getElementById('addTypeModal').style.display='flex'">
                <i class="bi bi-plus"></i> {{ __('crm.add_donation_type') }}
            </button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('crm.name') }}</th>
                        <th>{{ __('crm.status_th') }}</th>
                        <th>{{ __('crm.leads_count_col') }}</th>
                        <th>{{ __('crm.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($donationTypes as $type)
                        <tr>
                            <td><strong>{{ $type->name_ar }}</strong></td>
                            <td>
                                <span class="badge {{ $type->is_active ? 'active' : 'inactive' }}">
                                    {{ $type->is_active ? __('crm.active_status_label') : __('crm.inactive_status_label') }}
                                </span>
                            </td>
                            <td>{{ $type->leads_count }}</td>
                            <td>
                                <form method="POST" action="{{ route('v2.settings.stages.donation-types.toggle', $type) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn small soft">
                                        {{ $type->is_active ? __('crm.deactivate_action') : __('crm.activate_action') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <!-- DONATION PURPOSES -->
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2><i class="bi bi-heart-pulse"></i> {{ __('crm.donation_purposes_section') }}</h2>
                <p>{{ __('crm.donation_purposes_desc') }}</p>
            </div>
            <button type="button" class="btn small primary" onclick="document.getElementById('addPurposeModal').style.display='flex'">
                <i class="bi bi-plus"></i> {{ __('crm.add_donation_purpose') }}
            </button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('crm.name') }}</th>
                        <th>{{ __('crm.status_th') }}</th>
                        <th>{{ __('crm.leads_count_col') }}</th>
                        <th>{{ __('crm.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($donationPurposes as $purpose)
                        <tr>
                            <td><strong>{{ $purpose->name_ar }}</strong></td>
                            <td>
                                <span class="badge {{ $purpose->is_active ? 'active' : 'inactive' }}">
                                    {{ $purpose->is_active ? __('crm.active_status_label') : __('crm.inactive_status_label') }}
                                </span>
                            </td>
                            <td>{{ $purpose->leads_count }}</td>
                            <td>
                                <form method="POST" action="{{ route('v2.settings.stages.donation-purposes.toggle', $purpose) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn small soft">
                                        {{ $purpose->is_active ? __('crm.deactivate_action') : __('crm.activate_action') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@php
$crmStageIcons = [
    ['icon' => 'bi-person', 'name' => 'عميل (شخص)'],
    ['icon' => 'bi-person-plus', 'name' => 'عميل جديد'],
    ['icon' => 'bi-person-check', 'name' => 'عميل مؤكد'],
    ['icon' => 'bi-person-x', 'name' => 'عميل ملغى'],
    ['icon' => 'bi-people', 'name' => 'مجموعة عملاء'],
    ['icon' => 'bi-telephone', 'name' => 'هاتف'],
    ['icon' => 'bi-telephone-outbound', 'name' => 'اتصال صادر'],
    ['icon' => 'bi-telephone-inbound', 'name' => 'اتصال وارد'],
    ['icon' => 'bi-telephone-x', 'name' => 'لم يتم الرد'],
    ['icon' => 'bi-chat-dots', 'name' => 'محادثة'],
    ['icon' => 'bi-whatsapp', 'name' => 'واتساب'],
    ['icon' => 'bi-envelope', 'name' => 'بريد إلكتروني'],
    ['icon' => 'bi-heart', 'name' => 'تبرع'],
    ['icon' => 'bi-heart-fill', 'name' => 'متبرع دائم'],
    ['icon' => 'bi-cash-coin', 'name' => 'تبرع نقدي'],
    ['icon' => 'bi-wallet2', 'name' => 'محفظة'],
    ['icon' => 'bi-gift', 'name' => 'هدية وتبرع'],
    ['icon' => 'bi-check-circle', 'name' => 'مكتمل ومؤكد'],
    ['icon' => 'bi-x-circle', 'name' => 'غير مهتم'],
    ['icon' => 'bi-clock-history', 'name' => 'متابعة لاحقة'],
    ['icon' => 'bi-calendar-check', 'name' => 'موعد محدد'],
    ['icon' => 'bi-calendar-event', 'name' => 'حدث ومقابلة'],
    ['icon' => 'bi-arrow-repeat', 'name' => 'متابعة دورية'],
    ['icon' => 'bi-hourglass-split', 'name' => 'قيد الانتظار'],
    ['icon' => 'bi-star', 'name' => 'مميز'],
    ['icon' => 'bi-star-fill', 'name' => 'نجم'],
    ['icon' => 'bi-flag', 'name' => 'راية ومرحلة'],
    ['icon' => 'bi-bookmark', 'name' => 'علامة'],
    ['icon' => 'bi-pin-angle', 'name' => 'تثبيت'],
    ['icon' => 'bi-bell', 'name' => 'تنبيه'],
    ['icon' => 'bi-hand-thumbs-up', 'name' => 'موافقة'],
    ['icon' => 'bi-briefcase', 'name' => 'أعمال وشركات'],
    ['icon' => 'bi-building', 'name' => 'مؤسسة'],
    ['icon' => 'bi-patch-check', 'name' => 'معتمد'],
    ['icon' => 'bi-tag', 'name' => 'تصنيف'],
    ['icon' => 'bi-sliders', 'name' => 'تخصيص'],
];
@endphp

<!-- MODAL: ADD STAGE -->
<div id="addStageModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; padding:18px;">
    <div style="background:#fff; border-radius:16px; max-width:520px; width:100%; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="margin:0; font-size:18px;">{{ __('crm.add_stage_modal_title') }}</h3>
            <button type="button" onclick="document.getElementById('addStageModal').style.display='none'" style="border:0; background:transparent; font-size:22px; cursor:pointer">&times;</button>
        </div>
        <form method="POST" action="{{ route('v2.settings.stages.store') }}">
            @csrf
            <div style="margin-bottom:14px;">
                <label>{{ __('crm.stage_name_label') }} <span style="color:var(--red)">*</span></label>
                <input type="text" name="name_ar" required placeholder="{{ __('crm.stage_name_label') }}" autofocus>
            </div>
            <div class="form-grid" style="margin-bottom:14px;">
                <div>
                    <label>{{ __('crm.stage_color_hex') }}</label>
                    <div style="display:flex; gap:8px;">
                        <input type="color" id="stageColorPicker" value="#7b61df" style="width:48px; height:42px; padding:2px;" onchange="document.getElementById('stageColorInput').value=this.value">
                        <input type="text" id="stageColorInput" name="color" value="#7b61df" placeholder="#7b61df" pattern="^#[0-9a-fA-F]{6}$" onchange="document.getElementById('stageColorPicker').value=this.value">
                    </div>
                </div>
                <div>
                    <label>{{ __('crm.icon_optional') }}</label>
                    <div class="icon-picker-wrap" id="addStageIconWrap" style="position:relative;">
                        <input type="hidden" id="addStageIconInput" name="icon" value="">
                        <button type="button" class="icon-picker-btn" id="addStageIconBtn" onclick="toggleIconDropdown('addStage')" style="width:100%; height:42px; display:flex; align-items:center; justify-content:space-between; gap:8px; border:1px solid #dbe1e9; border-radius:10px; padding:0 12px; background:#fff; color:var(--ink); cursor:pointer; text-align:start;">
                            <span style="display:flex; align-items:center; gap:8px; min-width:0; overflow:hidden;">
                                <span class="icon-preview-box" id="addStageIconPreview" style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; background:#f1f5f9; color:#475569; font-size:15px; flex-shrink:0;">
                                    <i class="bi bi-slash-circle" style="font-size:13px; color:#94a3b8;"></i>
                                </span>
                                <span class="icon-selected-label" id="addStageIconLabel" style="font-size:13px; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ __('crm.choose_icon_optional') }}
                                </span>
                            </span>
                            <span style="display:flex; align-items:center; gap:4px; flex-shrink:0;">
                                <span class="icon-clear-btn" id="addStageIconClear" onclick="event.stopPropagation(); clearIconSelection('addStage')" style="display:none; color:#94a3b8; font-size:14px; padding:2px 4px; border-radius:4px; cursor:pointer;" title="{{ __('crm.no_icon') }}">&times;</span>
                                <i class="bi bi-chevron-down" style="font-size:12px; color:#94a3b8;"></i>
                            </span>
                        </button>
                        <div class="icon-picker-dropdown" id="addStageIconDropdown" style="display:none; position:absolute; top:calc(100% + 4px); inset-inline-start:0; width:100%; min-width:280px; z-index:1050; background:#fff; border:1px solid var(--line); border-radius:12px; box-shadow:0 12px 30px rgba(0,0,0,0.15); max-height:240px; overflow-y:auto; padding:8px;">
                            <div style="position:sticky; top:0; background:#fff; padding-bottom:6px; margin-bottom:6px; border-bottom:1px solid var(--line); z-index:2;">
                                <div style="position:relative;">
                                    <i class="bi bi-search" style="position:absolute; inset-inline-start:10px; top:50%; transform:translateY(-50%); font-size:12px; color:#94a3b8; pointer-events:none;"></i>
                                    <input type="text" placeholder="{{ __('crm.search_icons') }}" oninput="filterIconList('addStage', this.value)" style="width:100%; padding:6px 10px; padding-inline-start:28px; font-size:12px; border:1px solid #dbe1e9; border-radius:8px; outline:none; height:34px;">
                                </div>
                            </div>
                            <div class="icon-option-item" onclick="selectStageIcon('addStage', '', '{{ __('crm.no_icon') }}')" style="display:flex; align-items:center; gap:8px; padding:6px 10px; border-radius:8px; cursor:pointer; font-size:13px; margin-bottom:2px;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                <span style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; background:#f1f5f9; color:#94a3b8; font-size:13px;"><i class="bi bi-slash-circle"></i></span>
                                <span style="color:#64748b; font-weight:600;">{{ __('crm.no_icon') }}</span>
                            </div>
                            <div class="icon-options-list" id="addStageIconList" style="display:grid; grid-template-columns:1fr; gap:2px;">
                                @foreach($crmStageIcons as $ico)
                                    <div class="icon-option-item" data-code="{{ $ico['icon'] }}" data-name="{{ $ico['name'] }}" onclick="selectStageIcon('addStage', '{{ $ico['icon'] }}', '{{ $ico['name'] }}')" style="display:flex; align-items:center; justify-content:space-between; padding:6px 10px; border-radius:8px; cursor:pointer; font-size:13px;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                        <span style="display:flex; align-items:center; gap:8px; min-width:0;">
                                            <span style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; background:#f1f5f9; color:#1e293b; font-size:14px; flex-shrink:0;"><i class="bi {{ $ico['icon'] }}"></i></span>
                                            <span style="font-weight:600; color:#334155; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $ico['name'] }}</span>
                                        </span>
                                        <span style="font-size:11px; color:#94a3b8; font-family:monospace;">{{ $ico['icon'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div style="margin-bottom:18px;">
                <label>{{ __('crm.stage_desc_optional') }}</label>
                <textarea name="description_ar" rows="2" style="min-height:70px;" placeholder="{{ __('crm.stage_desc_placeholder') }}"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn soft" onclick="document.getElementById('addStageModal').style.display='none'">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn primary">{{ __('crm.save_stage') }}</button>
            </div>
        </form>
    </div>
</div>
<!-- MODAL: EDIT STAGE -->
<div id="editStageModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; padding:18px;">
    <div style="background:#fff; border-radius:16px; max-width:520px; width:100%; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="margin:0; font-size:18px;" id="editStageModalTitle">{{ __('crm.edit_stage_modal_title') }}</h3>
            <button type="button" onclick="document.getElementById('editStageModal').style.display='none'" style="border:0; background:transparent; font-size:22px; cursor:pointer">&times;</button>
        </div>
        <form id="editStageForm" method="POST" action="">
            @csrf
            @method('PATCH')
            <div style="margin-bottom:14px;">
                <label>{{ __('crm.stage_name_label') }} <span style="color:var(--red)">*</span></label>
                <input type="text" id="editStageName" name="name_ar" required>
            </div>
            <div class="form-grid" style="margin-bottom:14px;">
                <div>
                    <label>{{ __('crm.stage_color_hex') }}</label>
                    <div style="display:flex; gap:8px;">
                        <input type="color" id="editStageColorPicker" value="#3478f6" style="width:48px; height:42px; padding:2px;" onchange="document.getElementById('editStageColorInput').value=this.value">
                        <input type="text" id="editStageColorInput" name="color" value="#3478f6" pattern="^#[0-9a-fA-F]{6}$" onchange="document.getElementById('editStageColorPicker').value=this.value">
                    </div>
                </div>
                <div>
                    <label>{{ __('crm.order_col') }}</label>
                    <input type="number" id="editStagePosition" name="position" min="1" max="255" required>
                </div>
            </div>
            <div style="margin-bottom:14px;">
                <label>{{ __('crm.icon_optional') }}</label>
                <div class="icon-picker-wrap" id="editStageIconWrap" style="position:relative;">
                    <input type="hidden" id="editStageIcon" name="icon" value="">
                    <button type="button" class="icon-picker-btn" id="editStageIconBtn" onclick="toggleIconDropdown('editStage')" style="width:100%; height:42px; display:flex; align-items:center; justify-content:space-between; gap:8px; border:1px solid #dbe1e9; border-radius:10px; padding:0 12px; background:#fff; color:var(--ink); cursor:pointer; text-align:start;">
                        <span style="display:flex; align-items:center; gap:8px; min-width:0; overflow:hidden;">
                            <span class="icon-preview-box" id="editStageIconPreview" style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; background:#f1f5f9; color:#475569; font-size:15px; flex-shrink:0;">
                                <i class="bi bi-slash-circle" style="font-size:13px; color:#94a3b8;"></i>
                            </span>
                            <span class="icon-selected-label" id="editStageIconLabel" style="font-size:13px; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                {{ __('crm.choose_icon_optional') }}
                            </span>
                        </span>
                        <span style="display:flex; align-items:center; gap:4px; flex-shrink:0;">
                            <span class="icon-clear-btn" id="editStageIconClear" onclick="event.stopPropagation(); clearIconSelection('editStage')" style="display:none; color:#94a3b8; font-size:14px; padding:2px 4px; border-radius:4px; cursor:pointer;" title="{{ __('crm.no_icon') }}">&times;</span>
                            <i class="bi bi-chevron-down" style="font-size:12px; color:#94a3b8;"></i>
                        </span>
                    </button>
                    <div class="icon-picker-dropdown" id="editStageIconDropdown" style="display:none; position:absolute; top:calc(100% + 4px); inset-inline-start:0; width:100%; min-width:280px; z-index:1050; background:#fff; border:1px solid var(--line); border-radius:12px; box-shadow:0 12px 30px rgba(0,0,0,0.15); max-height:240px; overflow-y:auto; padding:8px;">
                        <div style="position:sticky; top:0; background:#fff; padding-bottom:6px; margin-bottom:6px; border-bottom:1px solid var(--line); z-index:2;">
                            <div style="position:relative;">
                                <i class="bi bi-search" style="position:absolute; inset-inline-start:10px; top:50%; transform:translateY(-50%); font-size:12px; color:#94a3b8; pointer-events:none;"></i>
                                <input type="text" placeholder="{{ __('crm.search_icons') }}" oninput="filterIconList('editStage', this.value)" style="width:100%; padding:6px 10px; padding-inline-start:28px; font-size:12px; border:1px solid #dbe1e9; border-radius:8px; outline:none; height:34px;">
                            </div>
                        </div>
                        <div class="icon-option-item" onclick="selectStageIcon('editStage', '', '{{ __('crm.no_icon') }}')" style="display:flex; align-items:center; gap:8px; padding:6px 10px; border-radius:8px; cursor:pointer; font-size:13px; margin-bottom:2px;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <span style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; background:#f1f5f9; color:#94a3b8; font-size:13px;"><i class="bi bi-slash-circle"></i></span>
                            <span style="color:#64748b; font-weight:600;">{{ __('crm.no_icon') }}</span>
                        </div>
                        <div class="icon-options-list" id="editStageIconList" style="display:grid; grid-template-columns:1fr; gap:2px;">
                            @foreach($crmStageIcons as $ico)
                                <div class="icon-option-item" data-code="{{ $ico['icon'] }}" data-name="{{ $ico['name'] }}" onclick="selectStageIcon('editStage', '{{ $ico['icon'] }}', '{{ $ico['name'] }}')" style="display:flex; align-items:center; justify-content:space-between; padding:6px 10px; border-radius:8px; cursor:pointer; font-size:13px;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                    <span style="display:flex; align-items:center; gap:8px; min-width:0;">
                                        <span style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; background:#f1f5f9; color:#1e293b; font-size:14px; flex-shrink:0;"><i class="bi {{ $ico['icon'] }}"></i></span>
                                        <span style="font-weight:600; color:#334155; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $ico['name'] }}</span>
                                    </span>
                                    <span style="font-size:11px; color:#94a3b8; font-family:monospace;">{{ $ico['icon'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div style="margin-bottom:14px;">
                <label>{{ __('crm.stage_desc_optional') }}</label>
                <textarea id="editStageDescription" name="description_ar" rows="2" style="min-height:70px;"></textarea>
            </div>
            <div id="editStageActiveWrap" style="margin-bottom:18px;">
                <label class="check-card" style="cursor:pointer">
                    <input type="checkbox" id="editStageActive" name="is_active" value="1">
                    <div>
                        <strong>{{ __('crm.active_stage_checkbox_label') }}</strong>
                        <small>{{ __('crm.inactive_stage_checkbox_hint') }}</small>
                    </div>
                </label>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn soft" onclick="document.getElementById('editStageModal').style.display='none'">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn primary">{{ __('crm.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: ADD DONATION TYPE -->
<div id="addTypeModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; padding:18px;">
    <div style="background:#fff; border-radius:16px; max-width:440px; width:100%; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="margin:0; font-size:18px;">{{ __('crm.add_donation_type_title') }}</h3>
            <button type="button" onclick="document.getElementById('addTypeModal').style.display='none'" style="border:0; background:transparent; font-size:22px; cursor:pointer">&times;</button>
        </div>
        <form method="POST" action="{{ route('v2.settings.stages.donation-types.store') }}">
            @csrf
            <div style="margin-bottom:18px;">
                <label>{{ __('crm.donation_type_name_label') }} <span style="color:var(--red)">*</span></label>
                <input type="text" name="name_ar" required placeholder="{{ __('crm.donation_type_name_label') }}">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn soft" onclick="document.getElementById('addTypeModal').style.display='none'">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn primary">{{ __('crm.add_action') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: ADD DONATION PURPOSE -->
<div id="addPurposeModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; padding:18px;">
    <div style="background:#fff; border-radius:16px; max-width:440px; width:100%; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="margin:0; font-size:18px;">{{ __('crm.add_donation_purpose_title') }}</h3>
            <button type="button" onclick="document.getElementById('addPurposeModal').style.display='none'" style="border:0; background:transparent; font-size:22px; cursor:pointer">&times;</button>
        </div>
        <form method="POST" action="{{ route('v2.settings.stages.donation-purposes.store') }}">
            @csrf
            <div style="margin-bottom:18px;">
                <label>{{ __('crm.donation_purpose_name_label') }} <span style="color:var(--red)">*</span></label>
                <input type="text" name="name_ar" required placeholder="{{ __('crm.donation_purpose_name_label') }}">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn soft" onclick="document.getElementById('addPurposeModal').style.display='none'">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn primary">{{ __('crm.add_action') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
const crmIconsMap = @json(collect($crmStageIcons)->pluck('name', 'icon'));

function toggleIconDropdown(prefix) {
    const dropdown = document.getElementById(prefix + 'IconDropdown');
    if (!dropdown) return;
    const isVisible = dropdown.style.display === 'block';
    closeAllIconDropdowns();
    if (!isVisible) {
        dropdown.style.display = 'block';
        const searchInput = dropdown.querySelector('input');
        if (searchInput) {
            searchInput.value = '';
            filterIconList(prefix, '');
            setTimeout(() => searchInput.focus(), 50);
        }
    }
}

function closeAllIconDropdowns() {
    document.querySelectorAll('.icon-picker-dropdown').forEach(el => el.style.display = 'none');
}

function selectStageIcon(prefix, iconCode, iconLabel) {
    const input = document.getElementById(prefix === 'addStage' ? 'addStageIconInput' : 'editStageIcon');
    const preview = document.getElementById(prefix + 'IconPreview');
    const label = document.getElementById(prefix + 'IconLabel');
    const clearBtn = document.getElementById(prefix + 'IconClear');

    if (input) input.value = iconCode || '';

    if (iconCode) {
        const cleanCode = iconCode.startsWith('bi-') ? iconCode : ('bi-' + iconCode);
        if (preview) preview.innerHTML = `<i class="bi ${cleanCode}"></i>`;
        if (label) {
            label.textContent = iconLabel || crmIconsMap[iconCode] || iconCode;
            label.style.color = 'var(--ink)';
            label.style.fontWeight = '600';
        }
        if (clearBtn) clearBtn.style.display = 'inline-flex';
    } else {
        if (preview) preview.innerHTML = '<i class="bi bi-slash-circle" style="font-size:13px; color:#94a3b8;"></i>';
        if (label) {
            label.textContent = @json(__('crm.choose_icon_optional'));
            label.style.color = '#64748b';
            label.style.fontWeight = 'normal';
        }
        if (clearBtn) clearBtn.style.display = 'none';
    }

    closeAllIconDropdowns();
}

function clearIconSelection(prefix) {
    selectStageIcon(prefix, '', '');
}

function filterIconList(prefix, query) {
    const list = document.getElementById(prefix + 'IconList');
    if (!list) return;
    const q = (query || '').toLowerCase().trim();
    list.querySelectorAll('.icon-option-item').forEach(item => {
        const code = (item.getAttribute('data-code') || '').toLowerCase();
        const name = (item.getAttribute('data-name') || '').toLowerCase();
        if (!q || code.includes(q) || name.includes(q)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

function openAddStageModal() {
    selectStageIcon('addStage', '', '');
    document.getElementById('addStageModal').style.display = 'flex';
}

function openEditModal(stage) {
    const form = document.getElementById('editStageForm');
    form.action = `/settings/stages/${stage.id}`;
    document.getElementById('editStageName').value = stage.name_ar || '';
    document.getElementById('editStageColorPicker').value = stage.color || '#3478f6';
    document.getElementById('editStageColorInput').value = stage.color || '#3478f6';
    document.getElementById('editStagePosition').value = stage.position || 1;
    document.getElementById('editStageDescription').value = stage.description_ar || '';

    const stageIcon = stage.icon || '';
    const stageIconLabel = crmIconsMap[stageIcon] || stageIcon;
    selectStageIcon('editStage', stageIcon, stageIconLabel);

    const activeWrap = document.getElementById('editStageActiveWrap');
    const activeCheck = document.getElementById('editStageActive');
    activeCheck.checked = Boolean(stage.is_active);

    if (stage.is_primary) {
        document.getElementById('editStageModalTitle').textContent = @json(__('crm.edit_primary_stage_title')).replace(':name', stage.name_ar);
        activeWrap.style.display = 'none';
    } else {
        document.getElementById('editStageModalTitle').textContent = @json(__('crm.edit_additional_stage_title')).replace(':name', stage.name_ar);
        activeWrap.style.display = 'block';
    }

    document.getElementById('editStageModal').style.display = 'flex';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.icon-picker-wrap')) {
        closeAllIconDropdowns();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openDropdown = document.querySelector('.icon-picker-dropdown[style*="display: block"]');
        if (openDropdown) {
            e.stopPropagation();
            closeAllIconDropdowns();
        }
    }
});
</script>
@endsection
