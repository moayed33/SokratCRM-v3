@php($isEdit = isset($group))
<div class="form-grid">
    <div class="field">
        <label for="name">{{ __('crm.group_name') }} <span style="color:var(--red)">*</span></label>
        <input id="name" name="name" required maxlength="100" placeholder="مثال: مسؤولو التحصيل أو VIP Donors Care" value="{{ old('name', $group->name ?? '') }}" autofocus>
        <div class="hint">{{ __('crm.group_name') }} الذي سيظهر في لوحة التحكم وتوزيع الصلاحيات.</div>
    </div>

    <div class="field">
        @if ($isEdit)
            <label>{{ __('crm.group_code') }} <span style="font-size:11px; color:var(--muted); font-weight:normal;"><i class="bi bi-lock-fill"></i> ({{ __('crm.group_code_permanent') }})</span></label>
            <div style="display:flex; align-items:center; gap:8px; padding:11px 14px; border-radius:10px; border:1px solid var(--line); background:var(--bg); color:var(--ink); font-family:monospace; font-weight:700; font-size:14px;">
                <i class="bi bi-key-fill" style="color:var(--red)"></i>
                <span dir="ltr">{{ $group->code }}</span>
            </div>
            <div class="hint">{{ $group->is_system ? __('crm.system_group_code_protected') : __('crm.group_code_permanent') }}</div>
        @else
            <label for="group_code_preview">{{ __('crm.group_code') }} <span style="font-size:11px; color:var(--muted); font-weight:normal;">({{ __('crm.automatic_preview') }})</span></label>
            <div style="display:flex; align-items:center; gap:8px; padding:11px 14px; border-radius:10px; border:1px solid var(--line); background:var(--bg); color:var(--ink); font-family:monospace; font-weight:700; font-size:14px;">
                <i class="bi bi-key" style="color:var(--red)"></i>
                <span id="group_code_preview_text" dir="ltr">{{ old('code', '—') }}</span>
            </div>
            <div class="hint">{{ __('crm.group_code_auto_hint') }}</div>
        @endif
    </div>

    <div class="field full">
        <label for="description">{{ __('crm.description') }}</label>
        <textarea id="description" name="description" rows="3" maxlength="1000" placeholder="اكتب وصفاً مختصراً لمهام ومسؤوليات هذا الدور...">{{ old('description', $group->description ?? '') }}</textarea>
    </div>
</div>

<div class="actions" style="margin-top:20px">
    <button class="btn primary" type="submit">{{ $isEdit ? __('crm.save_changes') : __('crm.create_group') }}</button>
    <a class="btn" href="{{ route('v2.settings.users.index', ['tab' => 'groups']) }}">{{ __('crm.cancel') }}</a>
</div>

@unless ($isEdit)
<script>
(() => {
    const nameInput = document.getElementById('name');
    const previewEl = document.getElementById('group_code_preview_text');
    if (!nameInput || !previewEl) return;

    const arabicMap = {
        'أ': 'a', 'إ': 'e', 'آ': 'a', 'ا': 'a', 'ب': 'b', 'ت': 't', 'ث': 'th',
        'ج': 'g', 'ح': 'h', 'خ': 'kh', 'د': 'd', 'ذ': 'z', 'ر': 'r', 'ز': 'z',
        'س': 's', 'ش': 'sh', 'ص': 's', 'ض': 'd', 'ط': 't', 'ظ': 'z', 'ع': 'a',
        'غ': 'gh', 'ف': 'f', 'ق': 'q', 'ك': 'k', 'ل': 'l', 'م': 'm', 'ن': 'n',
        'ه': 'h', 'و': 'w', 'ي': 'y', 'ى': 'a', 'ة': 'a', 'ء': 'a', 'ئ': 'e', 'ؤ': 'o'
    };

    const generateSlug = (text) => {
        let str = text.trim().toLowerCase();
        if (!str) return '—';
        let converted = '';
        for (let char of str) {
            converted += arabicMap[char] || char;
        }
        let slug = converted
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .substring(0, 50);
        return slug || '—';
    };

    nameInput.addEventListener('input', () => {
        previewEl.textContent = generateSlug(nameInput.value);
    });

    if (nameInput.value.trim()) {
        previewEl.textContent = generateSlug(nameInput.value);
    }
})();
</script>
@endunless
