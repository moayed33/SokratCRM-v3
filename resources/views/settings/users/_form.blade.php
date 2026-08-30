@php
    $isEdit = isset($managedUser);
    $selectedGroupIds = collect(old(
        'group_ids',
        $isEdit ? $managedUser->groups->pluck('id')->all() : [],
    ))->map(static fn ($id) => (int) $id);
@endphp

<div class="form-grid">
    <div class="field">
        <label for="name">{{ __('crm.display_name') }}</label>
        <input id="name" name="name" required maxlength="150" value="{{ old('name', $managedUser->name ?? '') }}">
    </div>
    <div class="field">
        <label for="username">{{ __('crm.username_label') }}</label>
        <input id="username" name="username" required maxlength="100" dir="ltr" value="{{ old('username', $managedUser->username ?? '') }}">
        <div class="hint">{{ __('crm.username_rules') }}</div>
    </div>
    <div class="field">
        <label for="email">{{ __('crm.email_optional') }}</label>
        <input id="email" name="email" type="email" dir="ltr" value="{{ old('email', $managedUser->email ?? '') }}">
    </div>
    <div class="field">
        <label for="branch_id">{{ __('crm.branch') }}</label>
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
    <div class="field">
        <label for="manager_id">{{ __('crm.direct_manager_or_team_leader') }}</label>
        <select id="manager_id" name="manager_id">
            <option value="">— {{ __('crm.no_direct_manager') }} —</option>
            @if (!empty($managers))
                @foreach ($managers as $mgr)
                    <option value="{{ $mgr->id }}" @selected((string) old('manager_id', $managedUser->manager_id ?? '') === (string) $mgr->id)>
                        {{ $mgr->name }} ({{ $mgr->username }})
                    </option>
                @endforeach
            @endif
        </select>
        <div class="hint">{{ __('crm.direct_manager_hint') }}</div>
    </div>
    <div class="field">
        <label for="collection_zone">{{ __('crm.collection_zone') }}</label>
        <input id="collection_zone" name="collection_zone" maxlength="255" placeholder="{{ __('crm.collection_zone_placeholder') }}" value="{{ old('collection_zone', $managedUser->collection_zone ?? '') }}">
        <div class="hint">{{ __('crm.collection_zone_hint') }}</div>
    </div>
    @if(!empty($isVoipConnected))
    <div class="field">
        <label for="voip_extension">{{ __('crm.extension_label') }}</label>
        @php
            $currentExt = (string) old('voip_extension', $managedUser->voip_extension ?? '');
            $foundCurrent = false;
        @endphp
        <select id="voip_extension" name="voip_extension" style="width:100%;padding:10px;border-radius:10px;border:1px solid var(--line)">
            <option value="">{{ __('crm.no_extension_option') }}</option>
            @if(!empty($voipExtensions))
                @foreach($voipExtensions as $ext)
                    @php
                        $extNum = (string) (is_array($ext) ? ($ext['extension'] ?? '') : (is_object($ext) ? ($ext->extension ?? '') : $ext));
                        $extName = is_array($ext) ? ($ext['name'] ?? '') : (is_object($ext) ? ($ext->name ?? '') : '');
                        $isOnline = is_array($ext) ? (!empty($ext['online'])) : (is_object($ext) ? (!empty($ext->online)) : false);

                        if ($currentExt !== '' && $currentExt === $extNum) {
                            $foundCurrent = true;
                        }

                        $statusBadge = $isOnline ? ' 🟢 ' . __('crm.online_badge') : '';
                        $displayName = $extName ? "{$extNum} - {$extName}{$statusBadge}" : $extNum;
                    @endphp
                    <option value="{{ $extNum }}" @selected($currentExt === $extNum)>
                        {{ $displayName }}
                    </option>
                @endforeach
            @endif
            @if($currentExt !== '' && !$foundCurrent)
                <option value="{{ $currentExt }}" selected>
                    {{ $currentExt }} {{ __('crm.custom_extension_label') }}
                </option>
            @endif
        </select>
        <div class="hint">{{ __('crm.extension_hint') }}</div>
    </div>
    @endif
    @unless ($isEdit)
        <div class="field">
            <label for="password">{{ __('crm.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password">
            <div class="hint">{{ __('crm.password_minimum') }}</div>
        </div>
        <div class="field">
            <label for="password_confirmation">{{ __('crm.confirm_password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        </div>
    @endunless

    <div class="field full">
        <label>{{ __('crm.groups') }}</label>
        <div class="checkbox-grid">
            @foreach ($groups as $group)
                <label class="check-card">
                    <input
                        type="checkbox"
                        name="group_ids[]"
                        class="group-checkbox"
                        data-group-code="{{ strtolower($group->code) }}"
                        value="{{ $group->id }}"
                        @checked($selectedGroupIds->contains((int) $group->id))
                    >
                    <span>
                        <strong>{{ $group->name }}</strong>
                        <small>{{ $group->description ?: $group->code }}</small>
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    <input type="hidden" name="subordinates_section_rendered" value="1">

    {{-- SALES TEAM SUBORDINATES (When Team Leader role is assigned) --}}
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

    {{-- SUPERVISED COLLECTORS (When Collection Manager role is assigned) --}}
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
                                @if($col->collection_zone)
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

<div class="actions" style="margin-top:20px">
    <button class="btn primary">{{ $isEdit ? __('crm.save_changes') : __('crm.create_user') }}</button>
    <a class="btn" href="{{ route('v2.settings.users.index') }}">{{ __('crm.cancel') }}</a>
</div>

<style>
html.dark-mode .subordinates-panel {
    background: var(--bg-card, rgba(24, 24, 27, 0.75)) !important;
    border-color: var(--line, rgba(255, 255, 255, 0.08)) !important;
}
html.dark-mode .subordinate-search {
    background: var(--bg-input, #27272a) !important;
    border-color: var(--line, rgba(255, 255, 255, 0.12)) !important;
    color: var(--text-primary, #f4f4f5) !important;
}
html.dark-mode .subordinates-panel h3 {
    color: var(--text-primary, #f4f4f5) !important;
}
</style>

<script>
(() => {
    const leaderCodes = ['manager', 'branch-admin', 'team-leader', 'team_leader', 'sales-manager', 'sales_manager', 'sales-supervisor', 'sales_supervisor', 'leader'];
    const collectorManagerCodes = ['manager', 'branch-admin', 'collection-manager', 'collection_manager', 'collections-manager', 'collections_manager'];

    const salesSection = document.getElementById('salesSubordinatesSection');
    const collectorSection = document.getElementById('collectorSubordinatesSection');
    const groupCheckboxes = Array.from(document.querySelectorAll('.group-checkbox'));
    const branchSelect = document.getElementById('branch_id');

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
            const matchesBranch = itemBranch === selectedBranch;
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

    const filterAllSubordinates = () => {
        filterGridByBranchAndSearch('salesEmployeesGrid', 'salesEmployeeSearch', 'salesNoBranchPrompt', 'salesNoBranchMatches');
        filterGridByBranchAndSearch('collectorsGrid', 'collectorSearch', 'collectorNoBranchPrompt', 'collectorNoBranchMatches');
        updateCounts();
    };

    const updateVisibility = () => {
        const checkedCodes = groupCheckboxes
            .filter(cb => cb.checked)
            .map(cb => (cb.dataset.groupCode || '').toLowerCase().trim());

        const hasLeader = checkedCodes.some(code => leaderCodes.includes(code));
        const hasCollectorMgr = checkedCodes.some(code => collectorManagerCodes.includes(code));

        if (salesSection) {
            salesSection.style.display = hasLeader ? 'block' : 'none';
        }
        if (collectorSection) {
            collectorSection.style.display = hasCollectorMgr ? 'block' : 'none';
        }
        filterAllSubordinates();
    };

    groupCheckboxes.forEach(cb => cb.addEventListener('change', updateVisibility));
    branchSelect?.addEventListener('change', filterAllSubordinates);
    document.getElementById('salesEmployeeSearch')?.addEventListener('input', filterAllSubordinates);
    document.getElementById('collectorSearch')?.addEventListener('input', filterAllSubordinates);

    // Select/Deselect All buttons (only operates on visible branch-filtered items)
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

    document.querySelectorAll('.sales-subordinate-checkbox, .collector-subordinate-checkbox')
        .forEach(cb => cb.addEventListener('change', updateCounts));

    // Run on init
    updateVisibility();
})();
</script>
