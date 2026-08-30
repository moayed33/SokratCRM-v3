@forelse ($extensions as $ext)
    @php
        $extState = $ext['in_call'] ? 'incall' : ($ext['online'] ? 'online' : 'offline');
        $isRinging = $ext['in_call'] && str_contains(strtolower((string) ($ext['call_state'] ?? '')), 'ring');
        $durationSeconds = max(0, (int) ($ext['call_duration_seconds'] ?? 0));
        $durationText = sprintf(
            '%02d:%02d:%02d',
            intdiv($durationSeconds, 3600),
            intdiv($durationSeconds % 3600, 60),
            $durationSeconds % 60,
        );
    @endphp
    <div
        class="ext-card"
        data-extension="{{ $ext['extension'] }}"
        data-state="{{ $extState }}"
        data-search="{{ mb_strtolower($ext['name'].' '.$ext['extension']) }}"
    >
        <div class="ext-header">
            <div class="ext-user-info">
                <div class="ext-avatar">{{ mb_substr($ext['name'], 0, 2) }}</div>
                <div class="ext-meta">
                    <span class="ext-name" title="{{ $ext['name'] }}">{{ $ext['name'] }}</span>
                    <span class="ext-number">{{ app()->getLocale() === 'en' ? 'Extension:' : 'التحويلة :' }} {{ $ext['extension'] }}</span>
                </div>
            </div>
            <span class="status-badge {{ $extState }}">
                <span class="status-dot"></span>
                @if ($isRinging)
                    {{ app()->getLocale() === 'en' ? 'Ringing' : 'يرن الآن' }}
                @elseif ($ext['in_call'])
                    {{ app()->getLocale() === 'en' ? 'In Call' : 'في مكالمة' }}
                @elseif ($ext['online'])
                    {{ app()->getLocale() === 'en' ? 'Online' : 'متصل' }}
                @else
                    {{ app()->getLocale() === 'en' ? 'Offline' : 'غير متصل' }}
                @endif
            </span>
        </div>

        <div class="ext-details">
            <div class="detail-row">
                <span>{{ app()->getLocale() === 'en' ? 'Line Status:' : 'حالة الخط:' }}</span>
                <strong>
                    @if ($isRinging)
                        {{ app()->getLocale() === 'en' ? 'Ringing' : 'الخط يرن' }}
                    @elseif ($ext['in_call'])
                        {{ app()->getLocale() === 'en' ? 'Active Call' : 'مكالمة جارية' }}
                    @elseif ($ext['online'])
                        {{ app()->getLocale() === 'en' ? 'Ready for Calls' : 'جاهز للمكالمات' }}
                    @else
                        {{ app()->getLocale() === 'en' ? 'Unregistered' : 'الخط غير مسجل' }}
                    @endif
                </strong>
            </div>

            @if (! empty($ext['crm_user_name']))
                <div class="detail-row">
                    <span>{{ app()->getLocale() === 'en' ? 'Assigned Employee:' : 'الموظف المسؤول:' }}</span>
                    <strong>{{ $ext['crm_user_name'] }}</strong>
                </div>
            @endif

            @if ($ext['in_call'])
                @if (! empty($ext['call_partner']))
                    <div class="detail-row">
                        <span>{{ app()->getLocale() === 'en' ? 'Other Party:' : 'الطرف الآخر:' }}</span>
                        <strong dir="ltr">{{ $ext['call_partner'] }}</strong>
                    </div>
                @endif
                <div class="detail-row">
                    <span>{{ app()->getLocale() === 'en' ? 'Call Duration:' : 'مدة المكالمة:' }}</span>
                    <strong dir="ltr" class="call-duration">{{ $durationText }}</strong>
                </div>
            @endif
        </div>
    </div>
@empty
    <div class="error-card" style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; background: var(--card); border: 1px solid var(--line); border-radius: 16px;">
        <i class="bi bi-telephone-slash" style="font-size: 42px; color: var(--muted); margin-bottom: 12px; display: inline-block;"></i>
        <h3 style="margin: 0 0 8px; color: var(--dark);">{{ app()->getLocale() === 'en' ? 'No Extensions Found' : 'لم يتم العثور على تحويلات' }}</h3>
        <p style="color: var(--muted); margin: 0;">{{ app()->getLocale() === 'en' ? 'No extensions were returned by the PBX server.' : 'لم يُرجع سيرفر السنترال أي تحويلات.' }}</p>
    </div>
@endforelse
