@php
    $crmQuotationReadOnly = isset($quotation);
    $crmQuotationData = $crmQuotationReadOnly ? $quotation->payload : null;
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>{{ $crmQuotationReadOnly ? 'عرض سعر محفوظ' : 'إنشاء عرض سعر' }} | CRM v2</title>
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <link rel="stylesheet" href="{{ asset('quotation-generator/styles.css') }}?v=quotation-v36" />
  <link rel="stylesheet" href="{{ asset('quotation-generator/crm-module.css') }}?v=crm-module-no-sidebar-v5" />
</head>
<body>
@include('partials.page-loader')
  <!-- CRM QUOTATION SHARED SIDEBAR V1 START -->
  <div class="crm-quote-shell">
   @include('partials.crm-sidebar')
   <div class="crm-quote-main">
  <!-- CRM QUOTATION SHARED SIDEBAR V1 END -->
  <div class="app-shell">
    <aside class="builder no-print">
      <div class="builder-scroll">
      <div class="builder-head">
        <div>
          <span class="eyebrow">Sokrat Pro Tech</span>
          <h1>{{ __('crm.quotation_builder') }}</h1>
          <p>{{ __('crm.quotation_builder_subtitle') }}</p>
        </div>
        <div class="head-actions">
          <button type="button" id="loadDemoBtn" class="btn ghost">{{ __('crm.load_demo') }}</button>
          <button type="button" id="resetBtn" class="btn ghost danger-text">{{ __('crm.reset') }}</button>
          <button type="button" id="resetLayoutBtn" class="btn ghost" title="{{ __('crm.reset_panel_width_title') }}">{{ __('crm.default_size') }}</button>
        </div>
          @include('partials.profile-dropdown')
      </div>

      <form id="quoteForm" autocomplete="off">
        <section class="panel">
          <h2>بيانات العميل</h2>
          <div class="grid two">
            <label>
              <span>{{ __('crm.client_company_name') }}</span>
              <input id="clientName" type="text" placeholder="{{ __('crm.client_name_example') }}" />
            </label>
            <label>
              <span>{{ __('crm.location') }}</span>
              <input id="location" type="text" placeholder="{{ __('crm.location_example') }}" />
            </label>
            <label>
              <span>{{ __('crm.prepared_by') }}</span>
              <input id="preparedBy" type="text" value="احمد حمدي" />
            </label>
            <label>
              <span>{{ __('crm.quotation_number') }}</span>
              <input id="quotationNo" type="text" placeholder="{{ __('crm.quotation_number_example') }}" />
            </label>
            <label>
              <span>{{ __('crm.date') }}</span>
              <input id="quoteDate" type="date" />
            </label>
            <label>
              <span>{{ __('crm.quotation_system_title') }}</span>
              <input id="systemTitle" type="text" value="Call Center System" dir="ltr" />
            </label>
          </div>
          <label>
            <span>{{ __('crm.quotation_description') }}</span>
            <textarea id="proposalDescription" rows="3">اليكم المقايسة الفنية و المالية لتوريد و تشغيل برنامج أدارة الكول سنتر
