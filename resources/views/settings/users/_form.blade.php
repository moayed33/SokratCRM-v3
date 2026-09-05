@php
    $isEdit = isset($managedUser);
    $selectedGroupIds = collect(old(
        'group_ids',
        $isEdit ? $managedUser->groups->pluck('id')->all() : [],
    ))->map(static fn ($id) => (int) $id);

    $currentSubregionId = (int) old('collection_subregion_id', $managedUser->collection_subregion_id ?? 0);
    $currentGovId = 0;
    $subregionsMap = [];
    if (!empty($governorates)) {
        foreach ($governorates as $g) {
            if ($currentSubregionId > 0 && $currentGovId === 0 && $g->subregions->contains('id', $currentSubregionId)) {
                $currentGovId = $g->id;
            }
            $subregionsMap[$g->id] = $g->subregions->map(static fn ($s) => [
                'id' => $s->id,
                'name_ar' => $s->name_ar,
                'name_en' => $s->name_en,
                'is_active' => (bool) $s->is_active,
            ])->all();
        }
    }
@endphp

<style>
    .role-selection-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 14px;
        margin-bottom: 22px;
    }
    .role-card {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        border: 2px solid var(--line);
        border-radius: 14px;
        background: var(--card);
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
    }
    .role-card:hover {
        border-color: #f3aab1;
        background: rgba(220, 38, 55, 0.02);
    }
    .role-card.selected {
        border-color: var(--red);
        background: #fff5f6;
        box-shadow: 0 4px 12px rgba(220, 38, 55, 0.1);
    }
    html.dark-mode .role-card.selected {
        background: rgba(220, 38, 55, 0.15);
        border-color: #f87171;
    }
    .role-card input[type="radio"],
    .role-card input[type="checkbox"],
    .subordinate-item input[type="checkbox"] {
        width: 18px !important;
        min-width: 18px !important;
        height: 18px !important;
        padding: 0 !important;
        margin: 0 !important;
        border: 0 !important;
        accent-color: var(--red);
        cursor: pointer;
        flex-shrink: 0;
    }
    .role-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: rgba(220, 38, 55, 0.08);
        color: var(--red);
        font-size: 18px;
        flex-shrink: 0;
        transition: all 0.15s ease;
    }
    .role-card.selected .role-card-icon {
        background: var(--red);
        color: #ffffff;
    }
    .role-card-content {
        flex: 1;
        min-width: 0;
    }
    .role-card-title {
        font-size: 15px;
        font-weight: 800;
        color: var(--ink);
        display: block;
        margin-bottom: 2px;
    }
    .role-card-desc {
        display: block;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.4;
    }
    .form-section-title {
        font-size: 16px;
        font-weight: 800;
        margin: 24px 0 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--ink);
        border-bottom: 1px solid var(--line);
        padding-bottom: 8px;
    }
    .geo-dropdown-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    @media(max-width: 768px) {
        .geo-dropdown-grid {
            grid-template-columns: 1fr;
        }
        .role-selection-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

{{-- STEP 1: ROLE SELECTION (MANDATORY & FIRST) --}}
<div class="field full" style="margin-bottom: 20px;">
    <label style="font-size: 15px; font-weight: 800; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
        <i class="bi bi-person-check-fill" style="color:var(--red)"></i>
        <span>{{ __('crm.choose_user_role') ?? 'تحديد دور / وظيفة المستخدم' }}</span>
        <span style="color:var(--red)">*</span>
    </label>
    <div class="role-selection-grid">
        @foreach ($groups as $group)
            @php
                $gCode = strtolower($group->code);
                $icon = match($gCode) {
                    'super-admin' => 'bi-shield-shaded',
                    'branch-admin' => 'bi-building-gear',
                    'collection-manager', 'collection_manager' => 'bi-cash-stack',
                    'manager', 'team-leader', 'sales-manager' => 'bi-person-gear',
                    'collector', 'field-collector' => 'bi-cash-coin',
                    default => 'bi-person-badge',
                };
                $isSelected = $selectedGroupIds->contains((int) $group->id);
            @endphp
            <label class="role-card {{ $isSelected ? 'selected' : '' }}" id="role_card_{{ $group->id }}">
                <input
                    type="radio"
                    name="group_ids[]"
                    class="group-radio"
                    data-group-code="{{ $gCode }}"
                    value="{{ $group->id }}"
                    @checked($isSelected)
                    required
                >
                <div class="role-card-icon">
                    <i class="bi {{ $icon }}"></i>
                </div>
                <div class="role-card-content">
                    <span class="role-card-title">{{ $group->name }}</span>
                    <span class="role-card-desc">{{ $group->description ?: $group->code }}</span>
                </div>
            </label>
        @endforeach
    </div>
</div>

{{-- STEP 2: ACCOUNT & IDENTITY DATA --}}
<div class="form-section-title">
    <i class="bi bi-person-vcard"></i>
    <span>{{ __('crm.account_data') }}</span>
</div>

<div class="form-grid">
    <div class="field">
        <label for="name">{{ __('crm.full_name') }} <span style="color:var(--red)">*</span></label>
        <input id="name" name="name" required maxlength="150" placeholder="مثال: أحمد علي محمد" value="{{ old('name', $managedUser->name ?? '') }}">
        <div class="hint">اكتب الاسم الكامل للموظف، وسيتم توليد اسم الدخول تلقائياً.</div>
    </div>

    <div class="field">
        <label for="mobile_phone">{{ __('crm.mobile_phone') ?? 'رقم الهاتف المحمول' }} <span id="mobilePhoneReq" style="color:var(--red); display:none;">*</span></label>
        <input id="mobile_phone" name="mobile_phone" type="tel" dir="ltr" placeholder="010XXXXXXXX" value="{{ old('mobile_phone', $managedUser->mobile_phone ?? '') }}">
        <div class="hint">مطلوب للتواصل ولاتصالات المحصلين والمشرفين.</div>
    </div>

    <div class="field">
        <label for="email">{{ __('crm.email_optional') }}</label>
        <input id="email" name="email" type="email" dir="ltr" placeholder="name@domain.com" value="{{ old('email', $managedUser->email ?? '') }}">
    </div>

    <div class="field">
        <label for="username">
            {{ __('crm.username_label') }}
            <span class="optional" style="color:var(--muted); font-size:11px; font-weight:normal;">({{ __('crm.optional_suffix') }} - تلقائي)</span>
        </label>
        <input id="username" name="username" maxlength="100" dir="ltr" placeholder="يتم التوليد تلقائياً أو اكتب يدوياً" value="{{ old('username', $managedUser->username ?? '') }}">
        <div class="hint">{{ __('crm.username_rules') }}</div>
    </div>
    @unless ($isEdit)
        <div class="field">
            <label for="password">{{ __('crm.password') }} <span style="color:var(--red)">*</span></label>
            <input id="password" name="password" type="password" required autocomplete="new-password">
        </div>
        <div class="field">
            <label for="password_confirmation">{{ __('crm.confirm_password') }} <span style="color:var(--red)">*</span></label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        </div>
    @endunless
</div>

{{-- STEP 3: BRANCH & ROLE CONFIGURATION --}}
<div class="form-section-title" id="roleConfigTitle">
    <i class="bi bi-sliders"></i>
    <span>{{ __('crm.role_configuration') ?? 'تخصيص الصلاحيات والفرع والمنطقة' }}</span>
</div>

<div class="form-grid">
    {{-- Branch (Hidden for Super Admin, required for others) --}}
    <div class="field" id="branchField">
        <label for="branch_id">{{ __('crm.branch') }} <span style="color:var(--red)">*</span></label>
        <select id="branch_id" name="branch_id">
            <option value="">— {{ __('crm.select_branch') }} —</option>
            @if (!empty($branches))
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $managedUser->branch_id ?? '') === (string) $branch->id)>
                        {{ $branch->name_ar }} {{ $branch->name_en ? '('.$branch->name_en.')' : '' }}
                    </option>
                @endforeach
            @endif
        </select>
        <div class="hint">{{ __('crm.user_branch_assignment_hint') }}</div>
    </div>

    {{-- Direct Manager (Shown for Collectors, Employees) --}}
    <div class="field" id="directManagerField">
        <label for="manager_id">{{ __('crm.direct_manager_or_team_leader') }} <span id="managerReq" style="color:var(--red); display:none;">*</span></label>
        <select id="manager_id" name="manager_id">
            <option value="">— {{ __('crm.no_direct_manager') }} —</option>
            @if (!empty($managers))
                @foreach ($managers as $mgr)
                    <option value="{{ $mgr->id }}" data-branch-id="{{ $mgr->branch_id ?? '' }}" @selected((string) old('manager_id', $managedUser->manager_id ?? '') === (string) $mgr->id)>
                        {{ $mgr->name }} ({{ $mgr->username }})
                    </option>
                @endforeach
            @endif
        </select>
        <div class="hint">{{ __('crm.direct_manager_hint') }}</div>
    </div>

    {{-- Collector Geography (ONLY for Collector) --}}
    <div class="field full" id="collectorSubregionField" style="display:none; padding:16px; border:2px dashed #0284c7; border-radius:12px; background:rgba(2, 132, 199, 0.03);">
        <label style="font-weight:800; color:#0284c7; margin-bottom:8px; display:block;">
            <i class="bi bi-geo-alt-fill"></i> {{ __('crm.assigned_collector_subregion') }} <span style="color:var(--red)">*</span>
        </label>
        <div class="geo-dropdown-grid">
            <div>
                <label for="collector_governorate_id" style="font-size:12px; margin-bottom:4px;">{{ __('crm.governorate') }} <span style="color:var(--red)">*</span></label>
                <select id="collector_governorate_id">
                    <option value="">— {{ __('crm.select_governorate') }} —</option>
                    @if(!empty($governorates))
                        @foreach($governorates as $gov)
                            <option value="{{ $gov->id }}" @selected($currentGovId === $gov->id)>
                                {{ $gov->name_ar }} @if($gov->name_en)({{ $gov->name_en }})@endif
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div>
                <label for="collection_subregion_id" style="font-size:12px; margin-bottom:4px;">{{ __('crm.subregion') }} <span style="color:var(--red)">*</span></label>
                <select id="collection_subregion_id" name="collection_subregion_id" @disabled(empty($currentGovId))>
                    <option value="">{{ empty($currentGovId) ? __('crm.select_governorate_first') : __('crm.select_subregion') }}</option>
                    @if(!empty($governorates) && !empty($currentGovId))
                        @foreach($governorates as $gov)
                            @if($gov->id === $currentGovId)
                                @foreach($gov->subregions as $sub)
                                    @php
                                        $isCurrent = $currentSubregionId === (int) $sub->id;
                                        $renderOption = $sub->is_active || $isCurrent;
                                    @endphp
                                    @if($renderOption)
                                        <option value="{{ $sub->id }}" data-gov-id="{{ $gov->id }}" @selected($isCurrent)>
                                            {{ $sub->name_ar }} @if($sub->name_en)({{ $sub->name_en }})@endif @if(!$sub->is_active) ({{ __('crm.inactive') }})@endif
                                        </option>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="hint" style="margin-top:8px;">{{ __('crm.assigned_collector_subregion_hint') }}</div>
    </div>

    @if(!empty($isVoipConnected) || !empty($managedUser->voip_extension))
    {{-- VoIP Extension --}}
    <div class="field" id="voipExtensionField">
        <label for="voip_extension">{{ __('crm.extension_label') ?? 'تحويلة الهاتف (VoIP Extension)' }}</label>
        @php
            $currentExt = (string) old('voip_extension', $managedUser->voip_extension ?? '');
            $foundCurrent = false;
        @endphp
        <select id="voip_extension" name="voip_extension">
            <option value="">{{ __('crm.no_extension_option') ?? 'بدون تحويلة' }}</option>
            @if(!empty($voipExtensions))
                @foreach($voipExtensions as $ext)
                    @php
                        $extNum = (string) (is_array($ext) ? ($ext['extension'] ?? '') : (is_object($ext) ? ($ext->extension ?? '') : ''));
                        $extName = is_array($ext) ? ($ext['name'] ?? '') : (is_object($ext) ? ($ext->name ?? '') : '');
                        $isOnline = is_array($ext) ? (!empty($ext['online'])) : (is_object($ext) ? (!empty($ext->online)) : false);
                        $isWebRtc = in_array($extNum, ['150', '151', '170']) || (is_array($ext) ? !empty($ext['webrtc']) : (!empty($ext->webrtc)));
                        if ($currentExt !== '' && $currentExt === $extNum) {
                            $foundCurrent = true;
                        }
                        $statusBadge = $isOnline ? ' 🟢' : '';
                        $webrtcBadge = $isWebRtc ? ' 🌐 [WebRTC Softphone]' : ' 📞 [SIP Desk]';
                        $displayName = $extName ? "{$extNum} - {$extName}{$webrtcBadge}{$statusBadge}" : "{$extNum}{$webrtcBadge}{$statusBadge}";
                    @endphp
                    <option value="{{ $extNum }}" @selected($currentExt === $extNum)>
                        {{ $displayName }}
                    </option>
                @endforeach
            @endif
            @if($currentExt !== '' && !$foundCurrent)
                <option value="{{ $currentExt }}" selected>
                    {{ $currentExt }} {{ __('crm.custom_extension_label') ?? '(تحويلة مخصصة)' }}
                </option>
            @endif
        </select>
        <div class="hint">{{ __('crm.extension_hint') ?? 'اختر تحويلة WebRTC (150, 151, 170) لتفعيل الاتصال المباشر من المتصفح عبر سوكرات فويس.' }}</div>
    </div>
    @endif

