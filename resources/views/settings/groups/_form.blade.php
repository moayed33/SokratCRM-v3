@php($isEdit = isset($group))
<div class="form-grid">
    <div class="field">
        <label for="name">{{ __('crm.group_name') }}</label>
        <input id="name" name="name" required maxlength="100" value="{{ old('name', $group->name ?? '') }}">
    </div>
    <div class="field">
        <label for="code">{{ __('crm.group_code') }}</label>
        <input
            id="code"
            name="code"
            required
            maxlength="100"
            dir="ltr"
            value="{{ old('code', $group->code ?? '') }}"
            @readonly($isEdit && $group->is_system)
        >
        <div class="hint">{{ __('crm.group_code_rules') }}</div>
    </div>
    <div class="field full">
        <label for="description">{{ __('crm.description') }}</label>
        <textarea id="description" name="description" maxlength="1000">{{ old('description', $group->description ?? '') }}</textarea>
    </div>
</div>
<div class="actions" style="margin-top:20px">
    <button class="btn primary">{{ $isEdit ? __('crm.save_changes') : __('crm.create_group') }}</button>
    <a class="btn" href="{{ route('v2.settings.groups.index') }}">{{ __('crm.cancel') }}</a>
</div>