توصلنا الى ان متطلبات العمل لديكم فيما يخص الكول سنتر تتركز فى اعمال أستقبال المكالمات و الاتصال بالعملاء و التواصل معهم من داخل مقركم او من خارج مقركم
تم اختيار استخدام اجهزة الكمبيوتر المتوفرة لديكم لاستقبال و الاتصال بالعملاء</textarea>
          </label>
        </section>

        <section class="panel">
          <div class="panel-title-row">
            <h2>{{ __('crm.products_items') }}</h2>
            <div class="panel-action-group">
              <button type="button" id="addAccessoryBtn" class="btn small accessory-btn">{{ __('crm.add_accessory') }}</button>
              <button type="button" id="addItemBtn" class="btn small">{{ __('crm.add_financial_item') }}</button>
            </div>
          </div>
          <p class="helper">يمكنك تحديد ظهور كل منتج في <strong>المقايسة المالية</strong> أو في <strong>الملحقات فقط</strong>. البنود غير المحددة للمقايسة المالية لا تدخل في الإجمالي العام.</p>
          <div class="items-head desktop-only">
            <span>{{ __('crm.item') }}</span><span>{{ __('crm.quantity') }}</span><span>{{ __('crm.unit_price') }}</span><span>{{ __('crm.unit') }}</span><span>{{ __('crm.total') }}</span><span>{{ __('crm.estimate') }}</span><span>{{ __('crm.accessories') }}</span><span></span>
          </div>
          <div id="itemsEditor" class="items-editor"></div>
          <div class="grand-total-card subtotal-card">
            <span>{{ __('crm.items_total') }}</span>
            <strong><span id="formSubtotal">0</span> {{ __('crm.pound') }}</strong>
          </div>

          <div class="adjustments-editor-block">
            <div class="panel-title-row adjustments-title-row">
              <div>
                <h3>{{ __('crm.rows_after_total') }}</h3>
                <p class="helper">{{ __('crm.adjustment_hint') }}</p>
              </div>
              <button type="button" id="addAdjustmentBtn" class="btn small">{{ __('crm.add_row') }}</button>
            </div>
            <div id="adjustmentsEditor" class="adjustments-editor"></div>
          </div>

          <div class="grand-total-card final-total-card">
            <span>{{ __('crm.grand_total') }}</span>
            <strong><span id="formGrandTotal">0</span> {{ __('crm.pound') }}</strong>
          </div>
          <label class="financial-note-editor">
            <span>{{ __('crm.financial_note') }}</span>
            <textarea id="financialNote" rows="4" placeholder="{{ __('crm.financial_note_placeholder') }}">تم الاتفاق على عمل عرض خاص لكم بإجمالي {total} جنيه مصري فقط لا غير
ويتم العمل بهذا العرض حسب الاتفاق</textarea>
            <small class="helper">يمكنك تعديل النص بالكامل أو تركه فارغًا. استخدم <strong>{subtotal}</strong> لإجمالي البنود، و<strong>{total}</strong> للإجمالي النهائي بعد الضريبة/الخدمات/الخصومات.</small>
          </label>
        </section>

        <section class="panel typography-panel">
          <div class="panel-title-row">
            <h2>{{ __('crm.quotation_typography') }}</h2>
            <button type="button" id="resetTypographyBtn" class="btn ghost small">{{ __('crm.restore_default') }}</button>
          </div>
          <p class="helper">{{ __('crm.typography_hint') }}</p>
          <div class="grid two typography-grid">
            <label>
              <span>{{ __('crm.font_type') }}</span>
              <select id="quoteFontFamily">
                <option value="arial">Arial</option>
                <option value="tahoma">Tahoma</option>
                <option value="traditional">Traditional Arabic</option>
                <option value="simplified">Simplified Arabic</option>
                <option value="times">Times New Roman</option>
                <option value="verdana">Verdana</option>
                <option value="georgia">Georgia</option>
              </select>
            </label>
            <label>
              <span>حجم الخط: <strong id="quoteFontSizeValue">100%</strong></span>
              <input id="quoteFontSize" class="font-size-range" type="range" min="85" max="130" step="5" value="100" />
            </label>
            <div class="color-control">
              <span class="field-label">{{ __('crm.text_color') }}</span>
              <div class="color-row">
                <input id="quoteTextColor" type="color" value="#111111" disabled aria-label="{{ __('crm.text_color') }}" />
                <label class="inline-check"><input id="enableTextColor" type="checkbox" /> <span>{{ __('crm.enable_custom_color') }}</span></label>
              </div>
            </div>
            <div class="color-control">
              <span class="field-label">{{ __('crm.heading_color') }}</span>
              <div class="color-row">
                <input id="quoteAccentColor" type="color" value="#e10d0d" disabled aria-label="{{ __('crm.heading_color') }}" />
                <label class="inline-check"><input id="enableAccentColor" type="checkbox" /> <span>{{ __('crm.enable_custom_color') }}</span></label>
              </div>
            </div>
          </div>
        </section>

        <section class="panel">
          <h2>{{ __('crm.optional_sections') }}</h2>
          <div class="switch-grid">
            <label class="checkline"><input id="includeProducts" type="checkbox" checked /> <span>{{ __('crm.product_accessory_pages') }}</span></label>
            <label class="checkline"><input id="includeTechnical" type="checkbox" checked /> <span>{{ __('crm.technical_estimate') }}</span></label>
            <label class="checkline"><input id="includeTerms" type="checkbox" checked /> <span>{{ __('crm.terms_agreements') }}</span></label>
            <label class="checkline"><input id="includeFeatures" type="checkbox" checked /> <span>{{ __('crm.call_center_features') }}</span></label>
          </div>
        </section>

        <section class="panel collapsible open">
          <button class="collapse-trigger" type="button" data-target="technicalEditor">
            <span>{{ __('crm.technical_estimate') }}</span><span>⌄</span>
          </button>
          <div id="technicalEditor" class="collapse-body">
            <textarea id="technicalScope" rows="12">1- نقوم بكافة اعمال تركيب و تشغيل نظام الكول سنتر و التاكد من عمله بمنتهى الكفاءة و تسليمة للمسؤل لديكم