<input type="hidden" name="subordinates_section_rendered" value="1">

{{-- SUPERVISED SALES TEAM (When Manager/Supervisor role is selected) --}}
<div class="field full subordinates-panel" id="salesSubordinatesSection" style="display:none; margin-top:20px; border:1px solid var(--line, #e2e8f0); border-radius:14px; padding:18px; background:var(--card, #fff);">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; flex-wrap:wrap;">
        <div>
            <h3 style="margin:0; font-size:16px; font-weight:800; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-people-fill" style="color:var(--red, #dc2637)"></i>
                <span>{{ __('crm.supervised_sales_team') }}</span>
                <span class="badge" id="salesSelectedBadge" style="font-size:11px; padding:3px 9px;">0</span>
            </h3>
            <p class="hint" style="margin:4px 0 0">{{ __('crm.supervised_sales_team_hint') }}</p>
        </div>
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <div style="position:relative; min-width:200px;">
                <i class="bi bi-search" style="position:absolute; inset-inline-start:10px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:13px;"></i>
                <input type="search" id="salesEmployeeSearch" class="subordinate-search" placeholder="{{ __('crm.search_employees_placeholder') }}" style="height:34px; padding-inline-start:30px; font-size:12px;">
            </div>
            <button type="button" class="btn soft small" id="selectAllSalesBtn">{{ __('crm.select_all') }}</button>
            <button type="button" class="btn soft small" id="deselectAllSalesBtn">{{ __('crm.deselect_all') }}</button>
        </div>
    </div>

    <div class="checkbox-grid subordinate-grid" id="salesEmployeesGrid" style="max-height:320px; overflow-y:auto; padding:2px;">
        @if(!empty($salesEmployees) && $salesEmployees->isNotEmpty())
            @foreach ($salesEmployees as $emp)
                @php
                    $isAssignedToThis = isset($currentSubordinateIds) && $currentSubordinateIds->contains($emp->id);
                    $isChecked = $isAssignedToThis || in_array($emp->id, (array) old('subordinate_ids', []));
                    $hasOtherManager = $emp->manager && $emp->manager->id !== ($managedUser->id ?? null);
                @endphp
                <label class="check-card subordinate-item" data-branch-id="{{ $emp->branch_id ?? '' }}" data-search-text="{{ mb_strtolower($emp->name.' '.$emp->username) }}">
                    <input
                        type="checkbox"
                        name="subordinate_ids[]"
                        class="sales-subordinate-checkbox"
                        value="{{ $emp->id }}"
                        @checked($isChecked)
                    >
                    <span style="min-width:0; flex:1;">
                        <strong style="font-size:13px; display:flex; align-items:center; justify-content:space-between; gap:6px; flex-wrap:wrap;">
                            <span>{{ $emp->name }}</span>
                            @if($isAssignedToThis)
                                <span class="badge active" style="font-size:10px; padding:2px 6px;">{{ __('crm.in_your_team') }}</span>
                            @elseif($hasOtherManager)
                                <span class="badge" style="background:#fff7df; color:#8a6100; font-size:10px; padding:2px 6px;">{{ __('crm.currently_under_leader', ['name' => $emp->manager->name]) }}</span>
                            @else
                                <span class="badge" style="font-size:10px; padding:2px 6px;">{{ __('crm.no_current_leader') }}</span>
                            @endif
                        </strong>
                        <small style="display:flex; align-items:center; gap:8px; margin-top:3px; color:var(--muted)">
                            <span>&#64;{{ $emp->username }}</span>
                            @if($emp->branch)
                                <span>· {{ $emp->branch->name_ar }}</span>
                            @endif
                        </small>
                    </span>
                </label>
            @endforeach
        @endif
        <div class="branch-empty-state" id="salesNoBranchPrompt" style="display:none; grid-column:1/-1; padding:24px; text-align:center; color:var(--muted); font-size:13px;">
            <i class="bi bi-geo-alt" style="font-size:24px; display:block; margin-bottom:8px; color:var(--red, #dc2637)"></i>
            <strong>{{ __('crm.select_branch_first_to_assign') }}</strong>
        </div>
        <div class="branch-empty-state" id="salesNoBranchMatches" style="display:none; grid-column:1/-1; padding:24px; text-align:center; color:var(--muted); font-size:13px;">
            <i class="bi bi-people" style="font-size:24px; display:block; margin-bottom:8px;"></i>
            <strong>{{ __('crm.no_employees_in_selected_branch') }}</strong>
        </div>
    </div>
