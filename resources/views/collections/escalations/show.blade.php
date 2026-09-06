@extends('leads.transfer-layout')

@section('title', __('crm.donor_unreachable_title').' #'.$collectionCase->id)
@section('page-title', __('crm.donor_unreachable_title'))
@section('page-description', __('crm.donor_unreachable_desc'))

@section('top-actions')
 <a class="btn soft" href="{{ route('v2.collections.show', $collectionCase) }}"><i class="bi bi-arrow-right"></i>{{ __('crm.back_to_collections') }}</a>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
 .esc-hero { padding:22px; margin-bottom:20px; border-radius:14px; background:linear-gradient(120deg,#991b1b,#dc2626); color:#fff; }
 .esc-hero h2 { margin:0 0 8px; font-size:22px; }
 .esc-hero p { margin:0; color:#fecdd3; }
 .esc-card { padding:20px; margin-bottom:18px; border:1px solid var(--line, #e2e8f0); border-radius:14px; background:var(--card, #fff); }
 .esc-card h3 { margin:0 0 16px; font-size:16px; }
 .esc-data { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:14px; }
 .esc-data span { display:block; font-size:12px; color:var(--muted, #64748b); margin-bottom:4px; }
 .esc-data strong { font-size:14px; }
 .esc-actions-bar { display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:10px; margin-top:14px; }
 .esc-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:12px 16px; border-radius:10px; font-weight:800; font-size:13px; text-decoration:none; border:none; cursor:pointer; }
 .esc-btn.call { background:#0284c7; color:#fff; }
 .esc-btn.whatsapp { background:#16a34a; color:#fff; }
</style>
@endpush

@section('content')
<div style="max-width:800px; margin:0 auto;">
    @if (session('success'))<div class="alert success" style="margin-bottom:16px;">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>@endif

    <div class="esc-hero">
        <h2><i class="bi bi-exclamation-octagon-fill"></i> {{ __('crm.donor_unreachable_title') }}</h2>
        <p>{{ __('crm.donor_unreachable_desc') }}</p>
    </div>

    {{-- Donor & Collector Details --}}
    <div class="esc-card">
        <h3><i class="bi bi-person-lines-fill"></i> {{ __('crm.collection_contact_details') }}</h3>
        <div class="esc-data">
            <div><span>{{ __('crm.donor') }}</span><strong>{{ $collectionCase->lead?->name ?: __('crm.unnamed_lead') }}</strong></div>
            <div><span>{{ __('crm.expected_collection_amount') }}</span><strong style="color:#16a34a; font-size:18px;">{{ number_format((float) $collectionCase->expected_amount, 2) }} {{ __('crm.currency_egp') }}</strong></div>
            <div><span>{{ __('crm.donor_phone') }}</span><strong><a href="tel:{{ preg_replace('/[^0-9+]/', '', (string)$collectionCase->lead?->phone) }}" data-voice-dial="{{ preg_replace('/[^0-9+]/', '', (string)$collectionCase->lead?->phone) }}" data-lead-name="{{ $collectionCase->lead?->name }}">{{ $collectionCase->lead?->phone ?: '—' }}</a></strong></div>
            <div><span>{{ __('crm.geography_and_zones') }}</span><strong>{{ $collectionCase->subregion?->full_name ?? ($collectionCase->lead?->subregion?->full_name ?? ($collectionCase->governorate?->name ?? '—')) }}</strong></div>
            <div style="grid-column:1/-1;"><span>{{ __('crm.donor_address') }}</span><strong>{{ $collectionCase->collection_address }}</strong></div>
            <div><span>{{ __('crm.collector_on_site') }}</span><strong>{{ $escalation->collector?->name ?: '—' }} ({{ $escalation->collector?->mobile_phone ?: '—' }})</strong></div>
            <div><span>{{ __('crm.call_center_agent') }}</span><strong>{{ $escalation->callCenterUser?->name ?: '—' }}</strong></div>
            @if($escalation->collector_note)
                <div style="grid-column:1/-1; background:rgba(220,38,55,0.05); padding:12px; border-radius:10px;">
                    <span>{{ __('crm.collector_note') }}</span>
                    <p style="margin:0; font-weight:700;">{{ $escalation->collector_note }}</p>
                </div>
            @endif
        </div>

        @php $cleanPhone = preg_replace('/[^0-9+]/', '', (string)($collectionCase->lead?->phone ?? '')); @endphp
        @if($cleanPhone)
            <div class="esc-actions-bar">
                <a href="tel:{{ $cleanPhone }}" data-voice-dial="{{ $cleanPhone }}" data-lead-name="{{ $collectionCase->lead?->name }}" class="esc-btn call"><i class="bi bi-telephone-fill"></i> {{ __('crm.call') }} {{ __('crm.donor') }}</a>
                <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" rel="noopener" class="esc-btn whatsapp"><i class="bi bi-whatsapp"></i> WhatsApp {{ __('crm.donor') }}</a>
                @if($escalation->collector?->mobile_phone)
                    @php $collectorPhone = preg_replace('/[^0-9+]/', '', (string)$escalation->collector->mobile_phone); @endphp
                    <a href="tel:{{ $collectorPhone }}" data-voice-dial="{{ $collectorPhone }}" data-lead-name="{{ $escalation->collector?->name }}" class="esc-btn" style="background:#475569; color:#fff;"><i class="bi bi-telephone-outbound"></i> {{ __('crm.call') }} {{ __('crm.collector') }}</a>
                @endif
            </div>
        @endif
    </div>

    {{-- Decision Form --}}
    @if($escalation->isPending())
        <div class="esc-card" style="border:2px solid var(--red);">
            <h3><i class="bi bi-check2-circle"></i> {{ __('crm.call_center_response') }}</h3>
            <p style="color:var(--muted); font-size:13px; margin-top:0;">تواصل مع المتبرع هاتفياً ثم حدد القرار لإشعار المحصل المتواجد بالموقع ومسؤول التحصيل فوراً:</p>

            <form method="POST" action="{{ route('v2.collections.escalations.resolve', $escalation) }}">
                @csrf @method('PATCH')
                
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-weight:800; margin-bottom:10px;">اختر القرار:</label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <label class="check-card" style="cursor:pointer; padding:14px; border:2px solid #16a34a;">
                            <input type="radio" name="decision" value="collect_now" required onchange="toggleDueAt(false)" checked>
                            <div>
                                <strong style="color:#16a34a;"><i class="bi bi-check-circle-fill"></i> {{ __('crm.action_collect_now') }}</strong>
                                <small style="display:block; margin-top:4px;">المتبرع متواجد وجاهز لدفع التبرع للمحصل حالاً.</small>
                            </div>
                        </label>
                        <label class="check-card" style="cursor:pointer; padding:14px; border:2px solid #0284c7;">
                            <input type="radio" name="decision" value="rescheduled" required onchange="toggleDueAt(true)">
                            <div>
                                <strong style="color:#0284c7;"><i class="bi bi-calendar-date-fill"></i> {{ __('crm.action_reschedule') }}</strong>
                                <small style="display:block; margin-top:4px;">تم الاتفاق مع المتبرع على موعد تحصيل بديل.</small>
                            </div>
                        </label>
                    </div>
                </div>

                <div id="rescheduleDateField" style="display:none; margin-bottom:14px;">
                    <label for="newDueAt" style="font-weight:700;">{{ __('crm.collection_due_at') }} <span style="color:var(--red)">*</span></label>
                    <input type="datetime-local" id="newDueAt" name="due_at" value="{{ now()->addDay()->format('Y-m-d\T12:00') }}" style="padding:10px; border-radius:8px; border:1px solid var(--line); width:100%;">
                </div>

                <div style="margin-bottom:18px;">
                    <label for="responseNote" style="font-weight:700;">{{ __('crm.optional_notes') }}</label>
                    <textarea id="responseNote" name="response_note" rows="2" placeholder="اكتب تفاصيل المكالمة مع المتبرع أو أسباب إعادة الجدولة..." style="padding:10px; border-radius:8px; border:1px solid var(--line); width:100%;"></textarea>
                </div>

                <button class="btn primary" type="submit" style="min-height:48px; width:100%; font-size:15px;">
                    <i class="bi bi-send-check"></i> حفظ القرار وإبلاغ المحصل
                </button>
            </form>
        </div>
    @else
        <div class="esc-card" style="background:#f8fafc;">
            <h3><i class="bi bi-info-circle"></i> {{ __('crm.call_center_response') }}</h3>
            <p><strong>الحالة:</strong> {{ $escalation->status === 'collect_now' ? __('crm.action_collect_now') : __('crm.action_reschedule') }}</p>
            @if($escalation->new_due_at)<p><strong>الموعد الجديد:</strong> {{ $escalation->new_due_at->translatedFormat('d M Y, H:i') }}</p>@endif
            @if($escalation->response_note)<p><strong>ملاحظات:</strong> {{ $escalation->response_note }}</p>@endif
            <p><small style="color:var(--muted)">تم الرد في: {{ $escalation->responded_at?->translatedFormat('d M Y, H:i') }}</small></p>
        </div>
    @endif
</div>

<script>
function toggleDueAt(show) {
    const field = document.getElementById('rescheduleDateField');
    if (field) field.style.display = show ? 'block' : 'none';
}
</script>
@endsection