2- نقوم بتدريب الموظفين المختصين كل فى حدود صلاحياته و التدريب لدينا عدد 2 زيارة ميدانية و الدعم الفنى online عن طريق الهاتف او الواتس او الايميل
3- الدعم الفنى online خلال مواعيد العمل الرسمية لدينا من التاسعه صباحا الى الخامسة مساء كل يوم ماعدا الجمعه و السبت و ما عدا الاجازات الرسمية
4- الزيارات الميدانية تتم بموعد مسبق خلال 48 ساعه من الاتفاق عليها بتكلفة تحدد وقتها خلال أيام العمل
5- الدعم الفنى online لمدة سنة من تاريخ تسليمكم الكول سنتر مجانا و حال رغبتكم التجديد يتم احتساب التجديد ب 30% من قيمة التعاقد
6- يقوم العميل بتوفير جهاز كمبيوتر ليعمل ك سيرفر او نقوم بالتوريد و الاتفاق على السعر حسب المواصفة التى تناسب طبيعة العمل
7- لكل خط تم توريده عدد 1 مشتركى VPN يتم عمل التجديد السنوى لل VPN اما بتجديد اشتراك الدعم الفنى 30% من قيمة التعاقد او الاشتراك على باقة من باقات VPN حسب السعر</textarea>
          </div>
        </section>

        <section class="panel collapsible open">
          <button class="collapse-trigger" type="button" data-target="termsEditor">
            <span>{{ __('crm.terms_agreements') }}</span><span>⌄</span>
          </button>
          <div id="termsEditor" class="collapse-body">
            <textarea id="terms" rows="5">- الاسعار لا تشمل ضريبة القيمة المضافه .
- يتم دفع 50% عند الاتفاق و يتم توريد و تركيب النظام خلال 48 ساعه (( أيام عمل )) و يتم تحصيل المتبقى عند التسليم .</textarea>
          </div>
        </section>

        <section class="panel collapsible">
          <button class="collapse-trigger" type="button" data-target="featuresEditor">
            <span>مميزات نظام الكول سنتر</span><span>⌄</span>
          </button>
          <div id="featuresEditor" class="collapse-body">
            <textarea id="features" rows="12">- الرسالة المسجلة التفاعلية.
