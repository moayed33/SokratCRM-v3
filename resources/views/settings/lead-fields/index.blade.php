@extends('settings.layout')

@section('title', __('crm.lead_fields'))
@section('heading', __('crm.lead_fields'))
@section('subheading', __('crm.lead_fields_desc'))

@section('content')
<div class="grid stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <span>{{ __('crm.lf_stats_total') }}</span>
        <b>{{ number_format($stats['total']) }}</b>
    </div>
    <div class="stat-card">
        <span>{{ __('crm.lf_stats_active') }}</span>
        <b style="color:#16a34a">{{ number_format($stats['active']) }}</b>
    </div>
    <div class="stat-card">
        <span>{{ __('crm.lf_stats_custom') }}</span>
        <b style="color:#2563eb">{{ number_format($stats['custom']) }}</b>
    </div>
    <div class="stat-card">
        <span>{{ __('crm.lf_stats_filterable') }}</span>
        <b style="color:#d97706">{{ number_format($stats['filterable']) }}</b>
    </div>
</div>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.lf_list_title') }}</h2>
            <p>{{ __('crm.lf_list_desc') }}</p>
        </div>
        <a class="btn primary" href="{{ route('v2.settings.fields.create', ['entity' => $currentEntity]) }}">
            <i class="bi bi-plus-lg"></i> {{ __('crm.lf_new_field') }}
        </a>
    </div>

    @if (count($entities) > 1)
        <div class="settings-tabs" style="margin-bottom:14px">
            @foreach ($entities as $entityTab)
                <a class="{{ $currentEntity === $entityTab['key'] ? 'active' : '' }}"
                   href="{{ route('v2.settings.fields.index', ['entity' => $entityTab['key']]) }}">
                    <i class="bi {{ $entityTab['icon'] }}"></i>
                    {{ $entityTab['label'] }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="table-wrap" style="margin-top:6px">
        <table style="min-width:980px">
            <thead>
                <tr>
                    <th style="width:90px">{{ __('crm.lf_position') }}</th>
                    <th>{{ __('crm.lf_label') }}</th>
                    <th>{{ __('crm.lf_key') }}</th>
                    <th>{{ __('crm.lf_type') }}</th>
                    <th>{{ __('crm.lf_section') }}</th>
                    <th>{{ __('crm.lf_visibility') }}</th>
                    <th style="width:230px">{{ __('crm.actions_th') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($fields as $field)
                    <tr style="{{ $field->is_active ? '' : 'opacity:.55' }}">
                        <td>
                            <div class="actions">
                                @if (! $loop->first)
                                    <form method="POST" action="{{ route('v2.settings.fields.move', $field) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="direction" value="up">
                                        <button class="btn small soft" type="submit" title="{{ __('crm.lf_move_up') }}"><i class="bi bi-arrow-up"></i></button>
                                    </form>
                                @endif
                                @if (! $loop->last)
                                    <form method="POST" action="{{ route('v2.settings.fields.move', $field) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="direction" value="down">
                                        <button class="btn small soft" type="submit" title="{{ __('crm.lf_move_down') }}"><i class="bi bi-arrow-down"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                        <td>
                            <strong>{{ $field->label() }}</strong>
                            @if ($field->is_required)
                                <span style="color:#dc2637;font-weight:900">*</span>
                            @endif
                            @if ($field->is_system)
                                <span class="badge system">{{ __('crm.lf_system_badge') }}</span>
                            @endif
                            @if ($field->is_locked)
                                <i class="bi bi-lock-fill" title="{{ __('crm.lf_locked_note') }}" style="color:#8a6100"></i>
                            @endif
                        </td>
                        <td><code dir="ltr">{{ $field->key }}</code></td>
                        <td><span class="badge">{{ __('crm.lf_type_'.$field->type) }}</span></td>
                        <td><span class="hint">{{ __('crm.section_'.$field->section) }}</span></td>
                        <td>
                            <div class="actions" style="gap:4px">
                                @if ($field->show_in_create)<span class="badge" title="{{ __('crm.lf_show_in_create') }}">{{ __('crm.lf_short_create') }}</span>@endif
                                @if ($field->show_in_edit)<span class="badge" title="{{ __('crm.lf_show_in_edit') }}">{{ __('crm.lf_short_edit') }}</span>@endif
                                @if ($field->show_in_filter)<span class="badge active" title="{{ __('crm.lf_show_in_filter') }}">{{ __('crm.lf_short_filter') }}</span>@endif
                                @if ($field->show_in_table)<span class="badge" title="{{ __('crm.lf_show_in_table') }}">{{ __('crm.lf_short_table') }}</span>@endif
                                @if ($field->show_in_export)<span class="badge" title="{{ __('crm.lf_show_in_export') }}">{{ __('crm.lf_short_export') }}</span>@endif
                            </div>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="btn small" href="{{ route('v2.settings.fields.edit', $field) }}">
                                    <i class="bi bi-pencil"></i> {{ __('crm.edit') }}
                                </a>
                                @unless ($field->is_locked)
                                    <form method="POST" action="{{ route('v2.settings.fields.toggle', $field) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn small {{ $field->is_active ? 'danger' : 'primary' }}" type="submit">
                                            {{ $field->is_active ? __('crm.deactivate') : __('crm.activate') }}
                                        </button>
                                    </form>
                                @endunless
                                @unless ($field->is_system)
                                    <form method="POST" action="{{ route('v2.settings.fields.destroy', $field) }}" onsubmit="return confirm(@json(__('crm.lf_confirm_delete')))">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn small danger" type="submit" title="{{ __('crm.delete') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="hint" style="margin-top:14px">
        <i class="bi bi-info-circle"></i> {{ __('crm.lf_index_footnote') }}
    </p>
</section>
@endsection
