@php $hideBranchSwitcher = true; @endphp
@extends('leads.transfer-layout')

@section('title', ($editing ?? false) ? __('crm.edit_campaign') : __('crm.add_new_campaign'))
@section('page-title', ($editing ?? false) ? __('crm.edit_campaign') : __('crm.add_new_campaign'))
@section('page-title-ar', ($editing ?? false) ? __('crm.edit_campaign', [], 'ar') : __('crm.add_new_campaign', [], 'ar'))
@section('page-description', __('crm.campaign_create_subtitle'))

@section('top-actions')
 <a class="btn soft" href="{{ route('v2.campaigns.index') }}">
  <i class="bi bi-arrow-right rtl:rotate-180"></i>
  {{ __('crm.view_campaigns') }}
 </a>
@endsection

@push('styles')
<style>
 .campaign-overhaul-container {
  max-width: 1040px;
  margin-inline: auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
 }
 .campaign-card {
  border: 1px solid var(--line);
  border-radius: 18px;
  background: var(--card);
  box-shadow: var(--shadow);
  overflow: hidden;
 }
 html.dark-mode .campaign-card {
  background: var(--bg-card, rgba(24, 24, 27, 0.75)) !important;
  border: var(--border-glass, 1px solid rgba(255, 255, 255, 0.08)) !important;
  box-shadow: var(--shadow-card, 0 10px 30px rgba(0, 0, 0, 0.5)) !important;
 }
 .campaign-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 18px 24px;
  border-bottom: 1px solid var(--line);
  background: rgba(0, 0, 0, 0.01);
 }
 html.dark-mode .campaign-card-header {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
  background: rgba(255, 255, 255, 0.02) !important;
 }
 .campaign-card-header-title {
  display: flex;
  align-items: center;
  gap: 10px;
 }
 .campaign-card-header-title i {
  font-size: 20px;
  color: var(--blue, #3478f6);
 }
 html.dark-mode .campaign-card-header-title i {
  color: #60a5fa !important;
 }
 .campaign-card-header-title h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: var(--dark);
 }
 html.dark-mode .campaign-card-header-title h3 {
  color: #f4f4f5 !important;
 }
 .campaign-card-header-title p {
  margin: 3px 0 0;
  font-size: 12px;
  color: var(--muted);
 }
 .campaign-card-body {
  padding: 24px;
 }
 .campaign-grid-2 {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20px;
 }
 .col-span-2 {
  grid-column: 1 / -1;
 }
 @media (max-width: 768px) {
  .campaign-grid-2 {
   grid-template-columns: 1fr;
   gap: 16px;
  }
  .col-span-2 {
   grid-column: span 1;
  }
 }
 .campaign-input-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
 }
 .campaign-input-label {
  font-size: 13px;
  font-weight: 700;
  color: var(--dark);
  display: flex;
  align-items: center;
  gap: 6px;
 }
 html.dark-mode .campaign-input-label {
  color: #e4e4e7 !important;
 }
 .campaign-input-label .required-star {
  color: var(--red, #dc2637);
  font-weight: 900;
 }
 .campaign-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
 }
 .campaign-currency-prefix {
  position: absolute;
  inset-inline-end: 14px;
  font-size: 13px;
  font-weight: 700;
  color: var(--muted);
  pointer-events: none;
 }
 .campaign-control {
  width: 100%;
  min-height: 46px;
  padding: 10px 14px;
  border: 1px solid #d8dee8;
  border-radius: 11px;
  background: #fff;
  color: var(--dark);
  font-size: 14px;
  transition: border-color 0.15s, box-shadow 0.15s;
  outline: none;
 }
 html.dark-mode .campaign-control {
  background: var(--bg-input, rgba(39, 39, 42, 0.65)) !important;
  border: 1px solid rgba(255, 255, 255, 0.14) !important;
  color: #f4f4f5 !important;
 }
 .campaign-control:focus {
  border-color: var(--blue, #3478f6);
  box-shadow: 0 0 0 3px rgba(52, 120, 246, 0.15);
 }
 html.dark-mode .campaign-control:focus {
  border-color: #60a5fa !important;
  box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2) !important;
 }
 .campaign-upload-zone {
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 16px;
  border: 1px dashed #cbd5e1;
  border-radius: 14px;
  background: #f8fafc;
  transition: all 0.2s;
 }
 html.dark-mode .campaign-upload-zone {
  background: rgba(255, 255, 255, 0.03) !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
 }
 .campaign-upload-preview {
  width: 96px;
  height: 76px;
  flex-shrink: 0;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #e2e8f0;
  border: 1px solid #cbd5e1;
  color: var(--muted);
 }
 html.dark-mode .campaign-upload-preview {
  background: rgba(255, 255, 255, 0.08) !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
 }
 .campaign-upload-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
 }
 .campaign-upload-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
 }
 .campaign-upload-details input[type="file"] {
  font-size: 13px;
 }

 /* User Tag Picker Styling */
 .picker-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;
 }
 .picker-search-wrapper {
  position: relative;
  flex: 1;
  min-width: 240px;
 }
 .picker-search-icon {
  position: absolute;
  inset-inline-start: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  font-size: 15px;
  pointer-events: none;
 }
 .picker-search-input {
  width: 100%;
  min-height: 42px;
  padding: 8px 12px;
  padding-inline-start: 36px;
  border: 1px solid #d8dee8;
  border-radius: 10px;
  background: #fff;
  color: var(--dark);
  font-size: 13px;
  outline: none;
  transition: all 0.15s;
 }
 html.dark-mode .picker-search-input {
  background: var(--bg-input, rgba(39, 39, 42, 0.65)) !important;
  border-color: rgba(255, 255, 255, 0.14) !important;
  color: #f4f4f5 !important;
 }
 .picker-search-input:focus {
  border-color: var(--blue, #3478f6);
  box-shadow: 0 0 0 3px rgba(52, 120, 246, 0.12);
 }
 .picker-actions {
  display: flex;
  align-items: center;
  gap: 8px;
 }
 .picker-btn-action {
  font-size: 12px;
  font-weight: 700;
  padding: 7px 12px;
  border-radius: 8px;
  border: 1px solid #d8dee8;
  background: #fff;
  color: var(--dark);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  transition: all 0.15s;
 }
 html.dark-mode .picker-btn-action {
  background: rgba(255, 255, 255, 0.05) !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
  color: #e4e4e7 !important;
 }
 .picker-btn-action:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
 }
 html.dark-mode .picker-btn-action:hover {
  background: rgba(255, 255, 255, 0.1) !important;
 }
 .picker-count-pill {
  font-size: 12px;
  font-weight: 800;
  padding: 6px 12px;
  border-radius: 20px;
  background: rgba(52, 120, 246, 0.1);
  color: var(--blue, #3478f6);
  display: inline-flex;
  align-items: center;
  gap: 5px;
 }
 html.dark-mode .picker-count-pill {
  background: rgba(96, 165, 250, 0.15) !important;
  color: #93c5fd !important;
 }

 /* Selected Badges Container */
 .picker-selected-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 12px;
  min-height: 48px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  margin-bottom: 16px;
  align-items: center;
 }
 html.dark-mode .picker-selected-badges {
  background: rgba(255, 255, 255, 0.03) !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
 }
 .picker-tag {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 5px 10px;
  background: #fff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  color: var(--dark);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
  animation: fadeIn 0.15s ease-out;
 }
 html.dark-mode .picker-tag {
  background: rgba(39, 39, 42, 0.9) !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
  color: #f4f4f5 !important;
 }
 .picker-tag-role {
  font-size: 10px;
  font-weight: 600;
  color: var(--muted);
  background: rgba(0, 0, 0, 0.04);
  padding: 2px 5px;
  border-radius: 4px;
 }
 html.dark-mode .picker-tag-role {
  background: rgba(255, 255, 255, 0.1) !important;
  color: #a1a1aa !important;
 }
 .picker-tag-remove {
  border: none;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  padding: 0 2px;
  font-size: 14px;
  line-height: 1;
  display: flex;
  align-items: center;
  transition: color 0.15s;
 }
 .picker-tag-remove:hover {
  color: var(--red, #dc2637);
 }
 .picker-empty-state {
  font-size: 12px;
  color: var(--muted);
  font-style: italic;
  padding: 4px 6px;
 }

 /* Scrollable User Cards Grid */
 .picker-users-grid {
  max-height: 250px;
  overflow-y: auto;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  padding: 4px;
  scrollbar-width: thin;
 }
 @media (max-width: 900px) {
  .picker-users-grid {
   grid-template-columns: repeat(2, minmax(0, 1fr));
  }
 }
 @media (max-width: 600px) {
  .picker-users-grid {
   grid-template-columns: 1fr;
  }
 }
 .picker-user-card {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 11px;
  background: #fff;
  cursor: pointer;
  transition: all 0.16s ease;
  user-select: none;
  position: relative;
 }
 html.dark-mode .picker-user-card {
  background: rgba(255, 255, 255, 0.03) !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
 }
 .picker-user-card:hover {
  border-color: #93c5fd;
  background: #f8fafc;
 }
 html.dark-mode .picker-user-card:hover {
  background: rgba(255, 255, 255, 0.06) !important;
  border-color: rgba(255, 255, 255, 0.18) !important;
 }
 .picker-user-card.selected {
  border-color: var(--blue, #3478f6);
  background: #eff6ff;
  box-shadow: 0 0 0 1px var(--blue, #3478f6);
 }
 html.dark-mode .picker-user-card.selected {
  border-color: #60a5fa !important;
  background: rgba(59, 130, 246, 0.15) !important;
  box-shadow: 0 0 0 1px #60a5fa !important;
 }
 .picker-avatar {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  background: #e2e8f0;
  color: #334155;
  font-weight: 800;
  font-size: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
 }
 html.dark-mode .picker-avatar {
  background: rgba(255, 255, 255, 0.12) !important;
  color: #f1f5f9 !important;
 }
 .picker-user-card.selected .picker-avatar {
  background: var(--blue, #3478f6);
  color: #fff;
 }
 html.dark-mode .picker-user-card.selected .picker-avatar {
  background: #3b82f6 !important;
  color: #fff !important;
 }
 .picker-user-info {
  flex: 1;
  min-width: 0;
 }
 .picker-user-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--dark);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
 }
 html.dark-mode .picker-user-name {
  color: #f4f4f5 !important;
 }
 .picker-user-role {
  font-size: 10px;
  color: var(--muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 2px;
 }
 .picker-check-indicator {
  width: 18px;
  height: 18px;
  border-radius: 6px;
  border: 1.5px solid #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  color: transparent;
  flex-shrink: 0;
  transition: all 0.15s;
 }
 html.dark-mode .picker-check-indicator {
  border-color: rgba(255, 255, 255, 0.2) !important;
 }
 .picker-user-card.selected .picker-check-indicator {
  background: var(--blue, #3478f6);
  border-color: var(--blue, #3478f6);
  color: #fff;
 }
 html.dark-mode .picker-user-card.selected .picker-check-indicator {
  background: #3b82f6 !important;
  border-color: #3b82f6 !important;
  color: #fff !important;
 }

 /* Action Bar */
 .campaign-actions-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 24px;
  border: 1px solid var(--line);
  border-radius: 18px;
  background: var(--card);
  box-shadow: var(--shadow);
 }
 html.dark-mode .campaign-actions-bar {
  background: var(--bg-card, rgba(24, 24, 27, 0.75)) !important;
  border: var(--border-glass, 1px solid rgba(255, 255, 255, 0.08)) !important;
 }

 @keyframes fadeIn {
  from { opacity: 0; transform: scale(0.96); }
  to { opacity: 1; transform: scale(1); }
 }
</style>
@endpush

@section('content')
@php
 $selectedUserIds = collect(old(
  'user_ids',
  ($editing ?? false) ? $campaign->users->modelKeys() : []
 ))->map(static fn ($id) => (int) $id)->values();

 $usersList = $users->map(function ($u) {
  $groupNames = $u->groups->pluck('name')->join('، ');
  $roleName = $groupNames ?: ($u->isSuperAdmin() ? __('crm.super_admin') : __('crm.sales_agent'));

  $nameParts = preg_split('/\s+/u', trim($u->name));
  $initials = '';
  if (count($nameParts) >= 2) {
   $initials = mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1);
  } else {
   $initials = mb_substr($u->name, 0, 2);
  }

  return [
   'id' => (int) $u->id,
   'name' => $u->name,
   'role' => $roleName,
   'initials' => mb_strtoupper($initials),
  ];
 })->values();
@endphp

<form
 method="POST"
 action="{{ ($editing ?? false) ? route('v2.campaigns.update', $campaign) : route('v2.campaigns.store') }}"
 enctype="multipart/form-data"
 class="campaign-overhaul-container"
>
 @csrf
 @if ($editing ?? false)
  @method('PATCH')
 @endif

 <!-- Card 1: بيانات الحملة الأساسية (Campaign Core Details) -->
 <section class="campaign-card">
  <div class="campaign-card-header">
   <div class="campaign-card-header-title">
    <i class="bi bi-megaphone-fill"></i>
    <div>
     <h3>{{ __('crm.campaign_data') }}</h3>
     <p>{{ __('crm.campaign_create_subtitle') }}</p>
    </div>
   </div>
  </div>

  <div class="campaign-card-body">
   <div class="campaign-grid-2">
    <!-- Field 1: اسم الحملة (name) - Col Span 2 -->
    <div class="campaign-input-group col-span-2">
     <label class="campaign-input-label" for="name">
      {{ __('crm.campaign_name') }}
      <span class="required-star">*</span>
     </label>
     <input
      class="campaign-control"
      id="name"
      name="name"
      type="text"
      maxlength="150"
      required
      autofocus
      value="{{ old('name', ($editing ?? false) ? $campaign->name : '') }}"
      placeholder="{{ __('crm.campaign_name_example') }} / مثال: حملة مبيعات الصيف / حملة إفطار صائم"
     >
     @error('name')
      <span class="help" style="color:var(--red)">{{ $message }}</span>
     @enderror
    </div>

    <!-- Field 2: الفرع (branch_id) -->
    <div class="campaign-input-group">
     <label class="campaign-input-label" for="branch_id">
      {{ __('crm.branch') }}
     </label>
     <select class="campaign-control" id="branch_id" name="branch_id">
      @if (auth()->user()->isSuperAdmin())
       <option value="">{{ __('crm.all_branches') }}</option>
      @endif
      @foreach (($branches ?? []) as $branch)
       <option
        value="{{ $branch->id }}"
        @selected((string) old('branch_id', ($editing ?? false) ? $campaign->branch_id : (auth()->user()->isSuperAdmin() ? '' : auth()->user()->branch_id)) === (string) $branch->id)
       >
        {{ (app()->getLocale() === 'en' && !empty($branch->name_en)) ? $branch->name_en : $branch->name_ar }}
       </option>
      @endforeach
     </select>
     @error('branch_id')
      <span class="help" style="color:var(--red)">{{ $message }}</span>
     @enderror
    </div>

    <!-- Field 3: تكلفة الحملة (cost) -->
    <div class="campaign-input-group">
     <label class="campaign-input-label" for="cost">
      {{ __('crm.campaign_cost') }}
      <span class="required-star">*</span>
     </label>
     <div class="campaign-input-wrapper">
      <input
       class="campaign-control"
       id="cost"
       name="cost"
       type="number"
       min="0"
       max="999999999999.99"
       step="0.01"
       required
       value="{{ old('cost', ($editing ?? false) ? $campaign->cost : '') }}"
       placeholder="0.00"
       style="padding-inline-end: 45px;"
      >
      <span class="campaign-currency-prefix">ج.م</span>
     </div>
     <span class="help">{{ __('crm.campaign_cost_hint') }}</span>
     @error('cost')
      <span class="help" style="color:var(--red)">{{ $message }}</span>
     @enderror
    </div>

    <!-- Field 4: بداية الحملة (starts_at) -->
    <div class="campaign-input-group">
     <label class="campaign-input-label" for="starts_at">
      {{ __('crm.campaign_start') }}
      <span class="required-star">*</span>
     </label>
     <input
      class="campaign-control"
      id="starts_at"
      name="starts_at"
      type="datetime-local"
      required
      value="{{ old('starts_at', ($editing ?? false) ? $campaign->starts_at->format('Y-m-d\TH:i') : '') }}"
     >
     @error('starts_at')
      <span class="help" style="color:var(--red)">{{ $message }}</span>
     @enderror
    </div>

    <!-- Field 5: نهاية الحملة (ends_at) -->
    <div class="campaign-input-group">
     <label class="campaign-input-label" for="ends_at">
      {{ __('crm.campaign_end') }}
      <span class="required-star">*</span>
     </label>
     <input
      class="campaign-control"
      id="ends_at"
      name="ends_at"
      type="datetime-local"
      required
      value="{{ old('ends_at', ($editing ?? false) ? $campaign->ends_at->format('Y-m-d\TH:i') : '') }}"
     >
     <span class="help">{{ __('crm.campaign_end_hint') }}</span>
     @error('ends_at')
      <span class="help" style="color:var(--red)">{{ $message }}</span>
     @enderror
    </div>

    <!-- Field 6: صورة الحملة (image) - Col Span 2 -->
    <div class="campaign-input-group col-span-2">
     <label class="campaign-input-label" for="campaignImage">
      {{ __('crm.campaign_image') }}
      <span class="help" style="font-weight: normal;">({{ __('crm.optional_suffix') }})</span>
     </label>
     <div class="campaign-upload-zone">
      <div class="campaign-upload-preview" id="campaignImagePreview">
       @if (($editing ?? false) && $campaign->image_path)
        <img src="{{ asset('storage/'.$campaign->image_path) }}" alt="{{ $campaign->name }}">
       @else
        <i class="bi bi-image" style="font-size: 28px;"></i>
       @endif
      </div>
      <div class="campaign-upload-details">
       <input
        class="campaign-control"
        id="campaignImage"
        name="image"
        type="file"
        accept="image/jpeg,image/png,image/webp"
       >
       <span class="help">{{ __('crm.image_file_help') }}</span>
      </div>
     </div>
     @error('image')
      <span class="help" style="color:var(--red)">{{ $message }}</span>
     @enderror
    </div>
   </div>
  </div>
 </section>

 <!-- Card 2: فريق العمل والمستخدمون المشاركون (Assigned Team Members) -->
 <section
  class="campaign-card"
  x-data="campaignUserPicker(@js($usersList), @js($selectedUserIds))"
 >
  <div class="campaign-card-header">
   <div class="campaign-card-header-title">
    <i class="bi bi-people-fill"></i>
    <div>
     <h3>{{ __('crm.campaign_users') }}</h3>
     <p>{{ __('crm.campaign_required_notice') }}</p>
    </div>
   </div>
   <div class="picker-count-pill">
    <i class="bi bi-check2-circle"></i>
    <span>المحدد: <strong x-text="selected.length"></strong> مستخدم</span>
   </div>
  </div>

  <div class="campaign-card-body">
   <!-- Toolbar: Search & Quick Actions -->
   <div class="picker-toolbar">
    <div class="picker-search-wrapper">
     <i class="bi bi-search picker-search-icon"></i>
     <input
      type="text"
      class="picker-search-input"
      x-model="search"
      placeholder="بحث بالاسم أو الدور الوظيفي..."
     >
    </div>
    <div class="picker-actions">
     <button
      type="button"
      class="picker-btn-action"
      @click="selectAllFiltered()"
     >
      <i class="bi bi-check-all"></i>
      <span>تحديد الكل</span>
     </button>
     <button
      type="button"
      class="picker-btn-action"
      @click="clearAll()"
     >
      <i class="bi bi-x-circle"></i>
      <span>إلغاء التحديد</span>
     </button>
    </div>
   </div>

   <!-- Selected Users Badges Container -->
   <div class="picker-selected-badges">
    <template x-if="selected.length === 0">
     <span class="picker-empty-state">لم يتم اختيار أي مستخدم بعد. انقر على المستخدمين أدناه للإضافة.</span>
    </template>

    <template x-for="id in selected" :key="id">
     <span class="picker-tag">
      <i class="bi bi-person-fill" style="color:var(--blue);"></i>
      <span x-text="getUser(id)?.name"></span>
      <span class="picker-tag-role" x-text="getUser(id)?.role"></span>
      <button
       type="button"
       class="picker-tag-remove"
       @click.stop="remove(id)"
       title="إزالة"
      >
       <i class="bi bi-x"></i>
      </button>
     </span>
    </template>
   </div>

   <!-- Hidden inputs for form synchronization -->
   <template x-for="id in selected" :key="'input-' + id">
    <input type="hidden" name="user_ids[]" :value="id">
   </template>

   <!-- Filterable Scrollable User Grid -->
   @if ($users->isNotEmpty())
    <div class="picker-users-grid">
     <template x-for="user in filteredUsers" :key="user.id">
      <div
       class="picker-user-card"
       :class="{ 'selected': isSelected(user.id) }"
       @click="toggle(user.id)"
      >
       <div class="picker-avatar" x-text="user.initials"></div>
       <div class="picker-user-info">
        <div class="picker-user-name" x-text="user.name"></div>
        <div class="picker-user-role" x-text="user.role"></div>
       </div>
       <div class="picker-check-indicator">
        <i class="bi bi-check-lg" x-show="isSelected(user.id)"></i>
       </div>
      </div>
     </template>
    </div>

    <div
     x-show="filteredUsers.length === 0"
     style="padding: 24px; text-align: center; color: var(--muted); font-size: 13px;"
    >
     <i class="bi bi-search" style="font-size: 20px; display: block; margin-bottom: 6px;"></i>
     لا يوجد مستخدم مطابق للبحث "<span x-text="search"></span>"
    </div>
   @else
    <div class="campaign-empty-users" style="padding: 24px; text-align: center; color: var(--muted);">
     {{ __('crm.no_active_users_to_assign') }}
    </div>
   @endif

   <!-- NoScript fallback for search engine or disabled JS -->
   <noscript>
    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 15px;">
     @foreach ($users as $user)
      <label style="display: flex; align-items: center; gap: 8px; padding: 10px; border: 1px solid var(--line); border-radius: 8px;">
       <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" @checked($selectedUserIds->contains((int) $user->id))>
       <span>{{ $user->name }}</span>
      </label>
     @endforeach
    </div>
   </noscript>

   @error('user_ids')
    <span class="help" style="color:var(--red); display:block; margin-top:10px;">{{ $message }}</span>
   @enderror
   @error('user_ids.*')
    <span class="help" style="color:var(--red); display:block; margin-top:10px;">{{ $message }}</span>
   @enderror
  </div>
 </section>

 <!-- Bottom Action Bar -->
 <div class="campaign-actions-bar">
  <button class="btn primary" type="submit" @disabled($users->isEmpty())>
   <i class="bi bi-check-circle"></i>
   {{ ($editing ?? false) ? __('crm.save_changes') : __('crm.create_campaign') }}
  </button>
  <a
   class="btn soft"
   href="{{ ($editing ?? false) ? route('v2.campaigns.show', $campaign) : route('v2.campaigns.index') }}"
  >
   {{ __('crm.cancel') }}
  </a>
 </div>
</form>
@endsection

@push('scripts')
<script>
 if (typeof window.Alpine === 'undefined') {
  const alpineScript = document.createElement('script');
  alpineScript.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js';
  alpineScript.defer = true;
  document.head.appendChild(alpineScript);
 }

 function campaignUserPicker(allUsers, initialSelected) {
  return {
   search: '',
   users: allUsers || [],
   selected: (initialSelected || []).map(Number),
   get filteredUsers() {
    if (!this.search || !this.search.trim()) {
     return this.users;
    }
    const q = this.search.toLowerCase().trim();
    return this.users.filter(u =>
     (u.name && u.name.toLowerCase().includes(q)) ||
     (u.role && u.role.toLowerCase().includes(q))
    );
   },
   isSelected(id) {
    return this.selected.includes(Number(id));
   },
   toggle(id) {
    const numId = Number(id);
    const idx = this.selected.indexOf(numId);
    if (idx > -1) {
     this.selected.splice(idx, 1);
    } else {
     this.selected.push(numId);
    }
   },
   selectAllFiltered() {
    const filteredIds = this.filteredUsers.map(u => Number(u.id));
    const set = new Set([...this.selected, ...filteredIds]);
    this.selected = Array.from(set);
   },
   clearAll() {
    this.selected = [];
   },
   remove(id) {
    const numId = Number(id);
    this.selected = this.selected.filter(item => item !== numId);
   },
   getUser(id) {
    const numId = Number(id);
    return this.users.find(u => Number(u.id) === numId);
   }
  };
 }

 (() => {
  const input = document.getElementById('campaignImage');
  const preview = document.getElementById('campaignImagePreview');

  input?.addEventListener('change', () => {
   const file = input.files?.[0];
   if (!file || !preview) return;

   const image = document.createElement('img');
   image.src = URL.createObjectURL(file);
   image.alt = @json(__('crm.campaign_image_preview'));
   image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });
   preview.replaceChildren(image);
  });
 })();
</script>
@endpush
