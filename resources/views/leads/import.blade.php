@extends('leads.transfer-layout')

@section(
 'title',
 isset($campaign)
  ? __('استيراد عملاء - ').$campaign->name
  : __('crm.import_leads')
)
@section(
 'page-title',
 isset($campaign)
  ? __('استيراد عملاء حملة ').$campaign->name
  : __('crm.import_leads')
)
@section(
 'page-description',
 isset($campaign)
  ? __('ارفع ملف الحملة وفق قواعد استيراد العملاء، ثم راجع المعاينة وأكدها.')
  : __('ارفع Excel أو CSV، راجع المعاينة، ثم أكد الاستيراد.')
)

@section('top-actions')
 @isset($campaign)
  <a
   class="btn soft"
   href="{{ route('v2.campaigns.show', $campaign) }}"
  >
   {{ __('العودة للحملة') }}
  </a>
 @endisset
@endsection

@section('content')

 <article class="transfer-card" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
  <div class="transfer-hero">
   <small>{{ __('crm.safe_import') }}</small>

   <h2>
    {{ __('crm.add_leads_from_file') }}
   </h2>

   <p>
    {{ __('لن يتم إضافة أي عميل بمجرد رفع الملف. ستظهر معاينة كاملة أولًا، ويتم الاستيراد فقط بعد الضغط على تأكيد الاستيراد. رقم الهاتف هو معيار منع التكرار.') }}
   </p>
  </div>

  <div class="card-body">
   <div class="notice info">
    {{ __('الحالة في الملف هي الحالة الابتدائية للعميل، ومنها يحدد النظام مرحلة المتابعة تلقائيًا. لا يتم إنشاء متابعة وهمية في سجل المتابعات.') }}
   </div>

   <form
    id="leadImportForm"
    dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}"
    method="POST"
    action="{{ route(
     'v2.leads.import.preview'
    ) }}"
    enctype="multipart/form-data"
   >
    @csrf

    @isset($campaign)
     <input
      type="hidden"
      name="campaign_id"
      value="{{ $campaign->id }}"
     >
    @endisset

    <div class="grid">
     <div class="field">
      <label for="importFile">
       {{ __('crm.lead_file') }}
      </label>

      <input
       class="control"
       id="importFile"
       type="file"
       name="import_file"
       accept=".xlsx,.csv"
       required
      >

      <span class="help">
       {{ __('XLSX أو CSV بحد أقصى 30MB، وحتى 10,000 صف في العملية الواحدة.') }}
      </span>
     </div>

     <div class="field">
      <label>
       {{ __('crm.ready_template') }}
      </label>

      <a
       class="btn soft"
       href="{{ route(
        'v2.leads.import.template'
       ) }}"
      >
       {{ __('crm.download_template') }}
      </a>

      <span class="help">
       {{ __('النموذج يحتوي على: اسم العميل / المتبرع، رقم الهاتف الأساسي، وأرقام هواتف إضافية (مفصولة بفواصل).') }}
      </span>
     </div>
    </div>
    <div class="distribution-config-card" style="margin-top: 18px; padding: 18px; background: var(--bg-hover, #f8fafc); border: 1px solid var(--line, #e2e8f0); border-radius: 14px;">
     <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
      <div>
       <h4 style="margin: 0; font-size: 15px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
        <i class="bi bi-people-fill" style="color: var(--blue, #3478f6); font-size: 18px;"></i>
        <span>{{ __('خطة توزيع العملاء على الموظفين') }}</span>
       </h4>
       <p style="margin: 4px 0 0; font-size: 12px; color: var(--muted, #64748b);">
        {{ __('حدد كيفية تعيين الموظف المسؤول للعملاء المستوردين قبل رفع الملف') }}
       </p>
      </div>
     </div>

     <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 16px;">
      <label class="distribution-mode-option" style="display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; background: var(--card, #fff); border: 2px solid var(--line, #e2e8f0); border-radius: 12px; cursor: pointer;">
       <input type="radio" name="distribution_mode" value="round_robin" {{ (old('distribution_mode', $selectedDistributionMode ?? 'round_robin') === 'round_robin') ? 'checked' : '' }} style="margin-top: 3px;" onchange="toggleDistributionOptions()">
       <div>
        <strong style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--dark, #182033);">
         <i class="bi bi-arrow-repeat" style="color: var(--blue, #3478f6);"></i>
         {{ __('توزيع بالتساوي (Round-Robin)') }}
        </strong>
        <small style="color: var(--muted, #64748b); font-size: 11px; line-height: 1.4; display: block; margin-top: 3px;">
         {{ __('تقسيم العملاء بالتساوي على الموظفين المحددين دورياً.') }}
        </small>
       </div>
      </label>

      <label class="distribution-mode-option" style="display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; background: var(--card, #fff); border: 2px solid var(--line, #e2e8f0); border-radius: 12px; cursor: pointer;">
       <input type="radio" name="distribution_mode" value="workload" {{ (old('distribution_mode', $selectedDistributionMode ?? '') === 'workload') ? 'checked' : '' }} style="margin-top: 3px;" onchange="toggleDistributionOptions()">
       <div>
        <strong style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--dark, #182033);">
         <i class="bi bi-bar-chart-steps" style="color: #16a34a;"></i>
         {{ __('موازنة العبء (الأقل عملاء أولاً)') }}
        </strong>
        <small style="color: var(--muted, #64748b); font-size: 11px; line-height: 1.4; display: block; margin-top: 3px;">
         {{ __('إسناد العملاء للموظفين الأقل عملاء نشطين حالياً لتحقيق التكافؤ.') }}
        </small>
       </div>
      </label>

      <label class="distribution-mode-option" style="display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; background: var(--card, #fff); border: 2px solid var(--line, #e2e8f0); border-radius: 12px; cursor: pointer;">
       <input type="radio" name="distribution_mode" value="single" {{ (old('distribution_mode', $selectedDistributionMode ?? '') === 'single') ? 'checked' : '' }} style="margin-top: 3px;" onchange="toggleDistributionOptions()">
       <div>
        <strong style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--dark, #182033);">
         <i class="bi bi-person-check-fill" style="color: #8b5cf6;"></i>
         {{ __('إسناد لموظف واحد') }}
        </strong>
        <small style="color: var(--muted, #64748b); font-size: 11px; line-height: 1.4; display: block; margin-top: 3px;">
         {{ __('إسناد 100% من عملاء الملف لموظف محدد بالكامل.') }}
        </small>
       </div>
      </label>
     </div>

     <div id="singleAgentConfig" style="display: none; padding-top: 14px; border-top: 1px dashed var(--line, #e2e8f0); margin-bottom: 10px;">
      <label style="display: block; font-size: 12px; font-weight: 700; color: var(--dark, #182033); margin-bottom: 6px;">
       {{ __('اختر الموظف المسؤول عن جميع العملاء:') }}
      </label>
      <select name="single_user_id" class="control" style="max-width: 380px;">
       @if(isset($assignableUsers))
        @foreach($assignableUsers as $u)
         <option value="{{ $u->id }}" {{ (old('single_user_id', $selectedSingleUserId ?? auth()->id()) == $u->id) ? 'selected' : '' }}>
          {{ $u->name }} ({{ $activeLeadsCounts[$u->id] ?? 0 }} {{ __('عميل نشط') }})
         </option>
        @endforeach
       @endif
      </select>
     </div>

     <div id="multiAgentConfig" style="padding-top: 14px; border-top: 1px dashed var(--line, #e2e8f0);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
       <label style="font-size: 12px; font-weight: 700; color: var(--dark, #182033);">
        {{ __('اختر الموظفين المشاركين في التوزيع:') }}
       </label>
       <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
        <button type="button" class="btn soft" style="padding: 3px 8px; font-size: 11px; min-height: 26px;" onclick="selectAllEmployees(true)">{{ __('تحديد الكل') }}</button>
        <button type="button" class="btn soft" style="padding: 3px 8px; font-size: 11px; min-height: 26px;" onclick="selectAllEmployees(false)">{{ __('إلغاء التحديد') }}</button>
        @if(isset($groups) && $groups->isNotEmpty())
         <span style="border-inline-start: 1px solid var(--line, #e2e8f0); height: 16px; margin: 0 4px;"></span>
         @foreach($groups as $grp)
          <button type="button" class="btn soft" style="padding: 3px 8px; font-size: 11px; min-height: 26px;" onclick="selectGroupEmployees({{ $grp->id }})" title="{{ $grp->name }}">
           {{ $grp->name }}
          </button>
         @endforeach
        @endif
       </div>
      </div>

      <div id="employeeGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 8px; max-height: 220px; overflow-y: auto; padding: 10px; background: var(--card, #fff); border: 1px solid var(--line, #e2e8f0); border-radius: 10px;">
       @if(isset($assignableUsers))
        @foreach($assignableUsers as $u)
         <label class="emp-checkbox-label" data-group-ids="{{ $u->groups->pluck('id')->implode(',') }}" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 7px 10px; border-radius: 8px; background: var(--bg-hover, #f8fafc); border: 1px solid var(--line, #e2e8f0); cursor: pointer; font-size: 12px; transition: background 0.15s;">
          <div style="display: flex; align-items: center; gap: 7px; overflow: hidden;">
           <input type="checkbox" name="distribution_user_ids[]" value="{{ $u->id }}" class="emp-dist-checkbox" onchange="updateSelectedEmployeeCount()" {{ (is_array(old('distribution_user_ids', $selectedDistributionUserIds ?? [])) && in_array($u->id, old('distribution_user_ids', $selectedDistributionUserIds ?? []))) ? 'checked' : '' }}>
           <span style="font-weight: 700; color: var(--dark, #182033); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
            {{ $u->name }}
           </span>
          </div>
          <span class="active-leads-pill" style="font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 6px; background: rgba(52, 120, 246, 0.08); color: var(--blue, #3478f6); white-space: nowrap;">
           {{ $activeLeadsCounts[$u->id] ?? 0 }} {{ __('عميل') }}
          </span>
         </label>
        @endforeach
       @endif
      </div>
      <div id="selectedCountMsg" style="margin-top: 8px; font-size: 12px; color: var(--blue, #3478f6); font-weight: 700;"></div>
     </div>
    </div>
    <div class="actions">
     <button
      class="btn primary"
      type="submit"
     >
      {{ __('crm.preview_before_import') }}
     </button>
    </div>
   </form>
  </div>
 </article>

 <article class="transfer-card" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
  <div class="card-head">
   <div>
    <h3>
     {{ __('crm.available_statuses') }}
    </h3>

    <p>
     {{ __('يمكنك كتابة الاسم العربي أو كود الحالة.') }}
    </p>
   </div>
  </div>

  <div class="card-body">
   <div class="status-guide">
    @foreach ($statuses as $status)
     <div class="status-guide-item">
      <strong>
       {{ $status->name_ar }}
      </strong>

      <small>
       {{ __('المرحلة:') }}
       {{ $status->stage?->name_ar ? __($status->stage->name_ar) : '----' }}
       ·
       {{ __('الكود:') }}
       {{ $status->code }}
      </small>
     </div>
    @endforeach
   </div>

   <div
    class="notice info"
    style="margin-top:16px;margin-bottom:0"
   >
    {{ __('لو خانة الحالة فارغة، سيتم اعتبار العميل بحالة') }}
    <strong>{{ __('جديد') }}</strong>.
   </div>
  </div>
 </article>

 @if ($preview)
  <section
   class="transfer-card"
   data-import-preview
  >
   <div class="card-head">
    <div>
     <h3>
      {{ __('crm.import_preview') }}
     </h3>

     <p>
      {{ __('راجع الصفوف قبل أي كتابة في قاعدة البيانات.') }}
     </p>
    </div>
   </div>

   <div class="card-body">
    <div class="stat-grid">
     <div class="stat">
      <span>{{ __('crm.total_rows') }}</span>
      <strong>
       {{ $preview['total_count'] }}
      </strong>
     </div>

     <div class="stat">
      <span>{{ __('crm.valid_for_import') }}</span>
      <strong>
       {{ $preview['valid_count'] }}
      </strong>
     </div>

     <div class="stat">
      <span>{{ __('crm.rows_with_errors') }}</span>
      <strong>
       {{ $preview['error_count'] }}
      </strong>
     </div>

     <div class="stat">
      <span>{{ __('crm.duplicate_numbers') }}</span>
      <strong>
       {{ $preview['duplicate_count'] }}
      </strong>
     </div>
    </div>

    @if ($preview['valid_count'] > 0 && $preview['token'])
     <div class="top-confirm-action-bar" style="margin: 18px 0; padding: 14px 18px; background: rgba(22, 163, 74, 0.08); border: 1px solid rgba(22, 163, 74, 0.3); border-radius: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
      <div style="display: flex; align-items: center; gap: 10px;">
       <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: #16a34a; color: #fff; font-size: 16px;">✓</span>
       <div>
        <strong style="display: block; font-size: 14px; color: var(--dark, #182033);">
         {{ __('جاهز للاستيراد الآن:') }} {{ $preview['valid_count'] }} {{ __('عميل صالح') }}
        </strong>

       </div>
      </div>
      <form
       class="confirmImportFormTop"
       method="POST"
       action="{{ route('v2.leads.import.confirm') }}"
      >
       @csrf
       <input type="hidden" name="preview_token" value="{{ $preview['token'] }}">
       <button
        class="btn success"
        type="submit"
        style="height: 42px; padding: 0 20px; font-size: 13px; font-weight: 800; border-radius: 10px; box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35); cursor: pointer;"
       >
        ✓ {{ __('تأكيد استيراد') }} {{ $preview['valid_count'] }} {{ __('عميل') }}
       </button>
      </form>
     </div>
    @endif

    @if (
     !empty(
      $preview['ignored_headers']
     )
    )
     <div class="notice info">
      {{ __('تم تجاهل الأعمدة غير المعروفة:') }}
      {{
       implode(
        app()->getLocale() == 'ar' ? '، ' : ', ',
        $preview['ignored_headers']
       )
      }}
     </div>
    @endif

    @if (!empty($preview['employee_distribution']))
     <div class="distribution-section" style="margin-bottom: 20px;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
       <h4 style="margin: 0; font-size: 14px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
        <i class="bi bi-pie-chart-fill" style="color: var(--blue, #3478f6);"></i>
        <span>{{ __('توزيع العملاء على الموظفين') }}</span>
       </h4>
       <span style="font-size: 12px; color: var(--muted, #64748b);">
        {{ __('عدد الموظفين المسؤولين:') }} <strong>{{ count($preview['employee_distribution']) }}</strong>
       </span>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px;">
       @foreach ($preview['employee_distribution'] as $dist)
        <div style="background: var(--card, #fff); border: 1px solid var(--line, #e2e8f0); border-radius: 12px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
         <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
          <strong style="font-size: 13px; color: var(--dark, #182033); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: flex; align-items: center; gap: 6px;">
           <i class="bi bi-person-circle" style="color: var(--blue, #3478f6); font-size: 15px;"></i>
           {{ $dist['name'] }}
          </strong>
          <span style="font-size: 14px; font-weight: 900; color: var(--blue, #3478f6); white-space: nowrap;">
           {{ $dist['count'] }} <small style="font-weight: 600; font-size: 11px; color: var(--muted, #64748b);">{{ __('عميل') }}</small>
          </span>
         </div>
         <div style="display: flex; align-items: center; gap: 8px;">
          <div style="flex: 1; height: 7px; background: rgba(52, 120, 246, 0.1); border-radius: 999px; overflow: hidden;">
           <div style="width: {{ $dist['percentage'] }}%; height: 100%; background: var(--blue, #3478f6); border-radius: 999px;"></div>
          </div>
          <span style="font-size: 11px; font-weight: 800; color: var(--muted, #64748b); min-width: 38px; text-align: end;">
           {{ $dist['percentage'] }}%
          </span>
         </div>
        </div>
       @endforeach
      </div>
     </div>
    @endif

    <div class="table-wrap">
     <table>
      <thead>
       <tr>
        <th>{{ __('crm.row') }}</th>
        <th>{{ __('crm.client') }}</th>
        <th>{{ __('crm.phone') }}</th>
        <th>{{ __('الموظف المسؤول') }}</th>
        <th>{{ __('crm.status') }}</th>
        <th>{{ __('المرحلة') }}</th>
        <th>{{ __('crm.result') }}</th>
        <th>{{ __('crm.details') }}</th>
       </tr>
      </thead>

      <tbody>
       @foreach (
        $preview['rows']
        as $row
       )
        <tr>
         <td>
          {{ $row['row_number'] }}
         </td>

         <td>
          {{ $row['name'] }}
         </td>

         <td dir="ltr">
          <strong style="color: var(--dark, #182033);">{{ $row['phone'] }}</strong>
          @if (!empty($row['additional_phones']))
           <div style="margin-top: 4px; display: flex; flex-wrap: wrap; gap: 4px;">
            @foreach ($row['additional_phones'] as $extraNum)
             <span class="badge soft" style="font-size: 10px; padding: 1px 6px; background: rgba(52, 120, 246, 0.1); color: var(--blue, #3478f6); border: 1px solid rgba(52, 120, 246, 0.2); border-radius: 6px;" title="{{ __('crm.phone_type_extra') ?? 'رقم إضافي' }}">
              <i class="bi bi-telephone-plus" style="font-size: 8.5px;"></i> {{ $extraNum }}
             </span>
            @endforeach
           </div>
          @endif
         </td>

         <td>
          <span style="display: inline-flex; align-items: center; gap: 5px; font-weight: 700; font-size: 12px; color: var(--dark, #182033);">
           <i class="bi bi-person-badge" style="color: var(--blue, #3478f6);"></i>
           {{ $row['assigned_employee'] ?? '----' }}
          </span>
         </td>

         <td>
          {{ $row['status'] }}
         </td>

         <td>
          {{ $row['stage'] }}
         </td>

         <td>
          @if ($row['state'] === 'valid')
           <span class="badge valid">
            {{ __('crm.valid') }}
           </span>
          @elseif (
           $row['state']
           === 'duplicate'
          )
           <span class="badge duplicate">
            {{ __('crm.duplicate') }}
           </span>
          @else
           <span class="badge error">
            {{ __('crm.error') }}
           </span>
          @endif
         </td>

         <td>
          @if (
           $row['duplicate_reason']
          )
           <div class="badge duplicate">
            {{ $row['duplicate_reason'] }}
           </div>
          @endif

          @if (!empty($row['errors']))
           <ul class="row-errors">
            @foreach (
             $row['errors']
             as $error
            )
             <li>{{ $error }}</li>
            @endforeach
           </ul>
          @endif

          @if (!empty($row['warnings']))
           <ul class="row-warnings">
            @foreach (
             $row['warnings']
             as $warning
            )
             <li>{{ $warning }}</li>
            @endforeach
           </ul>
          @endif

          @if (
           !$row['duplicate_reason']
           && empty($row['errors'])
           && empty($row['warnings'])
          )
           ----
          @endif
         </td>
        </tr>
       @endforeach
      </tbody>
     </table>
    </div>

    @if (
     $preview['valid_count'] > 0
     && $preview['token']
    )
     <form
      id="confirmImportForm"
      method="POST"
      action="{{ route(
       'v2.leads.import.confirm'
      ) }}"
     >
      @csrf

      <input
       type="hidden"
       name="preview_token"
       value="{{ $preview['token'] }}"
      >

      <div class="actions">
       <button
        class="btn success"
        type="submit"
       >
        ✓ {{ __('تأكيد استيراد') }}
        {{ $preview['valid_count'] }}
        {{ __('عميل') }}
       </button>
      </div>
     </form>
    @else
     <div
      class="notice error"
      style="margin-top:16px;margin-bottom:0"
     >
      {{ __('لا توجد صفوف صالحة للاستيراد. صحح الملف ثم ارفعه من جديد.') }}
     </div>
    @endif
   </div>
  </section>
 @endif