- تسجيل ومراقبة المكالمات.
- تقارير تفصيلية للمكالمات.
- توزيع المكالمات بشكل اوتوماتيكي.
- تقارير كاملة عن سجل المكالمات.
- التحكم في المكالمات خارج مواعيد العمل.
- تحويل المكالمات بين الفروع.
- تطبيق موبايل لاستقبال وارسال المكالمات خارج العمل.
- يدعم الهوت لاين.
- التواصل مع الموظفين داخل وخارج مقر العمل.
- توجية المكالمات للموظفين علي smartphones في اي مكان.
- تدعيم كامل للعمل خارج نطاق الشركة.</textarea>
          </div>
        </section>
      </form>
      </div>

      <div class="sticky-actions">
        @if (!$crmQuotationReadOnly)
         <button type="button" id="crmSaveQuotationBtn" class="btn primary">{{ __('crm.save_quotation') }}</button>
        @endif
        <button type="button" id="previewBtn" class="btn secondary">{{ __('crm.preview_quotation') }}</button>
        <button type="button" id="printBtn" class="btn primary">{{ __('crm.create_print_save_pdf') }}</button>
        <button type="button" id="wordBtn" class="btn word">{{ __('crm.export_word') }}</button>
        <span id="crmQuotationSaveStatus" aria-live="polite"></span>
      </div>
    </aside>

    <div id="builderResizer" class="builder-resizer no-print" role="separator" aria-orientation="vertical" aria-label="{{ __('crm.resize_input_panel') }}" title="{{ __('crm.resize_input_hint') }}">
      <span class="resizer-grip" aria-hidden="true"></span>
    </div>

    <main class="preview-stage">
      <div class="preview-toolbar no-print">
        <div>
          <strong>{{ __('crm.preview_quotation') }}</strong>
          <span id="pageCount">{{ __('crm.page_count_zero') }}</span>
        </div>
        <div class="preview-toolbar-actions">
          <button type="button" id="wordTopBtn" class="btn word small">{{ __('crm.export_word') }}</button>
          <button type="button" id="printTopBtn" class="btn primary small">طباعة / حفظ PDF</button>
        </div>
      </div>
      <div id="quotePreview" class="quote-preview"></div>
    </main>
  </div>

  <template id="itemRowTemplate">
    <div class="item-row">
      <div class="item-main">
        <label class="mobile-label">{{ __('crm.item') }}</label>
        <select class="item-product"></select>
        <input class="item-custom-name" type="text" placeholder="{{ __('crm.custom_item_name') }}" hidden />
        <input class="item-description" type="text" placeholder="{{ __('crm.short_description_optional') }}" />
        <label class="image-upload-label">
          <span>{{ __('crm.custom_image') }}</span>
          <input class="item-image" type="file" accept="image/*" />
        </label>
      </div>
      <div><label class="mobile-label">{{ __('crm.quantity') }}</label><input class="item-qty" type="number" min="0" step="1" value="1" /></div>
      <div><label class="mobile-label">{{ __('crm.unit_price') }}</label><input class="item-price" type="number" min="0" step="0.01" value="0" /></div>
      <div><label class="mobile-label">{{ __('crm.unit') }}</label><input class="item-unit" type="text" value="قطعه" /></div>
      <div class="item-total-cell"><label class="mobile-label">{{ __('crm.total') }}</label><strong class="item-total">0</strong></div>
      <div class="financial-inclusion-cell"><label class="mobile-label">{{ __('crm.in_financial_estimate') }}</label><input class="item-in-financial" type="checkbox" checked title="{{ __('crm.include_in_estimate_title') }}" /></div>
      <div class="product-page-cell"><label class="mobile-label">{{ __('crm.in_accessories') }}</label><input class="item-show-product" type="checkbox" title="{{ __('crm.show_accessory_title') }}" /></div>
      <div><button type="button" class="remove-item" title="{{ __('crm.delete_item') }}">×</button></div>
    </div>
  </template>

  <template id="adjustmentRowTemplate">
    <div class="adjustment-editor-row">
      <div class="adjustment-main">
        <label class="mobile-label">{{ __('crm.row_name') }}</label>
        <input class="adjustment-label" type="text" placeholder="{{ __('crm.adjustment_example') }}" />
      </div>
      <div>
        <label class="mobile-label">{{ __('crm.operation') }}</label>
        <select class="adjustment-operation">
          <option value="add">{{ __('crm.add_operation') }}</option>
          <option value="subtract">{{ __('crm.subtract_operation') }}</option>
        </select>
      </div>
      <div>
        <label class="mobile-label">{{ __('crm.value_type') }}</label>
        <select class="adjustment-mode">
          <option value="amount">{{ __('crm.amount') }}</option>
          <option value="percent">{{ __('crm.percentage') }}</option>
        </select>
      </div>
      <div>
        <label class="mobile-label">{{ __('crm.value') }}</label>
        <input class="adjustment-value" type="number" min="0" step="0.01" value="0" />
      </div>
      <div class="adjustment-calculated-cell">
        <label class="mobile-label">{{ __('crm.calculated_value') }}</label>
        <strong class="adjustment-calculated">0</strong>
      </div>
      <button type="button" class="remove-adjustment" title="{{ __('crm.delete_row') }}">×</button>
    </div>
  </template>

  <script>
   window.SOKRAT_QUOTE_ASSET_BASE = @json(asset('quotation-generator/assets'));
   window.CRM_QUOTATION_READ_ONLY = @json($crmQuotationReadOnly);
   window.CRM_QUOTATION_DATA = @json($crmQuotationData);
   window.CRM_QUOTATION_STORE_URL = @json(route('v2.quotations.store'));
  </script>
  <script src="{{ asset('quotation-generator/script.js') }}?v=quotation-v36"></script>
  <script src="{{ asset('quotation-generator/crm-sidebar.js') }}"></script>
  <script src="{{ asset('quotation-generator/crm-integration.js') }}"></script>
   </div>
  </div>
</body>
</html>
