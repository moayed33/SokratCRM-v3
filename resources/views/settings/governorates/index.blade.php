@extends('settings.layout')

@section('title', __('crm.geography_and_zones'))
@section('heading', __('crm.geography_and_zones'))
@section('subheading', __('crm.governorates_subtitle'))

@push('head')
<style>
    .geo-grid {
        display: grid;
        grid-template-columns: minmax(320px, 1fr) minmax(380px, 1.2fr);
        gap: 20px;
        align-items: start;
    }
    .gov-card-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--card);
        margin-bottom: 8px;
        transition: all 0.15s ease;
        text-decoration: none;
        color: var(--ink);
    }
    .gov-card-item:hover {
        border-color: var(--red);
        background: rgba(220, 38, 55, 0.02);
    }
    .gov-card-item.selected {
        border-color: var(--red);
        background: #fff5f6;
        box-shadow: 0 0 0 2px rgba(220, 38, 55, 0.15);
    }
    html.dark-mode .gov-card-item.selected {
        background: rgba(220, 38, 55, 0.12);
    }
    .gov-meta {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 4px;
        font-size: 11px;
    }
    .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 999;
        display: none;
        place-items: center;
        padding: 20px;
    }
    .modal-backdrop.open {
        display: grid;
    }
    .modal-box {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 16px;
        padding: 24px;
        max-width: 500px;
        width: 100%;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
    @media(max-width: 900px) {
        .geo-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="grid stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <span>{{ __('crm.governorates') }}</span>
        <b>{{ number_format($governorates->count()) }}</b>
    </div>
    <div class="stat-card">
        <span>{{ __('crm.subregions') }}</span>
        <b style="color:#0284c7">{{ number_format($governorates->sum('subregions_count')) }}</b>
    </div>
    <div class="stat-card">
        <span>{{ __('crm.assigned_collectors') }}</span>
        <b style="color:#16a34a">{{ number_format(\App\Models\User::query()->whereNotNull('collection_subregion_id')->count()) }}</b>
    </div>
    <div class="stat-card">
        <span>{{ __('crm.leads_and_donors_count') }}</span>
        <b style="color:#dc2637">{{ number_format($governorates->sum('leads_count')) }}</b>
    </div>
</div>

<div class="geo-grid">
    {{-- Left Column: Governorates List --}}
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2><i class="bi bi-map"></i> {{ __('crm.governorates') }}</h2>
                <p>{{ __('crm.governorates_subtitle') }}</p>
            </div>
            <button class="btn primary small" type="button" onclick="openModal('newGovModal')">
                <i class="bi bi-plus-lg"></i> {{ __('crm.new_governorate') }}
            </button>
        </div>

        <form class="toolbar" method="GET" action="{{ route('v2.settings.governorates.index') }}" style="margin-bottom:14px">
            <div class="search">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('crm.search') }}...">
                <button class="btn soft small">{{ __('crm.search') }}</button>
                @if(request('q'))
                    <a class="btn small" href="{{ route('v2.settings.governorates.index') }}">{{ __('crm.cancel') }}</a>
                @endif
            </div>
        </form>

        <div style="max-height: 650px; overflow-y: auto; padding-inline-end: 4px;">
            @forelse($governorates as $gov)
                <div class="gov-card-item {{ $selectedGovernorate && $selectedGovernorate->id === $gov->id ? 'selected' : '' }}">
                    <a href="{{ route('v2.settings.governorates.index', ['gov_id' => $gov->id, 'q' => request('q')]) }}" style="flex:1; text-decoration:none; color:inherit;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <strong>{{ $gov->name_ar }}</strong>
                            @if($gov->name_en)
                                <small class="hint" dir="ltr">({{ $gov->name_en }})</small>
                            @endif
                            <span class="badge {{ $gov->is_active ? 'active' : 'inactive' }}" style="font-size:10px; padding:2px 6px;">
                                {{ $gov->is_active ? __('crm.active') : __('crm.inactive') }}
                            </span>
                        </div>
                        <div class="gov-meta">
                            <span class="badge" style="font-size:10px;"><i class="bi bi-geo-alt"></i> {{ $gov->subregions_count }} {{ __('crm.subregions') }}</span>
                            <span class="badge" style="font-size:10px;"><i class="bi bi-person-heart"></i> {{ $gov->leads_count }} {{ __('crm.donor') }}</span>
                            <span class="badge" style="font-size:10px;"><i class="bi bi-cash-coin"></i> {{ $gov->collection_cases_count }} {{ __('crm.collections') }}</span>
                        </div>
                    </a>
                    <div class="actions" style="gap:4px;">
                        <button class="btn small soft" type="button" onclick="editGov({{ json_encode($gov) }})" title="{{ __('crm.edit') }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('v2.settings.governorates.toggle', $gov) }}">
                            @csrf @method('PATCH')
                            <button class="btn small {{ $gov->is_active ? 'danger' : 'soft' }}" type="submit" title="{{ $gov->is_active ? __('crm.deactivate') : __('crm.activate') }}">
                                <i class="bi {{ $gov->is_active ? 'bi-pause' : 'bi-play' }}"></i>
                            </button>
                        </form>
                        @if($gov->subregions_count === 0 && $gov->leads_count === 0 && $gov->collection_cases_count === 0)
                            <form method="POST" action="{{ route('v2.settings.governorates.destroy', $gov) }}" onsubmit="return confirm('{{ __('crm.delete') }}?')">
                                @csrf @method('DELETE')
                                <button class="btn small danger" type="submit" title="{{ __('crm.delete') }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="hint" style="text-align:center; padding:30px;">{{ __('crm.no_collection_cases') }}</p>
            @endforelse
        </div>
    </section>

    {{-- Right Column: Selected Governorate's Subregions --}}
    <section class="panel">
        @if($selectedGovernorate)
            <div class="panel-head">
                <div>
                    <h2>
                        <i class="bi bi-geo"></i>
                        {{ __('crm.subregions') }}: <span style="color:var(--red)">{{ $selectedGovernorate->name_ar }}</span>
                    </h2>
                    <p>{{ __('crm.assigned_collector_subregion_hint') }}</p>
                </div>
                <button class="btn primary small" type="button" onclick="openModal('newSubModal')">
                    <i class="bi bi-plus-lg"></i> {{ __('crm.new_subregion') }}
                </button>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('crm.subregion') }}</th>
                            <th>{{ __('crm.code') }}</th>
                            <th>{{ __('crm.assigned_collector') }}</th>
                            <th>{{ __('crm.status_th') }}</th>
                            <th>{{ __('crm.actions_th') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($selectedGovernorate->subregions as $sub)
                            <tr>
                                <td>
                                    <strong>{{ $sub->name_ar }}</strong>
                                    @if($sub->name_en)
                                        <div class="hint" dir="ltr" style="font-size:11px;">{{ $sub->name_en }}</div>
                                    @endif
                                </td>
                                <td><code>{{ $sub->code }}</code></td>
                                <td>
                                    <span class="badge {{ $sub->collectors_count > 0 ? 'active' : '' }}" style="font-size:11px;">
                                        <i class="bi bi-person-badge"></i> {{ $sub->collectors_count }} {{ __('crm.collector') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $sub->is_active ? 'active' : 'inactive' }}" style="font-size:11px;">
                                        {{ $sub->is_active ? __('crm.active') : __('crm.inactive') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="actions" style="gap:4px;">
                                        <button class="btn small soft" type="button" onclick="editSub({{ json_encode($sub) }})" title="{{ __('crm.edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('v2.settings.subregions.toggle', $sub) }}">
                                            @csrf @method('PATCH')
                                            <button class="btn small {{ $sub->is_active ? 'danger' : 'soft' }}" type="submit" title="{{ $sub->is_active ? __('crm.deactivate') : __('crm.activate') }}">
                                                <i class="bi {{ $sub->is_active ? 'bi-pause' : 'bi-play' }}"></i>
                                            </button>
                                        </form>
                                        @if($sub->collectors_count === 0 && $sub->leads_count === 0 && $sub->collection_cases_count === 0)
                                            <form method="POST" action="{{ route('v2.settings.subregions.destroy', $sub) }}" onsubmit="return confirm('{{ __('crm.delete') }}?')">
                                                @csrf @method('DELETE')
                                                <button class="btn small danger" type="submit" title="{{ __('crm.delete') }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center; padding:30px; color:var(--muted)">
                                    <i class="bi bi-geo-alt" style="font-size:28px; display:block; opacity:0.4; margin-bottom:6px;"></i>
                                    {{ __('crm.no_matching_employees_found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p class="hint" style="text-align:center; padding:40px;">{{ __('crm.select_governorate') }}</p>
        @endif
    </section>
</div>

{{-- MODAL: Create Governorate --}}
<div class="modal-backdrop" id="newGovModal">
    <div class="modal-box">
        <h3 style="margin-top:0; margin-bottom:16px;">{{ __('crm.new_governorate') }}</h3>
        <form method="POST" action="{{ route('v2.settings.governorates.store') }}">
            @csrf
            <div style="margin-bottom:12px;">
                <label for="gov_name_ar">{{ __('crm.governorate_name_ar') }} <span style="color:var(--red)">*</span></label>
                <input id="gov_name_ar" name="name_ar" required placeholder="مثال: الشرقية">
            </div>
            <div style="margin-bottom:12px;">
                <label for="gov_name_en">{{ __('crm.governorate_name_en') }}</label>
                <input id="gov_name_en" name="name_en" placeholder="e.g. Sharkia">
            </div>
            <div style="margin-bottom:18px;">
                <label for="gov_code">{{ __('crm.code') }} (slug)</label>
                <input id="gov_code" name="code" placeholder="e.g. sharkia">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button class="btn soft" type="button" onclick="closeModal('newGovModal')">{{ __('crm.cancel') }}</button>
                <button class="btn primary" type="submit">{{ __('crm.save') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: Edit Governorate --}}
<div class="modal-backdrop" id="editGovModal">
    <div class="modal-box">
        <h3 style="margin-top:0; margin-bottom:16px;">{{ __('crm.edit_governorate') }}</h3>
        <form id="editGovForm" method="POST" action="">
            @csrf @method('PATCH')
            <div style="margin-bottom:12px;">
                <label for="edit_gov_name_ar">{{ __('crm.governorate_name_ar') }} <span style="color:var(--red)">*</span></label>
                <input id="edit_gov_name_ar" name="name_ar" required>
            </div>
            <div style="margin-bottom:12px;">
                <label for="edit_gov_name_en">{{ __('crm.governorate_name_en') }}</label>
                <input id="edit_gov_name_en" name="name_en">
            </div>
            <div style="margin-bottom:18px;">
                <label for="edit_gov_code">{{ __('crm.code') }} <span style="color:var(--red)">*</span></label>
                <input id="edit_gov_code" name="code" required>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button class="btn soft" type="button" onclick="closeModal('editGovModal')">{{ __('crm.cancel') }}</button>
                <button class="btn primary" type="submit">{{ __('crm.save') }}</button>
            </div>
        </form>
    </div>
</div>

@if($selectedGovernorate)
{{-- MODAL: Create Subregion --}}
<div class="modal-backdrop" id="newSubModal">
    <div class="modal-box">
        <h3 style="margin-top:0; margin-bottom:16px;">
            {{ __('crm.new_subregion') }} — {{ $selectedGovernorate->name_ar }}
        </h3>
        <form method="POST" action="{{ route('v2.settings.governorates.subregions.store', $selectedGovernorate) }}">
            @csrf
            <div style="margin-bottom:12px;">
                <label for="sub_name_ar">{{ __('crm.subregion_name_ar') }} <span style="color:var(--red)">*</span></label>
                <input id="sub_name_ar" name="name_ar" required placeholder="مثال: مدينة نصر">
            </div>
            <div style="margin-bottom:12px;">
                <label for="sub_name_en">{{ __('crm.subregion_name_en') }}</label>
                <input id="sub_name_en" name="name_en" placeholder="e.g. Nasr City">
            </div>
            <div style="margin-bottom:18px;">
                <label for="sub_code">{{ __('crm.code') }} (slug)</label>
                <input id="sub_code" name="code" placeholder="e.g. nasr_city">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button class="btn soft" type="button" onclick="closeModal('newSubModal')">{{ __('crm.cancel') }}</button>
                <button class="btn primary" type="submit">{{ __('crm.save') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: Edit Subregion --}}
<div class="modal-backdrop" id="editSubModal">
    <div class="modal-box">
        <h3 style="margin-top:0; margin-bottom:16px;">{{ __('crm.edit_subregion') }}</h3>
        <form id="editSubForm" method="POST" action="">
            @csrf @method('PATCH')
            <div style="margin-bottom:12px;">
                <label for="edit_sub_name_ar">{{ __('crm.subregion_name_ar') }} <span style="color:var(--red)">*</span></label>
                <input id="edit_sub_name_ar" name="name_ar" required>
            </div>
            <div style="margin-bottom:12px;">
                <label for="edit_sub_name_en">{{ __('crm.subregion_name_en') }}</label>
                <input id="edit_sub_name_en" name="name_en">
            </div>
            <div style="margin-bottom:18px;">
                <label for="edit_sub_code">{{ __('crm.code') }} <span style="color:var(--red)">*</span></label>
                <input id="edit_sub_code" name="code" required>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button class="btn soft" type="button" onclick="closeModal('editSubModal')">{{ __('crm.cancel') }}</button>
                <button class="btn primary" type="submit">{{ __('crm.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
function openModal(id) {
    document.getElementById(id)?.classList.add('open');
}
function closeModal(id) {
    document.getElementById(id)?.classList.remove('open');
}
function editGov(gov) {
    const form = document.getElementById('editGovForm');
    if (!form) return;
    form.action = "{{ url('settings/governorates') }}/" + gov.id;
    document.getElementById('edit_gov_name_ar').value = gov.name_ar || '';
    document.getElementById('edit_gov_name_en').value = gov.name_en || '';
    document.getElementById('edit_gov_code').value = gov.code || '';
    openModal('editGovModal');
}
function editSub(sub) {
    const form = document.getElementById('editSubForm');
    if (!form) return;
    form.action = "{{ url('settings/subregions') }}/" + sub.id;
    document.getElementById('edit_sub_name_ar').value = sub.name_ar || '';
    document.getElementById('edit_sub_name_en').value = sub.name_en || '';
    document.getElementById('edit_sub_code').value = sub.code || '';
    openModal('editSubModal');
}
</script>
@endpush
@endsection