</div>

{{-- SUPERVISED COLLECTORS (When Manager/Supervisor role is selected) --}}
<div class="field full subordinates-panel" id="collectorSubordinatesSection" style="display:none; margin-top:20px; border:1px solid var(--line, #e2e8f0); border-radius:14px; padding:18px; background:var(--card, #fff);">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; flex-wrap:wrap;">
        <div>
            <h3 style="margin:0; font-size:16px; font-weight:800; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-cash-stack" style="color:var(--green, #16a34a)"></i>
                <span>{{ __('crm.supervised_collectors') }}</span>
                <span class="badge" id="collectorSelectedBadge" style="font-size:11px; padding:3px 9px;">0</span>
            </h3>
            <p class="hint" style="margin:4px 0 0">{{ __('crm.supervised_collectors_hint') }}</p>
        </div>
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <div style="position:relative; min-width:200px;">
                <i class="bi bi-search" style="position:absolute; inset-inline-start:10px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:13px;"></i>
                <input type="search" id="collectorSearch" class="subordinate-search" placeholder="{{ __('crm.search_collectors_placeholder') }}" style="height:34px; padding-inline-start:30px; font-size:12px;">
            </div>
            <button type="button" class="btn soft small" id="selectAllCollectorsBtn">{{ __('crm.select_all') }}</button>
            <button type="button" class="btn soft small" id="deselectAllCollectorsBtn">{{ __('crm.deselect_all') }}</button>
        </div>
    </div>

    <div class="checkbox-grid subordinate-grid" id="collectorsGrid" style="max-height:320px; overflow-y:auto; padding:2px;">
        @if(!empty($collectors) && $collectors->isNotEmpty())
            @foreach ($collectors as $col)
                @php
                    $isAssignedToThis = isset($currentSubordinateIds) && $currentSubordinateIds->contains($col->id);
                    $isChecked = $isAssignedToThis || in_array($col->id, (array) old('subordinate_ids', []));
                    $hasOtherManager = $col->manager && $col->manager->id !== ($managedUser->id ?? null);
                @endphp
                <label class="check-card subordinate-item" data-branch-id="{{ $col->branch_id ?? '' }}" data-search-text="{{ mb_strtolower($col->name.' '.$col->username) }}">
                    <input
                        type="checkbox"
                        name="subordinate_ids[]"
                        class="collector-subordinate-checkbox"
                        value="{{ $col->id }}"
                        @checked($isChecked)
                    >
                    <span style="min-width:0; flex:1;">
                        <strong style="font-size:13px; display:flex; align-items:center; justify-content:space-between; gap:6px; flex-wrap:wrap;">
                            <span>{{ $col->name }}</span>
                            @if($isAssignedToThis)
                                <span class="badge active" style="font-size:10px; padding:2px 6px;">{{ __('crm.in_your_team') }}</span>
                            @elseif($hasOtherManager)
                                <span class="badge" style="background:#fff7df; color:#8a6100; font-size:10px; padding:2px 6px;">{{ __('crm.currently_under_leader', ['name' => $col->manager->name]) }}</span>
                            @else
                                <span class="badge" style="font-size:10px; padding:2px 6px;">{{ __('crm.no_current_leader') }}</span>
                            @endif
                        </strong>
                        <small style="display:flex; align-items:center; gap:8px; margin-top:3px; color:var(--muted)">
                            <span>&#64;{{ $col->username }}</span>
                            @if($col->collectionSubregion)
                                <span>· {{ $col->collectionSubregion->full_name }}</span>
                            @elseif($col->collection_zone)
                                <span>· {{ $col->collection_zone }}</span>
                            @elseif($col->branch)
                                <span>· {{ $col->branch->name_ar }}</span>
                            @endif
                        </small>
                    </span>
                </label>
            @endforeach
        @endif
        <div class="branch-empty-state" id="collectorNoBranchPrompt" style="display:none; grid-column:1/-1; padding:24px; text-align:center; color:var(--muted); font-size:13px;">
            <i class="bi bi-geo-alt" style="font-size:24px; display:block; margin-bottom:8px; color:var(--green, #16a34a)"></i>
            <strong>{{ __('crm.select_branch_first_to_assign') }}</strong>
        </div>
        <div class="branch-empty-state" id="collectorNoBranchMatches" style="display:none; grid-column:1/-1; padding:24px; text-align:center; color:var(--muted); font-size:13px;">
            <i class="bi bi-cash-stack" style="font-size:24px; display:block; margin-bottom:8px;"></i>
            <strong>{{ __('crm.no_collectors_in_selected_branch') }}</strong>
        </div>
    </div>
