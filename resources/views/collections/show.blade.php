@extends('leads.transfer-layout')

@section('title', __('crm.collection_case').' #'.$collectionCase->id)
@section('page-title', __('crm.collection_case').' #'.$collectionCase->id)
@section('page-description', __('crm.collection_restricted_view'))

@section('top-actions')
 <a class="btn soft" href="{{ route('v2.collections.index') }}"><i class="bi bi-arrow-right"></i>{{ __('crm.back_to_collections') }}</a>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
 .collection-detail { --cd-ink:#172033; --cd-muted:#667085; --cd-line:#e4e7ec; --cd-card:#fff; --cd-accent:#0f766e; }
 .collection-hero { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; padding:22px; margin-bottom:16px; color:#fff; background:linear-gradient(120deg,#123c40,#0f766e); border-radius:14px; }
 .collection-hero h2 { margin:0 0 6px; font-size:24px; }.collection-hero p{margin:0;color:#ccfbf1}.collection-amount{text-align:end}.collection-amount span{display:block;font-size:12px}.collection-amount strong{font-size:28px}
 .collection-detail-grid { display:grid; grid-template-columns:minmax(0,1.5fr) minmax(290px,.8fr); gap:16px; align-items:start; }
 .collection-card { padding:18px; margin-bottom:16px; border:1px solid var(--cd-line); border-radius:12px; background:var(--cd-card); }
 .collection-card h3 { margin:0 0 16px; color:var(--cd-ink); font-size:15px; }.collection-card h3 i{color:var(--cd-accent);margin-inline-end:7px}
 .collection-data { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }.collection-data span{display:block;color:var(--cd-muted);font-size:11px;margin-bottom:4px}.collection-data strong{color:var(--cd-ink);font-size:14px;overflow-wrap:anywhere}
 .collection-form { display:grid; gap:12px; padding-top:14px; margin-top:14px; border-top:1px solid var(--cd-line); }.collection-form label{font-size:12px;font-weight:700;color:var(--cd-ink)}.collection-form input,.collection-form select,.collection-form textarea{width:100%;border:1px solid var(--cd-line);border-radius:8px;padding:10px;background:var(--cd-card);color:var(--cd-ink);font-size:13px}
 .collection-timeline { display:grid; gap:0; }.collection-event{position:relative;padding:0 22px 18px 0;border-right:2px solid var(--cd-line)}.collection-event:last-child{border-color:transparent}.collection-event:before{content:"";position:absolute;right:-6px;top:2px;width:10px;height:10px;border-radius:50%;background:var(--cd-accent)}.collection-event strong{display:block;color:var(--cd-ink);font-size:13px}.collection-event span,.collection-event p{color:var(--cd-muted);font-size:11px;margin:4px 0 0}
 .collection-actions .btn{width:100%;min-height:44px;font-weight:800;font-size:13px;border-radius:10px}.btn.danger{background:#fff1f2;border-color:#fecdd3;color:#be123c}
 .collector-mobile-actions-bar { display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:8px; margin-top:14px; padding-top:14px; border-top:1px solid var(--cd-line); }
 .collector-touch-btn { min-height:44px; display:inline-flex; align-items:center; justify-content:center; gap:7px; padding:8px 12px; border-radius:10px; font-weight:800; font-size:12px; text-decoration:none; border:1px solid transparent; transition:opacity .15s; }
 .collector-touch-btn:hover { opacity:.9 }
 .collector-touch-btn.call { background:#0284c7; color:#fff }
 .collector-touch-btn.whatsapp { background:#16a34a; color:#fff }
 .collector-touch-btn.maps { background:#f8fafc; border-color:#cbd5e1; color:#334155; grid-column:1/-1 }
 .camera-upload-box { border:2px dashed #99d5cf; border-radius:12px; background:#f0fdfa; padding:16px; text-align:center; cursor:pointer; }
 .camera-trigger-label { display:flex; flex-direction:column; align-items:center; gap:6px; cursor:pointer; }
 .camera-icon-wrap { width:46px; height:46px; border-radius:50%; background:#0f766e; color:#fff; display:grid; place-items:center; font-size:22px }
 .camera-trigger-label strong { color:#0f766e; font-size:13px; font-weight:900 }
 .camera-trigger-label small { color:#64748b; font-size:11px }
 .camera-preview-wrap { display:flex; flex-direction:column; align-items:center; gap:10px; }
 .camera-preview-wrap img { max-height:220px; width:auto; border-radius:8px; border:1px solid #cbd5e1; object-fit:contain; }
 html[dir="ltr"] .collection-event{padding:0 0 18px 22px;border-right:0;border-left:2px solid var(--cd-line)}html[dir="ltr"] .collection-event:before{right:auto;left:-6px}
 html.dark-mode .collection-detail{--cd-ink:#f4f4f5;--cd-muted:#a1a1aa;--cd-line:rgba(255,255,255,.1);--cd-card:rgba(24,24,27,.72)}
 html.dark-mode .camera-upload-box { background:#132e2c; border-color:#115e59 }
 html.dark-mode .collector-touch-btn.maps { background:#27272a; border-color:#3f3f46; color:#e4e4e7 }
 @media(max-width:850px){.collection-detail-grid{grid-template-columns:1fr}.collection-hero{display:none!important}}
@endpush

@section('content')
<div class="collection-detail">
 @if (session('success'))<div class="alert success">{{ session('success') }}</div>@endif
 @if ($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif

 <header class="collection-hero">
  <div><h2>{{ $collectionCase->lead?->name ?: __('crm.unnamed_lead') }}</h2><p>{{ $statuses[$collectionCase->status] ?? $collectionCase->status }} · {{ $collectionCase->donation_type }}</p></div>
  <div class="collection-amount"><span>{{ __('crm.expected_collection_amount') }}</span><strong>{{ number_format((float) $collectionCase->expected_amount, 2) }}</strong></div>
 </header>

 <div class="collection-detail-grid">
  <main>
   <section class="collection-card">
    <h3><i class="bi bi-person-vcard"></i>{{ __('crm.collection_contact_details') }}</h3>
    <div class="collection-data">
     <div><span>{{ __('crm.donor_phone') }}</span><strong><a href="tel:{{ preg_replace('/[^0-9+]/', '', (string)$collectionCase->lead?->phone) }}" data-voice-dial="{{ preg_replace('/[^0-9+]/', '', (string)$collectionCase->lead?->phone) }}" data-lead-name="{{ $collectionCase->lead?->name }}">{{ $collectionCase->lead?->phone ?: '—' }}</a></strong></div>
     <div><span>{{ __('crm.email') }}</span><strong>{{ $collectionCase->lead?->email ?: '—' }}</strong></div>
     <div><span>{{ __('crm.donor_address') }}</span><strong>{{ $collectionCase->collection_address }}</strong></div>
     <div><span>{{ __('crm.geography_and_zones') }}</span><strong>{{ $collectionCase->subregion?->full_name ?? ($collectionCase->lead?->subregion?->full_name ?? ($collectionCase->governorate?->name ?? ($collectionCase->lead?->governorate ?? '—'))) }}</strong></div>
     <div>
      <span>{{ __('crm.call_center_agent') }}</span>
      <strong>{{ $collectionCase->lead?->assignedUser?->name ?? $collectionCase->lead?->assigned_employee ?? '—' }}</strong>
      @if($collectionCase->lead?->assignedUser?->mobile_phone)
       <small style="display:block;color:var(--cd-muted);font-size:11px"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $collectionCase->lead->assignedUser->mobile_phone) }}" data-voice-dial="{{ preg_replace('/[^0-9+]/', '', $collectionCase->lead->assignedUser->mobile_phone) }}"><i class="bi bi-telephone"></i> {{ $collectionCase->lead->assignedUser->mobile_phone }}</a></small>
      @endif
     </div>
     <div>
      <span>{{ __('crm.assigned_collector') }} ({{ __('crm.manager') }})</span>
      <strong>{{ $collectionCase->assignedCollector?->name ?: __('crm.unassigned') }}</strong>
      @if($collectionCase->assignedCollector?->manager)
       <small style="display:block;color:var(--cd-muted);font-size:11px"><i class="bi bi-person-check"></i> {{ $collectionCase->assignedCollector->manager->name }}</small>
      @endif
     </div>
    </div>
    @if ($collectionCase->lead?->phone || $collectionCase->collection_address)
     @php
      $phoneClean = preg_replace('/[^0-9+]/', '', (string)($collectionCase->lead?->phone ?? ''));
      $navQuery = urlencode($collectionCase->collection_address . ($collectionCase->lead?->governorate ? ' ' . $collectionCase->lead->governorate : ''));
     @endphp
     <div class="collector-mobile-actions-bar">
      @if ($phoneClean)
       <a href="tel:{{ $phoneClean }}" data-voice-dial="{{ $phoneClean }}" data-lead-name="{{ $collectionCase->lead?->name }}" class="collector-touch-btn call" title="{{ __('crm.call') }}">
        <i class="bi bi-telephone-fill"></i>
        <span>{{ __('crm.call') }}</span>
       </a>
       <a href="https://wa.me/{{ $phoneClean }}" target="_blank" rel="noopener" class="collector-touch-btn whatsapp" title="WhatsApp">
        <i class="bi bi-whatsapp"></i>
        <span>WhatsApp</span>
       </a>
      @endif
      @if ($collectionCase->collection_address)
       <a href="https://www.google.com/maps/search/?api=1&query={{ $navQuery }}" target="_blank" rel="noopener" class="collector-touch-btn maps" title="{{ __('crm.open_maps_navigation') }}">
        <i class="bi bi-geo-alt-fill"></i>
        <span>{{ __('crm.open_maps_navigation') }}</span>
       </a>
      @endif
     </div>
    @endif
   </section>

   <section class="collection-card">
    <h3><i class="bi bi-calendar-check"></i>{{ __('crm.collection_details') }}</h3>
    <div class="collection-data">
     <div><span>{{ __('crm.collection_due_at') }}</span><strong>{{ $collectionCase->due_at->translatedFormat('d M Y, H:i') }}</strong></div>
     <div><span>{{ __('crm.assigned_collector') }}</span><strong>{{ $collectionCase->assignedCollector?->name ?: __('crm.unassigned') }}</strong></div>
     <div><span>{{ __('crm.donation_cycle') }}</span><strong>{{ __('crm.donation_cycle_'.$collectionCase->cycle) }}</strong></div>
     <div><span>{{ __('crm.branch') }}</span><strong>{{ $collectionCase->branch?->name ?: '—' }}</strong></div>
     @if($collectionCase->notes)<div style="grid-column:1/-1"><span>{{ __('crm.collection_notes') }}</span><strong>{{ $collectionCase->notes }}</strong></div>@endif
    </div>
    @if($collectionCase->donation?->receipt_path)
     <div style="display:flex;gap:8px;margin-top:16px">
      <a class="btn soft small" target="_blank" href="{{ route('v2.collections.receipt', $collectionCase) }}">{{ __('crm.preview_receipt') }}</a>
      <a class="btn soft small" href="{{ route('v2.collections.receipt.download', $collectionCase) }}">{{ __('crm.download_receipt') }}</a>
     </div>
    @endif
   </section>

   <section class="collection-card">
    <h3><i class="bi bi-clock-history"></i>{{ __('crm.collection_activity') }}</h3>
    <div class="collection-timeline">
     @forelse($collectionCase->activities as $activity)
      <div class="collection-event"><strong>{{ __('crm.collection_action_'.$activity->action) }}</strong><span>{{ $activity->actor?->name ?: __('crm.system') }} · {{ $activity->created_at->translatedFormat('d M Y, H:i') }}</span>@if($activity->notes)<p>{{ $activity->notes }}</p>@endif</div>
     @empty <p>{{ __('crm.no_collection_activity') }}</p> @endforelse
    </div>
   </section>
  </main>

  <aside class="collection-actions">
   @if(in_array($collectionCase->status, \App\Models\CollectionCase::OPEN_STATUSES, true))
    @can('assign', $collectionCase)
     <section class="collection-card">
      <h3><i class="bi bi-person-check"></i>{{ __('crm.assign_collector') }}</h3>
      <form class="collection-form" method="POST" action="{{ route('v2.collections.assign', $collectionCase) }}">
       @csrf @method('PATCH')
       <label for="collectorSelect">{{ __('crm.select_collector') }}</label>
       <select id="collectorSelect" name="assigned_collector_user_id" required>
        <option value="">{{ __('crm.select_collector') }}</option>
        @foreach($collectors as $collector)
         @php
          $caseSubId = $collectionCase->subregion_id ?? $collectionCase->lead?->subregion_id;
          $caseGovId = $collectionCase->governorate_id ?? $collectionCase->lead?->governorate_id;
          $isSubMatch = $caseSubId && $collector->collection_subregion_id === $caseSubId;
          $isGovMatch = !$isSubMatch && $caseGovId && $collector->collectionSubregion?->governorate_id === $caseGovId;

          if (!$isSubMatch && !$isGovMatch) {
              $donorGov = (string) ($collectionCase->lead?->governorate ?? '');
              $donorAddr = (string) ($collectionCase->collection_address ?? '');
              $collectorZone = (string) ($collector->collection_zone ?? '');
              if (!empty($collectorZone)) {
                  $words = array_filter(preg_split('/[\s,،\-]+/u', $collectorZone));
                  foreach ($words as $w) {
                      $cleanW = trim($w);
                      if (mb_strlen($cleanW) >= 3 && (
                          (!empty($donorGov) && (str_contains(mb_strtolower($donorGov), mb_strtolower($cleanW)) || str_contains(mb_strtolower($cleanW), mb_strtolower($donorGov)))) ||
                          (!empty($donorAddr) && str_contains(mb_strtolower($donorAddr), mb_strtolower($cleanW)))
                      )) {
                          $isGovMatch = true;
                          break;
                      }
                  }
              }
          }
          $zoneBadge = $collector->collectionSubregion ? $collector->collectionSubregion->full_name : $collector->collection_zone;
         @endphp
         <option value="{{ $collector->id }}" @selected((int)$collectionCase->assigned_collector_user_id === (int)$collector->id)>
          {{ $collector->name }}@if($zoneBadge) — [{{ $zoneBadge }}]@endif @if($isSubMatch) ★ ({{ __('crm.matching_subregion') }})@elseif($isGovMatch) ★ ({{ __('crm.matching_zone') }})@endif
         </option>
        @endforeach
       </select>
       <textarea name="notes" rows="2" placeholder="{{ __('crm.optional_notes') }}"></textarea>
       <button class="btn primary" type="submit">{{ __('crm.save_assignment') }}</button>
      </form>
     </section>
    @endcan

    @if($collectionCase->status === \App\Models\CollectionCase::STATUS_AWAITING_CALL_CENTER)
     <section class="collection-card" style="border:2px solid var(--red, #dc2626); background:#fff5f6;">
      <h3 style="color:var(--red, #dc2626);"><i class="bi bi-headset"></i> {{ __('crm.collection_status_awaiting_call_center') }}</h3>
      <p style="font-size:13px; margin:0 0 12px; color:var(--cd-ink);">
       {{ __('crm.donor_unreachable_desc') }}
      </p>
      @php
       $latestEsc = $collectionCase->escalations->first();
       $canResolve = auth()->user()->isSuperAdmin()
           || (int)($latestEsc?->call_center_user_id ?? 0) === (int)auth()->id()
           || (int)($collectionCase->lead?->assigned_user_id ?? 0) === (int)auth()->id()
           || auth()->user()->hasPermission(\App\Security\CrmPermission::COLLECTIONS_MANAGE)
           || auth()->user()->hasPermission(\App\Security\CrmPermission::COLLECTIONS_ESCALATIONS_RESPOND);
      @endphp
      @if($canResolve && $latestEsc)
       <a class="btn primary" href="{{ route('v2.collections.escalations.show', $latestEsc) }}" style="display:flex; width:100%; text-decoration:none; margin-bottom:10px;">
        <i class="bi bi-telephone-outbound"></i> {{ __('crm.call_center_response') }}
       </a>
      @else
       <div style="font-size:12px; font-weight:700; color:var(--cd-muted); padding:10px; background:#fff; border-radius:8px; border:1px solid var(--cd-line);">
        <i class="bi bi-clock"></i> {{ __('crm.waiting_for_call_center') }}
       </div>
      @endif
     </section>
    @else
     {{-- Collector Escalation Trigger Button --}}
     @if(auth()->user()->isSuperAdmin() || (int)$collectionCase->assigned_collector_user_id === (int)auth()->id())
      <section class="collection-card" style="border:1px dashed #dc2626; background:rgba(220,38,55,0.03);">
       <h3><i class="bi bi-exclamation-triangle" style="color:#dc2626;"></i> {{ __('crm.donor_unreachable_title') }}</h3>
       <p style="font-size:12px; color:var(--cd-muted); margin-top:0;">إذا كنت في الموقع ولم تتمكن من الوصول للمتبرع، قم بتصعيد الطلب لمسؤول الكول سنتر فوراً:</p>
       <form class="collection-form" method="POST" action="{{ route('v2.collections.escalate', $collectionCase) }}" style="margin-top:0; padding-top:0; border-top:none;">
        @csrf
        <textarea name="collector_note" rows="2" placeholder="اكتب ملاحظة (مثال: الهاتف مغلق، جرس الباب لا يستجيب)..."></textarea>
        <button class="btn danger" type="submit" style="background:#dc2626; color:#fff; border:none; min-height:44px;">
         <i class="bi bi-headset"></i> {{ __('crm.donor_unreachable_escalate') }}
        </button>
       </form>
      </section>
     @endif

     @canany(['manage', 'collect'], $collectionCase)
      <section class="collection-card"><h3><i class="bi bi-calendar2-week"></i>{{ __('crm.reschedule_collection') }}</h3><form class="collection-form" method="POST" action="{{ route('v2.collections.reschedule', $collectionCase) }}">@csrf @method('PATCH')<input type="datetime-local" name="due_at" value="{{ $collectionCase->due_at->format('Y-m-d\TH:i') }}" required><textarea name="notes" rows="2" placeholder="{{ __('crm.optional_notes') }}"></textarea><button class="btn soft" type="submit">{{ __('crm.reschedule') }}</button></form></section>
      <section class="collection-card"><h3><i class="bi bi-exclamation-circle"></i>{{ __('crm.report_failed_collection') }}</h3><form class="collection-form" method="POST" action="{{ route('v2.collections.fail', $collectionCase) }}">@csrf @method('PATCH')<textarea name="notes" rows="3" placeholder="{{ __('crm.failure_reason') }}" required></textarea><button class="btn soft" type="submit">{{ __('crm.mark_failed') }}</button></form></section>
     @endcanany

     @can('complete', $collectionCase)
      <section class="collection-card" id="completeCollectionSection">
       <h3><i class="bi bi-cash-coin"></i>{{ __('crm.complete_collection') }}</h3>
       <form class="collection-form" method="POST" enctype="multipart/form-data" action="{{ route('v2.collections.complete', $collectionCase) }}">
        @csrf
        <label for="collectionReceipt">{{ __('crm.donation_receipt') }} <span class="required">*</span></label>
        <div class="camera-upload-box" id="cameraUploadBox">
         <label for="collectionReceipt" class="camera-trigger-label" id="cameraTriggerLabel">
          <div class="camera-icon-wrap"><i class="bi bi-camera-fill"></i></div>
          <strong>{{ __('crm.take_receipt_photo') }}</strong>
          <small>{{ __('crm.camera_or_gallery') }}</small>
         </label>
         <input
          id="collectionReceipt"
          type="file"
          name="donation_receipt"
          accept="image/png,image/jpeg,image/webp,image/*"
          capture="environment"
          required
          style="display:none;"
         >
         <div class="camera-preview-wrap" id="cameraPreviewWrap" style="display:none;">
          <img id="cameraPreviewImg" src="" alt="{{ __('crm.donation_receipt') }}">
          <button type="button" class="btn small soft" id="cameraRetakeBtn">
           <i class="bi bi-arrow-repeat"></i> {{ __('crm.retake_photo') }}
          </button>
         </div>
        </div>
        <textarea name="notes" rows="2" placeholder="{{ __('crm.optional_notes') }}"></textarea>
        <button class="btn primary" type="submit" style="min-height:48px;font-size:14px;">
         <i class="bi bi-check2-circle"></i> {{ __('crm.confirm_collection_received') }}
        </button>
       </form>
      </section>
     @endcan
    @endif
    @can('cancel', $collectionCase)
     <section class="collection-card"><h3><i class="bi bi-x-circle"></i>{{ __('crm.cancel_collection') }}</h3><form class="collection-form" method="POST" action="{{ route('v2.collections.cancel', $collectionCase) }}">@csrf @method('PATCH')<textarea name="notes" rows="3" placeholder="{{ __('crm.cancellation_reason') }}" required></textarea><button class="btn danger" type="submit">{{ __('crm.cancel_collection') }}</button></form></section>
    @endcan
   @endif
  </aside>
 </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
 const receiptInput = document.getElementById('collectionReceipt');
 const previewWrap = document.getElementById('cameraPreviewWrap');
 const previewImg = document.getElementById('cameraPreviewImg');
 const triggerLabel = document.getElementById('cameraTriggerLabel');
 const retakeBtn = document.getElementById('cameraRetakeBtn');

 if (!receiptInput) return;

 receiptInput.addEventListener('change', () => {
  if (receiptInput.files && receiptInput.files[0]) {
   const reader = new FileReader();
   reader.onload = (e) => {
    if (previewImg) previewImg.src = e.target.result;
    if (triggerLabel) triggerLabel.style.display = 'none';
    if (previewWrap) previewWrap.style.display = 'flex';
   };
   reader.readAsDataURL(receiptInput.files[0]);
  }
 });

 if (retakeBtn) {
  retakeBtn.addEventListener('click', (e) => {
   e.stopPropagation();
   receiptInput.value = '';
   if (previewImg) previewImg.src = '';
   if (previewWrap) previewWrap.style.display = 'none';
   if (triggerLabel) triggerLabel.style.display = 'flex';
   receiptInput.click();
  });
 }
});
</script>
@endsection
