<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
 <meta charset="utf-8">
 <meta
  name="viewport"
  content="width=device-width,initial-scale=1"
 >
 <title>{{ __('crm.quotations') }} | CRM v2</title>
 <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

 <link
  rel="stylesheet"
  href="{{ asset('quotation-generator/crm-module.css') }}?v=crm-module-no-sidebar-v5"
 >
</head>
<body>
@include('partials.page-loader')
 <!-- CRM QUOTATION SHARED SIDEBAR V1 START -->
 <div class="crm-list-layout">

  @include('partials.crm-sidebar')

  <main class="crm-list-main">
 <!-- CRM QUOTATION SHARED SIDEBAR V1 END -->

   <header class="crm-list-head">
    <div>
     <h1>{{ __('crm.quotations') }}</h1>
     <p>
      {{ __('crm.saved_quotations_subtitle') }}
     </p>
    </div>

    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
     @can('quotations.create')
     <a
      class="crm-qbtn"
      href="{{ route('v2.quotations.create') }}"
     >
      {{ __('crm.create_quotation') }}
     </a>
     @endcan
     @include('partials.profile-dropdown')
    </div>
   </header>

   <form
    class="crm-qsearch"
    method="get"
    action="{{ route('v2.quotations.index') }}"
   >
    <input
     type="search"
     name="q"
     value="{{ $term }}"
     placeholder="{{ __('crm.quotation_search_placeholder') }}"
    >

    <button
     class="crm-qbtn light"
     type="submit"
    >
     {{ __('crm.search') }}
    </button>

    @if ($term !== '')
     <a
      class="crm-qbtn light"
      href="{{ route('v2.quotations.index') }}"
     >
      {{ __('crm.cancel') }}
     </a>
    @endif
   </form>

   <section class="crm-qcard">

    @if ($quotations->count())

     <table>
      <thead>
       <tr>
        <th>#</th>
        <th>{{ __('crm.quotation_number') }}</th>
        <th>{{ __('crm.client') }}</th>
        <th>{{ __('crm.date') }}</th>
        <th>{{ __('crm.prepared_by') }}</th>
        <th>{{ __('crm.system_title') }}</th>
        <th>{{ __('crm.total') }}</th>
        <th>{{ __('crm.saved_at') }}</th>
        <th>{{ __('crm.action') }}</th>
       </tr>
      </thead>

      <tbody>

       @foreach ($quotations as $quotation)

        <tr>

         <td>
          {{ $quotation->id }}
         </td>

         <td>
          <strong>
           {{ $quotation->quotation_no }}
          </strong>
         </td>

         <td>
          {{ $quotation->client_name }}
         </td>

         <td>
          {{
           $quotation->quote_date
            ? $quotation->quote_date->format('Y-m-d')
            : '—'
          }}
         </td>

         <td>
          {{ $quotation->prepared_by ?: '—' }}
         </td>

         <td>
          {{ $quotation->system_title ?: '—' }}
         </td>

         <td class="crm-qmoney">
          {{
           number_format(
            (float)
            $quotation->grand_total,
            2
           )
          }}
          جنيه
         </td>

         <td>
          {{
           optional(
            $quotation->created_at
           )->format(
            'Y-m-d H:i'
           )
          }}
         </td>

         <td>
          <a
           class="crm-qbtn light"
           href="{{
            route(
             'v2.quotations.show',
             $quotation
            )
           }}"
          >
           {{ __('crm.open_print') }}
          </a>
         </td>

        </tr>

       @endforeach

      </tbody>
     </table>

    @else

     <div class="crm-qempty">
      {{ __('crm.no_saved_quotations') }}
     </div>

    @endif

   </section>

   @if ($quotations->hasPages())

    <div class="crm-qpager">

     <span>
      صفحة
      {{ $quotations->currentPage() }}
      من
      {{ $quotations->lastPage() }}
     </span>

     <div class="crm-qpager-actions">

      @if (!$quotations->onFirstPage())
       <a
        class="crm-qbtn light"
        href="{{ $quotations->previousPageUrl() }}"
       >
        {{ __('crm.previous') }}
       </a>
      @endif

      @if ($quotations->hasMorePages())
       <a
        class="crm-qbtn light"
        href="{{ $quotations->nextPageUrl() }}"
       >
        {{ __('crm.next') }}
       </a>
      @endif

     </div>

    </div>

   @endif

  </main>
 </div>

 <script
  src="{{ asset('quotation-generator/crm-sidebar.js') }}"
 ></script>
</body>
</html>
