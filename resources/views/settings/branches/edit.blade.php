@extends('settings.layout')

@section('title', __('crm.edit_branch_title', ['name' => $branch->name_ar]))
@section('heading', __('crm.edit_branch_heading', ['name' => $branch->name_ar]))
@section('subheading', __('crm.edit_branch_subtitle'))

@section('content')
<div class="grid stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <span>{{ __('crm.branch_assigned_employees') }}</span>
        <b style="color:#2563eb">{{ number_format($branch->users_count ?? 0) }}</b>
    </div>
    <div class="stat-card">
        <span>{{ __('crm.branch_leads_and_donors') }}</span>
        <b style="color:#dc2637">{{ number_format($branch->leads_count ?? 0) }}</b>
    </div>
    <div class="stat-card">
        <span>{{ __('crm.branch_linked_campaigns') }}</span>
        <b style="color:#16a34a">{{ number_format($branch->campaigns_count ?? 0) }}</b>
    </div>
    <div class="stat-card">
        <span>{{ __('crm.creation_date') }}</span>
        <b style="font-size:16px;margin-top:14px">{{ $branch->created_at?->format('Y-m-d') ?? '—' }}</b>
    </div>
</div>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.edit_branch_data') }}</h2>
            <p>{{ __('crm.fields_with_star_required') }}</p>
        </div>
        <a class="btn soft" href="{{ route('v2.settings.branches.index') }}">
            <i class="bi bi-arrow-left rtl:rotate-180"></i> {{ __('crm.back_to_branches_list') }}
        </a>
    </div>

    <form method="POST" action="{{ route('v2.settings.branches.update', $branch) }}">
        @csrf
        @method('PATCH')

        <div class="form-grid">
            <div class="field">
                <label for="name_ar">{{ __('crm.branch_name_ar') }} <span style="color:var(--red)">*</span></label>
                <input id="name_ar" name="name_ar" required maxlength="150" value="{{ old('name_ar', $branch->name_ar) }}" placeholder="{{ __('crm.branch_name_example') }}">
            </div>

            <div class="field">
                <label for="name_en">{{ __('crm.branch_name_en_optional') }}</label>
                <input id="name_en" name="name_en" maxlength="150" dir="ltr" value="{{ old('name_en', $branch->name_en) }}" placeholder="e.g. Cairo Branch / Alexandria Branch">
            </div>

            <div class="field">
                <label for="code">{{ __('crm.branch_code_slug') }} <span style="color:var(--red)">*</span></label>
                <input id="code" name="code" required maxlength="50" dir="ltr" value="{{ old('code', $branch->code) }}" placeholder="cairo-main">
                <div class="hint">{{ __('crm.branch_code_hint') }}</div>
            </div>

            <div class="field">
                <label for="phone">{{ __('crm.branch_phone_optional') }}</label>
                <input id="phone" name="phone" maxlength="50" dir="ltr" value="{{ old('phone', $branch->phone) }}" placeholder="0223456789">
            </div>

            <div class="field full">
                <label for="address">{{ __('crm.branch_address_optional') }}</label>
                <textarea id="address" name="address" rows="2" maxlength="255" placeholder="{{ __('crm.branch_address_placeholder') }}">{{ old('address', $branch->address) }}</textarea>
            </div>

            <div class="field full">
                <label class="check-card" style="cursor:pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $branch->is_active ? '1' : '0') === '1' ? 'checked' : '' }}>
                    <span>
                        <strong>{{ __('crm.active_branch_label') }}</strong>
                        <small>{{ __('crm.active_branch_hint') }}</small>
                    </span>
                </label>
            </div>
        </div>

        <div class="actions" style="margin-top:24px">
            <button class="btn primary" type="submit">
                <i class="bi bi-check-lg"></i> {{ __('crm.save_changes') }}
            </button>
            <a class="btn" href="{{ route('v2.settings.branches.index') }}">
                {{ __('crm.cancel') }}
            </a>
        </div>
    </form>
</section>
@endsection
