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

 @can('leads.export')
 <a
  class="btn soft"
  href="{{ route('v2.leads.export') }}"
 >
  {{ __('↓ تصدير العملاء') }}
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
       {{ __('XLSX أو CSV بحد أقصى 5MB، وحتى 1000 صف في العملية الواحدة.') }}
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
       {{ __('استخدم النموذج للحفاظ على أسماء الأعمدة وصيغة رقم الهاتف كنص.') }}
      </span>
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

    <div class="table-wrap">
     <table>
      <thead>
       <tr>
        <th>{{ __('crm.row') }}</th>
        <th>{{ __('crm.client') }}</th>
        <th>{{ __('crm.phone') }}</th>
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
          {{ $row['phone'] }}
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
  const confirmForm =
   document.getElementById(
    'confirmImportForm'
   );

  confirmForm?.addEventListener(
   'submit',
   (event) => {
    const confirmed =
     window.confirm(
      '{{ __('سيتم الآن إضافة العملاء الصالحة فقط إلى CRM. العملاء المكررة أو الصفوف التي بها أخطاء لن تتم إضافتها. هل تريد المتابعة؟') }}'
     );

    if (!confirmed) {
     event.preventDefault();
    }
   }
  );
 })();
</script>
@endpush
