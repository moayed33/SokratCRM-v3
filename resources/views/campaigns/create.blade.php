@extends('leads.transfer-layout')

@section('title', ($editing ?? false) ? __('crm.edit_campaign') : __('crm.add_new_campaign'))
@section('page-title', ($editing ?? false) ? __('crm.edit_campaign') : __('crm.add_new_campaign'))
@section('page-title-ar', ($editing ?? false) ? __('crm.edit_campaign', [], 'ar') : __('crm.add_new_campaign', [], 'ar'))
@section('page-description', __('crm.campaign_create_subtitle'))

@section('top-actions')
 <a class="btn soft" href="{{ route('v2.campaigns.index') }}">
  {{ __('crm.view_campaigns') }}
 </a>
@endsection

@push('styles')
<style>
 .campaign-form-card{max-width:980px;margin-inline:auto}
 .campaign-user-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
 .campaign-user{min-height:72px;display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--line);border-radius:11px;background:#fafbfc;cursor:pointer;transition:.16s}
 .campaign-user:hover{border-color:#aabfe0;background:#f5f9ff}
 .campaign-user:has(input:checked){border-color:#3478f6;background:#eef5ff;box-shadow:0 0 0 2px #3478f612}
 .campaign-user input{width:18px;height:18px;flex:0 0 18px;accent-color:#3478f6}
 .campaign-user strong,.campaign-user small{display:block}
 .campaign-user small{margin-top:4px;color:var(--muted);font-size:10px}
 .campaign-empty-users{padding:16px;border:1px dashed var(--line);border-radius:11px;color:var(--muted);text-align:center}
 .campaign-image-field{display:grid;grid-template-columns:120px minmax(0,1fr);align-items:center;gap:16px}
 .campaign-image-preview{width:120px;height:92px;display:grid;place-items:center;overflow:hidden;border:1px dashed #cfd6e1;border-radius:13px;background:#f7f9fc;color:var(--muted);font-size:11px;font-weight:900;text-align:center}
 .campaign-image-preview img{width:100%;height:100%;display:block;object-fit:cover}
 .campaign-image-input input{padding:9px;background:#fafbfc}
 @media(max-width:1050px){.campaign-user-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
 @media(max-width:700px){.campaign-user-grid{grid-template-columns:1fr}.campaign-image-field{grid-template-columns:1fr}.campaign-image-preview{width:100%;height:150px}}
</style>
@endpush

@section('content')
@php
 $selectedUserIds = collect(old(
  'user_ids',
  ($editing ?? false) ? $campaign->users->modelKeys() : []
 ))
  ->map(static fn ($id) => (int) $id);
@endphp

<section class="transfer-card campaign-form-card">
 <div class="card-head">
  <div>
   <h3>{{ __('crm.campaign_data') }}</h3>
   <p>{{ __('crm.campaign_required_notice') }}</p>
  </div>
 </div>

 <form method="POST" action="{{ ($editing ?? false) ? route('v2.campaigns.update', $campaign) : route('v2.campaigns.store') }}" enctype="multipart/form-data">
  @csrf
  @if ($editing ?? false)
   @method('PATCH')
  @endif

  <div class="card-body">
   <div class="grid">
    <div class="field full">
     <label for="name" data-ar-label="{{ __('crm.campaign_name', [], 'ar') }}">{{ __('crm.campaign_name') }}</label>
     <input
      class="control"
      id="name"
      name="name"
      type="text"
      maxlength="150"
      required
      autofocus
      value="{{ old('name', ($editing ?? false) ? $campaign->name : '') }}"
      placeholder="{{ __('crm.campaign_name_example') }}"
     >
     </div>

     <div class="field full">
      <label for="campaignImage">{{ __('crm.campaign_image') }} <span class="help">(اختياري)</span></label>
      <div class="campaign-image-field">
       <div class="campaign-image-preview" id="campaignImagePreview">
        @if (($editing ?? false) && $campaign->image_path)
         <img src="{{ asset('storage/'.$campaign->image_path) }}" alt="صورة {{ $campaign->name }}">
        @else
         <span>{{ __('crm.no_image') }}</span>
        @endif
       </div>
       <div class="campaign-image-input">
        <input
         class="control"
         id="campaignImage"
         name="image"
         type="file"
         accept="image/jpeg,image/png,image/webp"
        >
        <span class="help">JPG أو PNG أو WebP، بحد أقصى 4MB.</span>
       </div>
      </div>
     </div>

    <div class="field">
     <label for="cost">{{ __('crm.campaign_cost') }}</label>
     <input
      class="control"
      id="cost"
      name="cost"
      type="number"
      min="0"
      max="999999999999.99"
      step="0.01"
      required
      value="{{ old('cost', ($editing ?? false) ? $campaign->cost : '') }}"
      placeholder="0.00"
     >
     <span class="help">{{ __('crm.campaign_cost_hint') }}</span>
    </div>

    <div class="field">
     <label for="starts_at">{{ __('crm.campaign_start') }}</label>
     <input
      class="control"
      id="starts_at"
      name="starts_at"
      type="datetime-local"
      required
      value="{{ old('starts_at', ($editing ?? false) ? $campaign->starts_at->format('Y-m-d\TH:i') : '') }}"
     >
    </div>

    <div class="field">
     <label for="ends_at">{{ __('crm.campaign_end') }}</label>
     <input
      class="control"
      id="ends_at"
      name="ends_at"
      type="datetime-local"
      required
      value="{{ old('ends_at', ($editing ?? false) ? $campaign->ends_at->format('Y-m-d\TH:i') : '') }}"
     >
     <span class="help">{{ __('crm.campaign_end_hint') }}</span>
    </div>

    <div class="field full">
     <label>{{ __('crm.campaign_users') }}</label>

     @if ($users->isNotEmpty())
      <div class="campaign-user-grid">
       @foreach ($users as $user)
        <label class="campaign-user">
         <input
          type="checkbox"
          name="user_ids[]"
          value="{{ $user->id }}"
          @checked($selectedUserIds->contains((int) $user->id))
         >
         <span>
          <strong>{{ $user->name }}</strong>
          <small>
           {{ $user->groups->pluck('name')->join('، ') ?: __('crm.no_group') }}
          </small>
         </span>
        </label>
       @endforeach
      </div>
     @else
      <div class="campaign-empty-users">
       {{ __('crm.no_active_users_to_assign') }}
      </div>
     @endif
    </div>
   </div>

   <div class="actions">
    <button class="btn primary" type="submit" @disabled($users->isEmpty())>
      {{ ($editing ?? false) ? 'حفظ التعديلات' : 'إنشاء الحملة' }}
    </button>
     <a class="btn soft" href="{{ ($editing ?? false) ? route('v2.campaigns.show', $campaign) : route('v2.campaigns.index') }}">
     {{ __('crm.cancel') }}
    </a>
   </div>
  </div>
 </form>
</section>
@endsection

@push('scripts')
<script>
 (() => {
  const input = document.getElementById('campaignImage');
  const preview = document.getElementById('campaignImagePreview');

  input?.addEventListener('change', () => {
   const file = input.files?.[0];
   if (!file || !preview) return;

   const image = document.createElement('img');
   image.src = URL.createObjectURL(file);
   image.alt = 'معاينة صورة الحملة';
   image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });
   preview.replaceChildren(image);
  });
 })();
</script>
@endpush
