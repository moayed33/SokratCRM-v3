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

                        $statusBadge = $isOnline ? ' 🟢 [متصل]' : '';
                        $displayName = $extName ? "{$extNum} - {$extName}{$statusBadge}" : $extNum;
                    @endphp
                    <option value="{{ $extNum }}" @selected($currentExt === $extNum)>
                        {{ $displayName }}
                    </option>
                @endforeach
            @endif
            @if($currentExt !== '' && !$foundCurrent)
                <option value="{{ $currentExt }}" selected>
                    {{ $currentExt }} (امتداد مخصص)
                </option>
            @endif
        </select>
        <div class="hint">{{ __('crm.extension_hint') }}</div>
    </div>
    @endif
    @unless ($isEdit)
        <div class="field">
            <label for="password">كلمة المرور</label>
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
</div>

<div class="actions" style="margin-top:20px">
    <button class="btn primary">{{ $isEdit ? 'حفظ التعديلات' : 'إنشاء المستخدم' }}</button>
    <a class="btn" href="{{ route('v2.settings.users.index') }}">{{ __('crm.cancel') }}</a>
</div>
