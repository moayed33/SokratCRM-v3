@extends('leads.transfer-layout')

@section('title', __('crm.export_leads'))
@section('page-title', __('crm.export_leads'))
@section(
 'page-description',
 __('حدد نطاق العملاء والأعمدة ثم نزّل ملف Excel.')
)

@section('top-actions')
 @can('leads.import')
 <a
  class="btn soft"
  href="{{ route('v2.leads.import') }}"
 >
  {{ __('crm.import_leads') }}
 </a>
 @endcan

 <a
  class="btn soft"
  href="{{ route('v2.leads') }}"
 >
  {{ __('crm.view_leads') }}
 </a>
@endsection

@section('content')

 <article class="transfer-card" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
  <div class="transfer-hero">
   <small>{{ __('crm.export_excel_title') }}</small>

   <h2>
    {{ __('crm.prepare_leads_file') }}
   </h2>

   <p>
    {{ __('اختر الفلاتر المطلوبة والأعمدة التي تريد ظهورها في الملف. تصدير العملاء المحددين من شاشة العملاء سيظل يعمل كما هو.') }}
   </p>
  </div>

  <div class="card-body">
   <div class="notice info">
    {{ __('إجمالي العملاء حاليًا:') }}
    <strong>{{ $totalLeads }}</strong>.
    {{ __('الحد الأقصى للتصدير من هذه الشاشة هو 5000 عميل في العملية الواحدة.') }}
   </div>

   @if ($errors->has('export'))
    <div class="notice error">
     {{ $errors->first('export') }}
    </div>
   @endif

   <form
    id="leadExportForm"
    dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}"
    method="POST"
    action="{{ route(
     'v2.leads.export.download'
    ) }}"
   >
    @csrf

    <div class="grid three">
     <div class="field">
      <label for="stageId">
       {{ __('المرحلة') }}
      </label>

      <select
       class="control"
       id="stageId"
       name="stage_id"
      >
       <option value="">
        {{ __('كل المراحل') }}
       </option>

       @foreach ($stages as $stage)
        <option
         value="{{ $stage->id }}"
         @selected(
          (string) old('stage_id')
          === (string) $stage->id
         )
        >
         {{ __($stage->name_ar) }}
        </option>
       @endforeach
      </select>
     </div>

     <div class="field">
      <label for="statusId">
       {{ __('crm.status') }}
      </label>

      <select
       class="control"
       id="statusId"
       name="status_id"
      >
       <option value="">
        {{ __('crm.all_states') }}
       </option>

       @foreach ($statuses as $status)
        <option
         value="{{ $status->id }}"
         @selected(
          (string) old('status_id')
          === (string) $status->id
         )
        >
         {{
          $status->stage?->name_ar
          ? __($status->stage->name_ar)
          : '----'
         }}
         —
         {{ __($status->name_ar) }}
        </option>
       @endforeach
      </select>
     </div>

     <div class="field">
      <label for="employee">
       {{ __('crm.responsible_employee') }}
      </label>

      <select
       class="control"
       id="employee"
       name="employee"
      >
       <option value="">
        {{ __('كل الموظفين') }}
       </option>

       @foreach (
        $employees
        as $employee
       )
        <option
         value="{{ $employee }}"
         @selected(
          old('employee')
          === $employee
         )
        >
         {{ $employee }}
        </option>
       @endforeach
      </select>
     </div>

     <div class="field">
      <label for="source">
       {{ __('crm.source') }}
      </label>

      <select
       class="control"
       id="source"
       name="source"
      >
       <option value="">
        {{ __('كل المصادر') }}
       </option>

       @foreach ($sources as $source)
        <option
         value="{{ $source }}"
         @selected(
          old('source')
          === $source
         )
        >
         {{ $source }}
        </option>
       @endforeach
      </select>
     </div>

     <div class="field">
      <label for="dateFrom">
       {{ __('تاريخ الإضافة من') }}
      </label>

      <input
       class="control"
       id="dateFrom"
       type="date"
       name="date_from"
       value="{{ old('date_from') }}"
      >
     </div>

     <div class="field">
      <label for="dateTo">
       {{ __('تاريخ الإضافة إلى') }}
      </label>

      <input
       class="control"
       id="dateTo"
       type="date"
       name="date_to"
       value="{{ old('date_to') }}"
      >
     </div>
    </div>

    <div
     style="
      margin-top:22px;
      padding-top:20px;
      border-top:1px solid #e4e8ef
     "
    >
     <div class="card-head"
          style="padding:0 0 14px;border:0">
      <div>
       <h3>
        {{ __('crm.excel_columns') }}
       </h3>

       <p>
        {{ __('crm.choose_export_data') }}
       </p>
      </div>

      <div style="display:flex;gap:7px">
       <button
        class="btn soft"
        id="selectAllColumns"
        type="button"
       >
        {{ __('crm.select_all') }}
       </button>

       <button
        class="btn soft"
        id="clearColumns"
        type="button"
       >
        {{ __('crm.deselect_all') }}
       </button>
      </div>
     </div>

     <div class="checkbox-grid">
      @foreach (
       $columns
       as $key => $label
      )
       <label class="check-item">
        <input
         class="export-column-checkbox"
         type="checkbox"
         name="columns[]"
         value="{{ $key }}"
         @checked(
          old('columns') === null
          || in_array(
           $key,
           old('columns', []),
           true
          )
         )
        >
        <span>{{ __($label) }}</span>
       </label>
      @endforeach
     </div>
    </div>

    <div class="actions">
     <button
      class="btn success"
      type="submit"
     >
      ↓ {{ __('تنزيل ملف Excel') }}
     </button>
    </div>
   </form>
  </div>
 </article>

@endsection

@push('scripts')
<script>
 (() => {
  const checkboxes = Array.from(
   document.querySelectorAll(
    '.export-column-checkbox'
   )
  );

  document
   .getElementById(
    'selectAllColumns'
   )
   ?.addEventListener(
    'click',
    () => {
     checkboxes.forEach(
      (checkbox) => {
       checkbox.checked = true;
      }
     );
    }
   );

  document
   .getElementById(
    'clearColumns'
   )
   ?.addEventListener(
    'click',
    () => {
     checkboxes.forEach(
      (checkbox) => {
       checkbox.checked = false;
      }
     );
    }
   );

  document
   .getElementById(
    'leadExportForm'
   )
   ?.addEventListener(
    'submit',
    (event) => {
     if (
      !checkboxes.some(
       (checkbox) => checkbox.checked
      )
     ) {
      event.preventDefault();

      window.alert(
       '{{ __('اختر عمودًا واحدًا على الأقل.') }}'
      );
     }
    }
   );
 })();
</script>
@endpush