@endsection

@push('scripts')
<script>
 (() => {
  document.querySelectorAll('#confirmImportForm, .confirmImportFormTop').forEach(form => {
    form.addEventListener('submit', (event) => {
      const confirmed = window.confirm(
        '{{ __('سيتم الآن إضافة العملاء الصالحة فقط إلى CRM. العملاء المكررة أو الصفوف التي بها أخطاء لن تتم إضافتها. هل تريد المتابعة؟') }}'
      );
      if (!confirmed) {
        event.preventDefault();
      }
    });
  });
 })();

  window.toggleDistributionOptions = function() {
    const mode = document.querySelector('input[name="distribution_mode"]:checked')?.value || 'round_robin';
    const singleDiv = document.getElementById('singleAgentConfig');
    const multiDiv = document.getElementById('multiAgentConfig');

    if (mode === 'single') {
      if (singleDiv) singleDiv.style.display = 'block';
      if (multiDiv) multiDiv.style.display = 'none';
    } else {
      if (singleDiv) singleDiv.style.display = 'none';
      if (multiDiv) multiDiv.style.display = 'block';
    }
    updateSelectedEmployeeCount();
  };

  window.selectAllEmployees = function(select) {
    document.querySelectorAll('.emp-dist-checkbox').forEach(cb => {
      cb.checked = select;
    });
    updateSelectedEmployeeCount();
  };

  window.selectGroupEmployees = function(groupId) {
    document.querySelectorAll('.emp-checkbox-label').forEach(lbl => {
      const groupIds = (lbl.dataset.groupIds || '').split(',').map(id => id.trim());
      const cb = lbl.querySelector('.emp-dist-checkbox');
      if (cb) {
        cb.checked = groupIds.includes(String(groupId));
      }
    });
    updateSelectedEmployeeCount();
  };

  window.updateSelectedEmployeeCount = function() {
    const checked = document.querySelectorAll('.emp-dist-checkbox:checked').length;
    const msg = document.getElementById('selectedCountMsg');
    const mode = document.querySelector('input[name="distribution_mode"]:checked')?.value || 'round_robin';
    if (msg) {
      if (mode === 'single') {
        msg.textContent = '';
      } else {
        msg.textContent = checked > 0 
          ? `تم اختيار ${checked} موظفاً للتوزيع.`
          : 'تنبيه: يجب اختيار موظف واحد على الأقل.';
        msg.style.color = checked > 0 ? 'var(--blue, #3478f6)' : 'var(--red, #dc2637)';
      }
    }
  };
  document.addEventListener('DOMContentLoaded', window.toggleDistributionOptions);
  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    window.toggleDistributionOptions();
  }
</script>
@endpush