</div>

<div class="actions" style="margin-top:24px;">
    <button class="btn primary" style="min-height:46px; padding:0 24px; font-size:15px;" type="submit">{{ $isEdit ? __('crm.save_changes') : __('crm.create_user') }}</button>
    <a class="btn" href="{{ route('v2.settings.users.index') }}">{{ __('crm.cancel') }}</a>
</div>

<script>
(() => {
    const superAdminCodes = ['super-admin'];
    const salesLeaderCodes = ['manager', 'branch-admin', 'team-leader', 'sales-manager'];
    const collectionManagerCodes = ['manager', 'branch-admin', 'collection-manager', 'collection_manager'];
    const collectorCodes = ['collector', 'field-collector'];
    const employeeCodes = ['employee'];
    const roleCards = Array.from(document.querySelectorAll('.role-card'));
    const groupRadios = Array.from(document.querySelectorAll('.group-radio'));
    const branchSelect = document.getElementById('branch_id');
    const managerSelect = document.getElementById('manager_id');
    const branchField = document.getElementById('branchField');
    const directManagerField = document.getElementById('directManagerField');
    const collectorSubregionField = document.getElementById('collectorSubregionField');
    const voipExtensionField = document.getElementById('voipExtensionField');
    const salesSection = document.getElementById('salesSubordinatesSection');
    const collectorSection = document.getElementById('collectorSubordinatesSection');
    const mobilePhoneReq = document.getElementById('mobilePhoneReq');
    const managerReq = document.getElementById('managerReq');

    // Update Role Card Visual Selection
    const syncRoleCards = () => {
        groupRadios.forEach(radio => {
            const card = radio.closest('.role-card');
            if (card) {
                if (radio.checked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            }
        });
    };

    // Subordinate Counters
    const updateCounts = () => {
        const salesChecked = document.querySelectorAll('#salesEmployeesGrid .subordinate-item:not([style*="display: none"]) .sales-subordinate-checkbox:checked, #salesEmployeesGrid .sales-subordinate-checkbox:checked').length;
        const salesBadge = document.getElementById('salesSelectedBadge');
        if (salesBadge) {
            salesBadge.textContent = salesChecked;
            salesBadge.className = salesChecked > 0 ? 'badge active' : 'badge';
        }

        const colChecked = document.querySelectorAll('#collectorsGrid .subordinate-item:not([style*="display: none"]) .collector-subordinate-checkbox:checked, #collectorsGrid .collector-subordinate-checkbox:checked').length;
        const colBadge = document.getElementById('collectorSelectedBadge');
        if (colBadge) {
            colBadge.textContent = colChecked;
            colBadge.className = colChecked > 0 ? 'badge active' : 'badge';
        }
    };

    // Filter Subordinates by Branch and Search Query
    const filterGridByBranchAndSearch = (gridId, searchInputId, noBranchPromptId, noMatchesPromptId) => {
        const grid = document.getElementById(gridId);
        const searchInput = document.getElementById(searchInputId);
        const noBranchPrompt = document.getElementById(noBranchPromptId);
        const noMatchesPrompt = document.getElementById(noMatchesPromptId);
        if (!grid) return;

        const selectedBranch = (branchSelect?.value || '').trim();
        const query = (searchInput?.value || '').trim().toLowerCase();
        const items = Array.from(grid.querySelectorAll('.subordinate-item'));

        if (!selectedBranch) {
            items.forEach(item => { item.style.display = 'none'; });
            if (noBranchPrompt) noBranchPrompt.style.display = 'block';
            if (noMatchesPrompt) noMatchesPrompt.style.display = 'none';
            return;
        }

        if (noBranchPrompt) noBranchPrompt.style.display = 'none';

        let visibleCount = 0;
        items.forEach(item => {
            const itemBranch = (item.dataset.branchId || '').trim();
            const text = (item.dataset.searchText || '').toLowerCase();
            const matchesBranch = !itemBranch || itemBranch === selectedBranch;
            const matchesQuery = !query || text.includes(query);

            if (matchesBranch && matchesQuery) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (noMatchesPrompt) {
            noMatchesPrompt.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    };

    // Filter Managers Dropdown by Selected Branch
    const filterManagersByBranch = () => {
        if (!managerSelect) return;
        const selectedBranch = (branchSelect?.value || '').trim();
        Array.from(managerSelect.options).forEach((opt, idx) => {
            if (idx === 0) return;
            const optBranch = opt.dataset.branchId;
            const matches = !selectedBranch || !optBranch || optBranch === selectedBranch;
            opt.style.display = matches ? '' : 'none';
        });
        const currentOpt = managerSelect.selectedOptions[0];
        if (currentOpt && currentOpt.dataset.branchId && selectedBranch && currentOpt.dataset.branchId !== selectedBranch) {
            managerSelect.value = '';
        }
    };

    const filterAllSubordinates = () => {
        filterGridByBranchAndSearch('salesEmployeesGrid', 'salesEmployeeSearch', 'salesNoBranchPrompt', 'salesNoBranchMatches');
        filterGridByBranchAndSearch('collectorsGrid', 'collectorSearch', 'collectorNoBranchPrompt', 'collectorNoBranchMatches');
        filterManagersByBranch();
        updateCounts();
    };

    // Role-Driven Visibility & Form Constraints
    const updateVisibility = () => {
        syncRoleCards();

        const selectedRadio = groupRadios.find(r => r.checked);
        const selectedCode = (selectedRadio?.dataset.groupCode || '').toLowerCase().trim();

        const isSuperAdmin = superAdminCodes.includes(selectedCode);
        const isSalesLeader = salesLeaderCodes.includes(selectedCode);
        const isCollectionManager = collectionManagerCodes.includes(selectedCode);
        const isCollector = collectorCodes.includes(selectedCode);
        const isEmployee = employeeCodes.includes(selectedCode);
        // Super Admin has no branch or manager or collector subregion
        if (branchField) {
            branchField.style.display = isSuperAdmin ? 'none' : 'block';
            const branchInput = branchField.querySelector('select');
            if (branchInput) branchInput.required = !isSuperAdmin;
        }

        // Direct Manager shown for non-superadmin, non-branchadmin
        if (directManagerField) {
            const needsManager = isCollector || isEmployee;
            directManagerField.style.display = (!isSuperAdmin && needsManager) ? 'block' : 'none';
            if (managerReq) managerReq.style.display = isCollector ? 'inline' : 'none';
        }

        // Collector Geography shown ONLY for Collector
        if (collectorSubregionField) {
            collectorSubregionField.style.display = isCollector ? 'block' : 'none';
            const subSelect = document.getElementById('collection_subregion_id');
            if (subSelect) {
                if (isCollector) {
                    subSelect.removeAttribute('disabled');
                } else {
                    subSelect.setAttribute('disabled', 'disabled');
                }
            }
        }

        // Mobile Phone is required for collectors
        if (mobilePhoneReq) {
            mobilePhoneReq.style.display = isCollector ? 'inline' : 'none';
            const phoneInput = document.getElementById('mobile_phone');
            if (phoneInput) phoneInput.required = isCollector;
        }

        // Sales Subordinates Panel
        if (salesSection) {
            salesSection.style.display = isSalesLeader ? 'block' : 'none';
            if (!isSalesLeader) {
                salesSection.querySelectorAll('.sales-subordinate-checkbox').forEach(cb => { cb.checked = false; });
            }
        }
        // Collector Subordinates Panel
        if (collectorSection) {
            collectorSection.style.display = isCollectionManager ? 'block' : 'none';
            if (!isCollectionManager) {
                collectorSection.querySelectorAll('.collector-subordinate-checkbox').forEach(cb => { cb.checked = false; });
            }
        }
        filterAllSubordinates();
    };

    groupRadios.forEach(radio => radio.addEventListener('change', updateVisibility));
    branchSelect?.addEventListener('change', filterAllSubordinates);
    document.getElementById('salesEmployeeSearch')?.addEventListener('input', filterAllSubordinates);
    document.getElementById('collectorSearch')?.addEventListener('input', filterAllSubordinates);

    // Batch Buttons
    const setupBatchButtons = (selectBtnId, deselectBtnId, checkboxClass, gridId) => {
        document.getElementById(selectBtnId)?.addEventListener('click', () => {
            const grid = document.getElementById(gridId);
            if (!grid) return;
            grid.querySelectorAll(`.${checkboxClass}`).forEach(cb => {
                if (cb.closest('.subordinate-item')?.style.display !== 'none') {
                    cb.checked = true;
                }
            });
            updateCounts();
        });
        document.getElementById(deselectBtnId)?.addEventListener('click', () => {
            const grid = document.getElementById(gridId);
            if (!grid) return;
            grid.querySelectorAll(`.${checkboxClass}`).forEach(cb => {
                if (cb.closest('.subordinate-item')?.style.display !== 'none') {
                    cb.checked = false;
                }
            });
            updateCounts();
        });
    };

    setupBatchButtons('selectAllSalesBtn', 'deselectAllSalesBtn', 'sales-subordinate-checkbox', 'salesEmployeesGrid');
    setupBatchButtons('selectAllCollectorsBtn', 'deselectAllCollectorsBtn', 'collector-subordinate-checkbox', 'collectorsGrid');

    // Dynamic Cascading Governorate -> Subregion
    const userSubregionsByGov = @json($subregionsMap);
    const selectGovFirstUserText = @json(__('crm.select_governorate_first'));
    const selectSubregionUserText = @json(__('crm.select_subregion'));
    const govSelect = document.getElementById('collector_governorate_id');
    const subSelect = document.getElementById('collection_subregion_id');
    if (govSelect && subSelect) {
        const filterSubregions = (isInitial = false) => {
            const selectedGov = govSelect.value;
            const previousSub = subSelect.value;
            subSelect.innerHTML = '';

            if (!selectedGov || !userSubregionsByGov[selectedGov] || userSubregionsByGov[selectedGov].length === 0) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = selectGovFirstUserText;
                subSelect.appendChild(opt);
                subSelect.disabled = true;
                return;
            }

            subSelect.disabled = false;
            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = selectSubregionUserText;
            subSelect.appendChild(defaultOpt);

            let hasMatched = false;
            userSubregionsByGov[selectedGov].forEach(sub => {
                const isCurrent = previousSub && String(previousSub) === String(sub.id);
                if (sub.is_active || isCurrent) {
                    const opt = document.createElement('option');
                    opt.value = sub.id;
                    opt.textContent = sub.name_ar + (sub.name_en ? ' (' + sub.name_en + ')' : '') + (!sub.is_active ? ' ({{ __('crm.inactive') }})' : '');
                    if (isCurrent) {
                        opt.selected = true;
                        hasMatched = true;
                    }
                    subSelect.appendChild(opt);
                }
            });

            if (!hasMatched && !isInitial) {
                subSelect.value = '';
            }
        };
        govSelect.addEventListener('change', () => filterSubregions(false));
        if (govSelect.value) {
            filterSubregions(true);
        } else {
            subSelect.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = selectGovFirstUserText;
            subSelect.appendChild(opt);
            subSelect.disabled = true;
        }
    }
    document.querySelectorAll('.sales-subordinate-checkbox, .collector-subordinate-checkbox')
        .forEach(cb => cb.addEventListener('change', updateCounts));

    const nameInput = document.getElementById('name');
    const usernameInput = document.getElementById('username');
    if (nameInput && usernameInput) {
        const arabicMap = {
            'أ': 'a', 'إ': 'e', 'آ': 'a', 'ا': 'a', 'ب': 'b', 'ت': 't', 'ث': 'th',
            'ج': 'g', 'ح': 'h', 'خ': 'kh', 'د': 'd', 'ذ': 'z', 'ر': 'r', 'ز': 'z',
            'س': 's', 'ش': 'sh', 'ص': 's', 'ض': 'd', 'ط': 't', 'ظ': 'z', 'ع': 'a',
            'غ': 'gh', 'ف': 'f', 'ق': 'q', 'ك': 'k', 'ل': 'l', 'م': 'm', 'ن': 'n',
            'ه': 'h', 'و': 'w', 'ي': 'y', 'ى': 'a', 'ة': 'a', 'ء': 'a', 'ئ': 'e', 'ؤ': 'o'
        };

        const generateUserSlug = (text) => {
            let str = text.trim().toLowerCase();
            if (!str) return '';
            let converted = '';
            for (let char of str) {
                converted += arabicMap[char] || char;
            }
            return converted
                .replace(/[^a-z0-9_]+/g, '_')
                .replace(/^_+|_+$/g, '')
                .substring(0, 50);
        };

        let userHasCustomized = {{ $isEdit ? 'true' : 'false' }};
        usernameInput.addEventListener('input', () => {
            userHasCustomized = usernameInput.value.trim() !== '';
        });

        nameInput.addEventListener('input', () => {
            if (!userHasCustomized) {
                usernameInput.value = generateUserSlug(nameInput.value);
            }
        });
    }
    // Run on initial page load
    updateVisibility();
})();
</script>
