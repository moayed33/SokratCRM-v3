(() => {
 const readOnly =
  Boolean(
   window.CRM_QUOTATION_READ_ONLY
  );

 const savedState =
  window.CRM_QUOTATION_DATA;

 const setValue = (
  control,
  value
 ) => {
  if (control) {
   control.value =
    value ?? '';
  }
 };

 const applySavedState = (
  state
 ) => {
  if (!state) {
   return;
  }

  setValue(
   els.clientName,
   state.clientName
  );

  setValue(
   els.location,
   state.location
  );

  setValue(
   els.preparedBy,
   state.preparedBy
  );

  setValue(
   els.quotationNo,
   state.quotationNo
  );

  setValue(
   els.quoteDate,
   state.quoteDate
  );

  setValue(
   els.systemTitle,
   state.systemTitle
  );

  setValue(
   els.proposalDescription,
   state.proposalDescription
  );

  setValue(
   els.technicalScope,
   state.technicalScope
  );

  setValue(
   els.terms,
   state.terms
  );

  setValue(
   els.features,
   state.features
  );

  if (
   Object.prototype.hasOwnProperty.call(
    state,
    'financialNote'
   )
  ) {
   setValue(
    els.financialNote,
    state.financialNote
   );
  }

  els.includeProducts.checked =
   Boolean(
    state.includeProducts
   );

  els.includeTechnical.checked =
   Boolean(
    state.includeTechnical
   );

  els.includeTerms.checked =
   Boolean(
    state.includeTerms
   );

  els.includeFeatures.checked =
   Boolean(
    state.includeFeatures
   );

  setValue(
   els.quoteFontFamily,
   state.quoteFontFamily
    || 'arial'
  );

  setValue(
   els.quoteFontSize,
   String(
    state.quoteFontSize
     ?? 100
   )
  );

  els.enableTextColor.checked =
   Boolean(
    state.enableTextColor
   );

  setValue(
   els.quoteTextColor,
   state.quoteTextColor
    || '#111111'
  );

  els.enableAccentColor.checked =
   Boolean(
    state.enableAccentColor
   );

  setValue(
   els.quoteAccentColor,
   state.quoteAccentColor
    || '#e10d0d'
  );

  items = [];
  nextItemId = 1;

  const savedItems =
   Array.isArray(
    state.items
   )
    ? state.items
    : [];

  if (savedItems.length) {
   savedItems.forEach(
    (item) =>
     addItem(item)
   );
  } else {
   addItem({
    productId:
     'custom',
    qty:1,
    price:0,
    inFinancial:true,
    showProduct:false
   });
  }

  adjustments = [];
  nextAdjustmentId = 1;

  const savedAdjustments =
   Array.isArray(
    state.adjustments
   )
    ? state.adjustments
    : [];

  if (savedAdjustments.length) {
   savedAdjustments.forEach(
    (adjustment) =>
     addAdjustment(adjustment)
   );
  } else {
   renderAdjustmentsEditor();
  }

  syncTypographyControls();
  updateAll();
 };

 const collectState = () => ({
  clientName:
   els.clientName.value.trim(),

  location:
   els.location.value.trim(),

  preparedBy:
   els.preparedBy.value.trim(),

  quotationNo:
   els.quotationNo.value.trim(),

  quoteDate:
   els.quoteDate.value,

  systemTitle:
   els.systemTitle.value.trim(),

  proposalDescription:
   els.proposalDescription.value,

  technicalScope:
   els.technicalScope.value,

  terms:
   els.terms.value,

  features:
   els.features.value,

  financialNote:
   els.financialNote?.value
    || '',

  includeProducts:
   els.includeProducts.checked,

  includeTechnical:
   els.includeTechnical.checked,

  includeTerms:
   els.includeTerms.checked,

  includeFeatures:
   els.includeFeatures.checked,

  quoteFontFamily:
   els.quoteFontFamily.value,

  quoteFontSize:
   Number(
    els.quoteFontSize.value
     || 100
   ),

  enableTextColor:
   els.enableTextColor.checked,

  quoteTextColor:
   els.quoteTextColor.value,

  enableAccentColor:
   els.enableAccentColor.checked,

  quoteAccentColor:
   els.quoteAccentColor.value,

  items:
   items.map(
    (item) => ({
     productId:
      item.productId
       || 'custom',

     name:
      item.name || '',

     description:
      item.description || '',

     qty:
      Number(
       item.qty || 0
      ),

     price:
      Number(
       item.price || 0
      ),

     unit:
      item.unit
       || 'قطعه',

     image:
      item.image || '',

     inFinancial:
      Boolean(
       item.inFinancial
      ),

     showProduct:
      Boolean(
       item.showProduct
      )
    })
   ),

  adjustments:
   adjustments.map(
    (adjustment) => ({
     label:
      adjustment.label || '',

     operation:
      adjustment.operation === 'subtract'
       ? 'subtract'
       : 'add',

     mode:
      adjustment.mode === 'percent'
       ? 'percent'
       : 'amount',

     value:
      Number(
       adjustment.value || 0
      )
    })
   )
 });

 const saveButton =
  document.getElementById(
   'crmSaveQuotationBtn'
  );

 const saveStatus =
  document.getElementById(
   'crmQuotationSaveStatus'
  );

 const setStatus = (
  text,
  type = ''
 ) => {
  if (!saveStatus) {
   return;
  }

  saveStatus.textContent =
   text;

  saveStatus.className =
   type;
 };

 const firstMessage = (
  payload
 ) => {
  const isEn = (document.documentElement.getAttribute('lang') || '').toLowerCase().startsWith('en');
  const errors =
   payload?.errors;

  if (!errors) {
   return (
    payload?.message
     || (isEn ? 'Failed to save quotation.' : 'تعذر حفظ عرض السعر.')
   );
  }

  const values =
   Object.values(errors);

  if (
   values.length
   && Array.isArray(
    values[0]
   )
  ) {
   return (
    values[0][0]
     || (isEn ? 'Please check required fields.' : 'راجع البيانات المطلوبة.')
   );
  }

  return (
   payload?.message
    || (isEn ? 'Please check required fields.' : 'راجع البيانات المطلوبة.')
  );
 };

 if (saveButton) {
  saveButton.addEventListener(
   'click',
   async () => {
    const isEn = (document.documentElement.getAttribute('lang') || '').toLowerCase().startsWith('en');
    const state =
     collectState();

    if (!state.clientName) {
     setStatus(
      isEn ? 'Client name is required.' : 'اسم العميل مطلوب.',
      'error'
     );

     els.clientName.focus();
     return;
    }
    if (!state.quotationNo) {
     setStatus(
      isEn ? 'Quotation number is required.' : 'رقم عرض السعر مطلوب.',
      'error'
     );

     els.quotationNo.focus();
     return;
    }

    if (!state.quoteDate) {
     setStatus(
      isEn ? 'Quotation date is required.' : 'تاريخ عرض السعر مطلوب.',
      'error'
     );

     els.quoteDate.focus();
     return;
    }

    saveButton.disabled =
     true;

    setStatus(
     isEn ? 'Saving...' : 'جاري الحفظ...'
    );
    try {
     const response =
      await fetch(
       window
        .CRM_QUOTATION_STORE_URL,
       {
        method:'POST',

        headers:{
         Accept:
          'application/json',

         'Content-Type':
          'application/json',

         'X-CSRF-TOKEN':
          document
           .querySelector(
            'meta[name="csrf-token"]'
           )
           .content
        },

        body:
         JSON.stringify(
          state
         )
       }
      );

     const payload =
      await response.json();

     if (!response.ok) {
      throw new Error(
       firstMessage(
        payload
       )
      );
     }

     setStatus(
      isEn ? 'Quotation saved successfully.' : 'تم حفظ عرض السعر.',
      'ok'
     );

     window.location.href =
      payload.redirect;
    } catch (error) {
     setStatus(
      error?.message
       || (isEn ? 'Failed to save quotation.' : 'تعذر حفظ عرض السعر.'),
      'error'
     );

     saveButton.disabled =
      false;
    }
   }
  );
 }

 if (savedState) {
  applySavedState(
   savedState
  );
 }

 if (readOnly) {
  const appShell =
   document.querySelector(
    '.app-shell'
   );

  const builder =
   document.querySelector(
    '.builder'
   );

  const resizer =
   document.getElementById(
    'builderResizer'
   );

  if (appShell) {
   appShell.classList.add(
    'saved-quotation-view'
   );
  }

  if (builder) {
   builder.hidden = true;
  }

  if (resizer) {
   resizer.hidden = true;
  }

  document.title =
   'عرض سعر '
   + (
    savedState?.quotationNo
     || ''
   )
   + ' | CRM v2';
 }
})();
