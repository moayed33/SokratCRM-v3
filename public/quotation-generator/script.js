const ASSET = (name) => `${window.SOKRAT_QUOTE_ASSET_BASE || 'assets'}/${name}`;

const productCatalog = [
  { id: 'custom', name: 'بند مخصص', price: 0, unit: 'قطعه', image: '', description: '' },

  // خدمات وخطوط الكول سنتر
  { id: 'basic-line', name: 'سعر الخط الأساسي', price: 5500, unit: 'قطعه', image: '', description: '' },
  { id: 'extra-line', name: 'سعر خط إضافي', price: 3500, unit: 'قطعه', image: '', description: '' },
  { id: 'special-line-3000', name: 'سعر الخط - عرض 5 خطوط', price: 3000, unit: 'قطعه', image: '', description: 'سعر الخط كما ورد في عرض 5 خطوط' },
  { id: 'landline-programming', name: 'برمجة الخطوط الأرضي', price: 2500, unit: 'قطعه', image: '', description: '' },
  { id: 'gateway-programming', name: 'برمجة الـ Gateway', price: 8000, unit: 'خدمة', image: '', description: 'برمجة وإعداد الـ Gateway' },

  // أجهزة وملحقات الكول سنتر
  { id: 'fxo-card-2-port', name: 'FXO Card 2 Port', price: 5000, unit: 'قطعه', image: '', description: 'FXO card - 2 ports' },
  { id: 'logitech-h340', name: 'Logitech H 340 Mono Headset, Direct USB Cable, Volume Control, 70% Noice Cancellation', price: 1350, unit: 'قطعه', image: ASSET('logitech-h340.png'), description: 'Logitech H340 Mono Headset' },
  { id: 'fiberme-fch7201', name: 'FIBERME FCH7201 Mono USB-A Professional Call Center Headset with Noise-Cancelling Microphone', price: 2050, unit: 'قطعه', image: ASSET('fiberme-fch7201.png'), description: 'Professional call center headset with noise-cancelling microphone' },
  { id: '2b-headset', name: '2B Business USB Headphones with Microphone, Black', price: 1100, unit: 'قطعه', image: ASSET('2b-headset.png'), description: '' },
  { id: 'fap2714p', name: 'FAP2714P Essential IP Phone', price: 1850, unit: 'قطعه', image: ASSET('fap2714p.png'), description: '' },
  { id: 'fap2714w', name: 'FAP2714W Essential IP Phone', price: 2200, unit: 'قطعه', image: ASSET('fap2714w.png'), description: '' },
  { id: 'fag4104', name: 'FAG4104 FXO Gateway, 4 FXO Ports', price: 10200, unit: 'قطعه', image: ASSET('fag4104.png'), description: '' },
  { id: 'fag4108', name: 'FAG4108 FXO Gateway, 8 FXO Ports', price: 17200, unit: 'قطعه', image: ASSET('fag4108.png'), description: '' },
  { id: 'hp-elitedesk', name: 'HP Elite Desk 800 G1 Desktop – PC . i3 -4th 8g ram 256 ssd', price: 5500, unit: 'قطعه', image: ASSET('hp-elitedesk.jpeg'), description: '' },
  { id: 'hp-elitedesk-512-hdd', name: 'HP Elite Desk 800 G1 Desktop – PC . i3 -4th 8g ram 512 HDD', price: 5500, unit: 'قطعه', image: ASSET('hp-elitedesk.jpeg'), description: 'HP EliteDesk 800 G1 - Core i3 4th Gen - 8GB RAM - 512 HDD' },

  // برامج
  { id: 'crm', name: 'CRM System', price: 25000, unit: 'نظام', image: '', description: 'نظام إدارة علاقات العملاء CRM' },
  { id: 'erp', name: 'ERP System', price: 25000, unit: 'نظام', image: '', description: 'نظام تخطيط موارد المؤسسة ERP' }
];

const els = {
  form: document.getElementById('quoteForm'),
  clientName: document.getElementById('clientName'),
  location: document.getElementById('location'),
  preparedBy: document.getElementById('preparedBy'),
  quotationNo: document.getElementById('quotationNo'),
  quoteDate: document.getElementById('quoteDate'),
  systemTitle: document.getElementById('systemTitle'),
  proposalDescription: document.getElementById('proposalDescription'),
  itemsEditor: document.getElementById('itemsEditor'),
  itemTemplate: document.getElementById('itemRowTemplate'),
  formSubtotal: document.getElementById('formSubtotal'),
  formGrandTotal: document.getElementById('formGrandTotal'),
  adjustmentsEditor: document.getElementById('adjustmentsEditor'),
  adjustmentTemplate: document.getElementById('adjustmentRowTemplate'),
  financialNote: document.getElementById('financialNote'),
  technicalScope: document.getElementById('technicalScope'),
  terms: document.getElementById('terms'),
  features: document.getElementById('features'),
  includeProducts: document.getElementById('includeProducts'),
  includeTechnical: document.getElementById('includeTechnical'),
  includeTerms: document.getElementById('includeTerms'),
  includeFeatures: document.getElementById('includeFeatures'),
  quoteFontFamily: document.getElementById('quoteFontFamily'),
  quoteFontSize: document.getElementById('quoteFontSize'),
  quoteFontSizeValue: document.getElementById('quoteFontSizeValue'),
  enableTextColor: document.getElementById('enableTextColor'),
  quoteTextColor: document.getElementById('quoteTextColor'),
  enableAccentColor: document.getElementById('enableAccentColor'),
  quoteAccentColor: document.getElementById('quoteAccentColor'),
  quotePreview: document.getElementById('quotePreview'),
  pageCount: document.getElementById('pageCount')
};

let items = [];
let nextItemId = 1;
let adjustments = [];
let nextAdjustmentId = 1;

const quoteFonts = {
  arial: 'Arial, Tahoma, sans-serif',
  tahoma: 'Tahoma, Arial, sans-serif',
  traditional: 'Traditional Arabic, Tahoma, Arial, sans-serif',
  simplified: 'Simplified Arabic, Tahoma, Arial, sans-serif',
  times: 'Times New Roman, Times, serif',
  verdana: 'Verdana, Tahoma, Arial, sans-serif',
  georgia: 'Georgia, Times New Roman, serif'
};

function getTypographySettings() {
  const sizePercent = Math.min(130, Math.max(85, Number(els.quoteFontSize?.value || 100)));
  // 5% step = 0.12mm. Keeps text visibly adjustable without pushing fixed A4 content into other elements.
  const bumpMm = ((sizePercent - 100) / 5) * 0.12;
  return {
    fontFamily: quoteFonts[els.quoteFontFamily?.value] || quoteFonts.arial,
    sizePercent,
    bumpMm,
    useTextColor: Boolean(els.enableTextColor?.checked),
    textColor: els.quoteTextColor?.value || '#111111',
    useAccentColor: Boolean(els.enableAccentColor?.checked),
    accentColor: els.quoteAccentColor?.value || '#e10d0d'
  };
}

function typographyPageAttrs() {
  const t = getTypographySettings();
  const classes = [t.useTextColor ? 'custom-text-color' : '', t.useAccentColor ? 'custom-accent-color' : ''].filter(Boolean).join(' ');
  const style = `--content-font:${t.fontFamily};--content-font-bump:${t.bumpMm.toFixed(2)}mm;--content-color:${t.textColor};--accent-color:${t.accentColor};`;
  return { classes, style };
}

function syncTypographyControls() {
  if (els.quoteFontSizeValue) els.quoteFontSizeValue.textContent = `${els.quoteFontSize.value}%`;
  if (els.quoteTextColor) els.quoteTextColor.disabled = !els.enableTextColor.checked;
  if (els.quoteAccentColor) els.quoteAccentColor.disabled = !els.enableAccentColor.checked;
}

function resetTypography() {
  els.quoteFontFamily.value = 'arial';
  els.quoteFontSize.value = '100';
  els.enableTextColor.checked = false;
  els.quoteTextColor.value = '#111111';
  els.enableAccentColor.checked = false;
  els.quoteAccentColor.value = '#e10d0d';
  syncTypographyControls();
  updateAll();
}

function todayISO() {
  const d = new Date();
  const local = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
  return local.toISOString().slice(0, 10);
}
els.quoteDate.value = todayISO();

function escapeHTML(value = '') {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function money(value) {
  const n = Number(value || 0);
  if (!Number.isFinite(n)) return '0';
  return Number.isInteger(n) ? String(n) : n.toFixed(2).replace(/\.00$/, '');
}

function lineBreaks(value = '') {
  return escapeHTML(value).replace(/\n/g, '<br>');
}

function catalogOptions(selected = 'custom') {
  return productCatalog.map(p => {
    const priceLabel = p.id === 'custom' ? '' : ` — ${money(p.price)} جنيه`;
    return `<option value="${p.id}" ${p.id === selected ? 'selected' : ''}>${escapeHTML(p.name)}${priceLabel}</option>`;
  }).join('');
}

function getCatalogProduct(id) {
  return productCatalog.find(p => p.id === id) || productCatalog[0];
}

function addItem(initial = {}) {
  const rowId = nextItemId++;
  const productId = initial.productId || 'custom';
  const product = getCatalogProduct(productId);
  const item = {
    id: rowId,
    productId,
    name: initial.name ?? (productId === 'custom' ? '' : product.name),
    description: initial.description ?? product.description,
    qty: Number(initial.qty ?? 1),
    price: Number(initial.price ?? product.price ?? 0),
    unit: initial.unit ?? product.unit ?? 'قطعه',
    image: initial.image ?? product.image ?? '',
    inFinancial: Boolean(initial.inFinancial ?? true),
    showProduct: Boolean(initial.showProduct ?? Boolean(product.image))
  };
  items.push(item);
  renderItemEditor();
  updateAll();
}

function addAccessory(initial = {}) {
  addItem({ ...initial, inFinancial: false, showProduct: true });
}

function removeItem(id) {
  items = items.filter(i => i.id !== id);
  if (!items.length) addItem();
  else {
    renderItemEditor();
    updateAll();
  }
}

function renderItemEditor() {
  els.itemsEditor.innerHTML = '';
  items.forEach(item => {
    const node = els.itemTemplate.content.firstElementChild.cloneNode(true);
    node.dataset.id = item.id;
    const select = node.querySelector('.item-product');
    const custom = node.querySelector('.item-custom-name');
    const description = node.querySelector('.item-description');
    const qty = node.querySelector('.item-qty');
    const price = node.querySelector('.item-price');
    const unit = node.querySelector('.item-unit');
    const total = node.querySelector('.item-total');
    const inFinancial = node.querySelector('.item-in-financial');
    const show = node.querySelector('.item-show-product');
    const imageInput = node.querySelector('.item-image');

    select.innerHTML = catalogOptions(item.productId);
    custom.hidden = item.productId !== 'custom';
    custom.value = item.name;
    description.value = item.description || '';
    qty.value = item.qty;
    price.value = item.price;
    unit.value = item.unit;
    inFinancial.checked = item.inFinancial;
    show.checked = item.showProduct;
    updateItemRowState(node, item, total);

    select.addEventListener('change', () => {
      const p = getCatalogProduct(select.value);
      item.productId = p.id;
      if (p.id === 'custom') {
        custom.hidden = false;
        item.name = custom.value || '';
        item.image = '';
        // Keep the current location choice. A custom accessory can receive an uploaded image later.
        if (!item.inFinancial) item.showProduct = true;
        show.checked = item.showProduct;
      } else {
        custom.hidden = true;
        item.name = p.name;
        item.description = p.description;
        item.price = p.price;
        item.unit = p.unit;
        item.image = p.image;
        item.showProduct = !item.inFinancial || Boolean(p.image);
        description.value = item.description;
        price.value = item.price;
        unit.value = item.unit;
        show.checked = item.showProduct;
      }
      updateItemRowState(node, item, total);
      updateAll();
    });
    custom.addEventListener('input', () => { item.name = custom.value; updateAll(); });
    description.addEventListener('input', () => { item.description = description.value; updateAll(); });
    qty.addEventListener('input', () => { item.qty = Number(qty.value || 0); updateItemRowState(node, item, total); updateAll(); });
    price.addEventListener('input', () => { item.price = Number(price.value || 0); updateItemRowState(node, item, total); updateAll(); });
    unit.addEventListener('input', () => { item.unit = unit.value; updateAll(); });
    inFinancial.addEventListener('change', () => {
      item.inFinancial = inFinancial.checked;
      if (!item.inFinancial && !item.showProduct) {
        item.showProduct = true;
        show.checked = true;
      }
      updateItemRowState(node, item, total);
      updateAll();
    });
    show.addEventListener('change', () => {
      item.showProduct = show.checked;
      updateItemRowState(node, item, total);
      updateAll();
    });
    imageInput.addEventListener('change', (event) => {
      const file = event.target.files?.[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = () => {
        item.image = reader.result;
        item.showProduct = true;
        show.checked = true;
        updateItemRowState(node, item, total);
        updateAll();
      };
      reader.readAsDataURL(file);
    });
    node.querySelector('.remove-item').addEventListener('click', () => removeItem(item.id));
    els.itemsEditor.appendChild(node);
  });
}

function updateItemRowState(node, item, totalNode) {
  node.classList.toggle('accessory-only', !item.inFinancial && item.showProduct);
  node.classList.toggle('financial-only', item.inFinancial && !item.showProduct);
  node.classList.toggle('both-locations', item.inFinancial && item.showProduct);
  if (item.inFinancial) {
    totalNode.textContent = money(item.qty * item.price);
    totalNode.classList.remove('excluded');
  } else {
    totalNode.textContent = 'ملحق فقط';
    totalNode.classList.add('excluded');
  }
}

function financialItems() {
  return items.filter(item => item.inFinancial);
}

function financialSubtotal() {
  return financialItems().reduce((sum, item) => sum + (Number(item.qty) || 0) * (Number(item.price) || 0), 0);
}

function adjustmentAmount(adjustment, subtotal = financialSubtotal()) {
  const value = Math.max(0, Number(adjustment?.value) || 0);
  const amount = adjustment?.mode === 'percent' ? subtotal * value / 100 : value;
  return adjustment?.operation === 'subtract' ? -amount : amount;
}

function grandTotal() {
  const subtotal = financialSubtotal();
  return Math.max(0, subtotal + adjustments.reduce((sum, adjustment) => sum + adjustmentAmount(adjustment, subtotal), 0));
}

function adjustmentLabel(adjustment) {
  const raw = String(adjustment?.label || '').trim() || (adjustment?.operation === 'subtract' ? 'خصم' : 'إضافة');
  if (adjustment?.mode === 'percent' && Number(adjustment?.value || 0) > 0) {
    return `${raw} (${money(adjustment.value)}%)`;
  }
  return raw;
}

function addAdjustment(data = {}) {
  adjustments.push({
    id: nextAdjustmentId++,
    label: data.label || '',
    operation: data.operation === 'subtract' ? 'subtract' : 'add',
    mode: data.mode === 'percent' ? 'percent' : 'amount',
    value: Math.max(0, Number(data.value || 0))
  });
  renderAdjustmentsEditor();
  updateAll(false);
}

function removeAdjustment(id) {
  adjustments = adjustments.filter(adjustment => adjustment.id !== id);
  renderAdjustmentsEditor();
  updateAll(false);
}

function renderAdjustmentsEditor() {
  if (!els.adjustmentsEditor || !els.adjustmentTemplate) return;
  els.adjustmentsEditor.innerHTML = '';
  const subtotal = financialSubtotal();

  if (!adjustments.length) {
    els.adjustmentsEditor.innerHTML = '<div class="adjustments-empty">لا توجد صفوف إضافية حاليًا.</div>';
    return;
  }

  adjustments.forEach(adjustment => {
    const fragment = els.adjustmentTemplate.content.cloneNode(true);
    const node = fragment.querySelector('.adjustment-editor-row');
    const label = node.querySelector('.adjustment-label');
    const operation = node.querySelector('.adjustment-operation');
    const mode = node.querySelector('.adjustment-mode');
    const value = node.querySelector('.adjustment-value');
    const calculated = node.querySelector('.adjustment-calculated');

    label.value = adjustment.label;
    operation.value = adjustment.operation;
    mode.value = adjustment.mode;
    value.value = adjustment.value;

    const refreshCalculated = () => {
      const amount = adjustmentAmount(adjustment, financialSubtotal());
      calculated.textContent = `${amount >= 0 ? '+' : '−'}${money(Math.abs(amount))}`;
      node.classList.toggle('is-discount', adjustment.operation === 'subtract');
    };
    refreshCalculated();

    const sync = () => {
      adjustment.label = label.value;
      adjustment.operation = operation.value === 'subtract' ? 'subtract' : 'add';
      adjustment.mode = mode.value === 'percent' ? 'percent' : 'amount';
      adjustment.value = Math.max(0, Number(value.value) || 0);
      refreshCalculated();
      updateAll(false);
    };

    label.addEventListener('input', sync);
    operation.addEventListener('change', sync);
    mode.addEventListener('change', sync);
    value.addEventListener('input', sync);
    node.querySelector('.remove-adjustment').addEventListener('click', () => removeAdjustment(adjustment.id));
    els.adjustmentsEditor.appendChild(node);
  });
}

function headerHTML(first = false) {
  return `
    <header class="q-header">
      <div class="q-header-red"></div>
      <div class="q-header-black"></div>
      <div class="q-brand"><strong>Socrates pro tech</strong><span>For trading &amp; system</span></div>
      <div class="q-title">Quotation</div>
      ${first ? `<div class="q-meta">
        <span>Quotation No</span><strong>${escapeHTML(els.quotationNo.value || '—')}</strong>
        <span>Date</span><strong>${escapeHTML(formatDate(els.quoteDate.value) || '—')}</strong>
      </div>` : ''}
    </header>`;
}

function footerHTML() {
  return `
    <footer class="q-footer">
      <div class="q-footer-black"></div>
      <div class="q-footer-red"></div>
      <div class="q-footer-contact">
        <div class="q-phone-symbol">☎</div>
        <div class="q-phone-lines"><span>01001327609</span><span>0233033 829</span></div>
        <img class="q-map" src="${ASSET('map-icon.png')}" alt="" />
        <div class="q-address">64 b El Rashied st,<br>El Mohandssen</div>
      </div>
      <div class="q-website">www.sokrattech.com</div>
      <img class="q-st-logo" src="${ASSET('st-logo.png')}" alt="ST" />
    </footer>`;
}

function page(content, className = '', first = false) {
  const typography = typographyPageAttrs();
  return `<section class="quote-page ${className} ${typography.classes}" style="${typography.style}">${headerHTML(first)}${content}${footerHTML()}</section>`;
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const [y,m,d] = dateStr.split('-');
  return `${Number(y)}-${Number(m)}-${Number(d)}`;
}

function coverPageHTML() {
  const client = escapeHTML(els.clientName.value || 'اسم العميل / الشركة');
  const loc = escapeHTML(els.location.value || 'الموقع');
  const prepared = escapeHTML(els.preparedBy.value || '—');
  return page(`
    <div class="q-body cover-body">
      <div class="client-card-wrap">
        <div class="client-card-title">Quotation to</div>
        <div class="client-card">
          <div>${client}</div>
          <div>${loc}</div>
          <div>مقدمه : ${prepared}</div>
        </div>
      </div>
      <img class="cover-circuit" src="${ASSET('circuit.jpeg')}" alt="" />
      <div class="cover-copy">
        <p class="greeting">السلام عليكم و رحمة الله وبركاته ....</p>
        <p class="intro"><strong>سقراط تك</strong> شركة رائدة فى كل ما يخص تكنولوجيا المعلومات بتخصص و دقة . لدينا فريق عمل قوى فى كل مجالات الاعمال التى نقوم بها يشرفنا ان نضعها بين ايديكم</p>
        <ul class="cover-services">
          <li>أعمال الانظمة الامنيه ( كاميرات المراقبة – اجهزة الانذار ضد السرقه – البوابات الامنية .. )</li>
          <li>الاعمال الادارية ( اجهزة الحضور و الانصراف – ماكينات عد النقدية – الطابعات بكافة انواعها – طابعات طباعة الكروت – ماكينات التصوير .. )</li>
          <li>اعمال الاتصالات ( سنترالات – call center – دش مركزي .... )</li>
          <li>انظمة الصوت و الاستدعاء و الطوابير</li>
          <li>انظمة الشبكات والخوادم</li>
          <li>انظمة العرض – شاشات العرض العملاقة – بروجيكتور ...</li>
          <li>اللاب توب و الكمبيوتر و اكسسوارات الكمبيوتر</li>
          <li>قسم خاص باعمال صيانة الالكترونيات</li>
          <li>برامج ادارية و محاسبية و انظمة نقاط البيع و الباركود</li>
        </ul>
        <p class="cover-end">نعتذر للاطالة لدينا دائما المزيد برجاء الاستفسار اى وقت و لنري العروض المقدمه اليكم</p>
      </div>
      <img class="cover-robot" src="${ASSET('robot-arm.jpeg')}" alt="" />
    </div>`, 'cover-page', true);
}

function financialPageHTML() {
  const client = escapeHTML(els.clientName.value || 'اسم العميل / الشركة');
  const title = escapeHTML(els.systemTitle.value || 'Call Center System');
  const description = lineBreaks(els.proposalDescription.value);
  const financial = financialItems();
  const rows = financial.map(item => {
    const total = item.qty * item.price;
    const desc = item.name || '—';
    return `<tr>
      <td class="desc-cell">${escapeHTML(desc)}</td>
      <td>${money(item.qty)}</td>
      <td>${money(item.price)}</td>
      <td>${escapeHTML(item.unit || 'قطعه')}</td>
      <td>${money(total)}</td>
    </tr>`;
  }).join('');
  const subtotal = financialSubtotal();
  const total = grandTotal();
  const adjustmentRows = adjustments.map(adjustment => {
    const amount = adjustmentAmount(adjustment, subtotal);
    const sign = amount < 0 ? '−' : '+';
    return `<tr class="adjustment-row ${amount < 0 ? 'discount-row' : 'addition-row'}">
      <td colspan="4">${escapeHTML(adjustmentLabel(adjustment))}</td>
      <td>${sign}${money(Math.abs(amount))}</td>
    </tr>`;
  }).join('');
  const finalTotalRow = adjustments.length
    ? `<tr class="final-total-row"><td colspan="4">الإجمالي النهائي</td><td>${money(total)}</td></tr>`
    : '';
  const noteRaw = String(els.financialNote?.value || '').trim();
  const noteHTML = noteRaw
    ? escapeHTML(noteRaw)
        .replaceAll('{subtotal}', `<strong>${money(subtotal)}</strong>`)
        .replaceAll('{total}', `<strong>${money(total)}</strong>`)
        .replace(/\n/g, '<br>')
    : '';
  const density = financial.length + adjustments.length;
  const densityClass = density >= 9 ? 'financial-extra-dense' : density >= 6 ? 'financial-dense' : '';
  return page(`
    <div class="q-body financial-body">
      <div class="financial-copy">
        <div class="recipient">السادة / ${client}</div>
        <p>كل التحية...</p>
        <p>${description}</p>
      </div>
      <div class="section-title">المقايسة المالية</div>
      <table class="financial-table">
        <colgroup><col style="width:46%"><col style="width:10%"><col style="width:15%"><col style="width:13%"><col style="width:16%"></colgroup>
        <thead>
          <tr class="system-head"><th colspan="5">${title}</th></tr>
          <tr class="columns"><th>الصنف</th><th>العدد</th><th>سعر القطعه</th><th>الوحده</th><th>الاجمالى</th></tr>
        </thead>
        <tbody>${rows}</tbody>
        <tfoot>
          <tr class="subtotal-row"><td colspan="4">الاجمالى</td><td>${money(subtotal)}</td></tr>
          ${adjustmentRows}
          ${finalTotalRow}
        </tfoot>
      </table>
      ${noteHTML ? `<div class="financial-note">${noteHTML}</div>` : ''}
    </div>`, `financial-page ${densityClass}`);
}

function productPagesHTML() {
  if (!els.includeProducts.checked) return [];
  const selected = items.filter(item => item.showProduct);
  if (!selected.length) return [];
  const pages = [];
  for (let i = 0; i < selected.length; i += 2) {
    const chunk = selected.slice(i, i + 2);
    const cards = chunk.map(item => productCardHTML(item)).join('');
    const placeholder = chunk.length === 1 ? `<div class="product-card"><div class="product-placeholder">Optional product</div></div>` : '';
    pages.push(page(`
      <div class="q-body product-body">
        <div class="product-page-title ${i === 0 ? 'red' : ''}">${i === 0 ? 'الملحقات' : ''}</div>
        <div class="product-grid">${cards}${placeholder}</div>
      </div>`, 'product-page'));
  }
  return pages;
}

function productCardHTML(item) {
  const image = item.image
    ? `<img src="${item.image}" alt="${escapeHTML(item.name)}" />`
    : `<div class="product-placeholder">No Image</div>`;
  return `<article class="product-card">
    ${image}
    <h3>${escapeHTML(item.name || 'Product')}</h3>
    ${item.description ? `<div class="product-description">${escapeHTML(item.description)}</div>` : ''}
    <div class="product-price">بسعر <strong>${money(item.price)}</strong> جنيه</div>
  </article>`;
}

function linesToDivs(text) {
  return String(text || '').split(/\n+/).filter(Boolean).map(line => `<div class="line">${escapeHTML(line)}</div>`).join('');
}

function technicalPageHTML() {
  const showTechnical = els.includeTechnical.checked;
  const showTerms = els.includeTerms.checked;
  if (!showTechnical && !showTerms) return '';
  return page(`
    <div class="q-body text-page-body">
      ${showTechnical ? `<div class="text-section-title">المقايسة الفنية</div><div class="text-lines">${linesToDivs(els.technicalScope.value)}</div>` : ''}
      ${showTerms ? `<div class="terms-block"><div class="text-section-title">الشروط و الاتفاقات</div><div class="text-lines">${linesToDivs(els.terms.value)}</div></div>` : ''}
    </div>`, 'technical-page');
}

function featuresPageHTML() {
  if (!els.includeFeatures.checked) return '';
  const features = String(els.features.value || '').split(/\n+/).map(s => s.trim()).filter(Boolean);
  return page(`
    <div class="q-body text-page-body">
      <div class="text-section-title">بعض مميزات و خصائص برنامج الكول سنتر</div>
      <div class="features-intro">دلوقتي تقدر تعمل تطوير شامل لمنظومة الاتصالات في مؤسستك مهما كان حجمها. خدمة العملاء اليوم قسم مهم جدا لأي نشاط، وتقدر تربط كل ده في مكان واحد يدعم الرسائل المسجلة والتقارير وتسجيل المكالمات.</div>
      <div class="features-list">${features.map(feature => `<div class="feature-item">${escapeHTML(feature.replace(/^[-–]\s*/, ''))}</div>`).join('')}</div>
      <div class="closing">واليكم كل التحية و التقدير<br>و يشرفنا العمل معكم.</div>
    </div>`, 'features-page');
}

function buildPreview() {
  const pageList = [coverPageHTML(), financialPageHTML(), ...productPagesHTML()];
  const tech = technicalPageHTML();
  const features = featuresPageHTML();
  if (tech) pageList.push(tech);
  if (features) pageList.push(features);
  els.quotePreview.innerHTML = pageList.join('');
  els.pageCount.textContent = `عدد الصفحات: ${pageList.length}`;
}

function updateAll(refreshAdjustments = true) {
  if (els.formSubtotal) els.formSubtotal.textContent = money(financialSubtotal());
  els.formGrandTotal.textContent = money(grandTotal());
  if (refreshAdjustments && adjustments.length) renderAdjustmentsEditor();
  buildPreview();
}

function loadDemo() {
  els.clientName.value = 'الحشاش للأثاث';
  els.location.value = 'المقطم';
  els.preparedBy.value = 'احمد حمدي';
  els.quotationNo.value = '78695455';
  els.quoteDate.value = '2026-08-09';
  els.systemTitle.value = 'Call Center System';
  adjustments = [];
  renderAdjustmentsEditor();
  items = [];
  addItem({ productId: 'basic-line', qty: 1, price: 5500, inFinancial: true, showProduct: false });
  addItem({ productId: 'extra-line', qty: 1, price: 3500, inFinancial: true, showProduct: false });
  addAccessory({ productId: 'logitech-h340', qty: 1, price: 1350 });
  addAccessory({ productId: 'hp-elitedesk-512-hdd', qty: 1, price: 5500 });
  updateAll();
}

function resetForm() {
  if (!confirm('هل تريد مسح بيانات عرض السعر والبدء من جديد؟')) return;
  els.form.reset();
  els.preparedBy.value = 'احمد حمدي';
  els.quoteDate.value = todayISO();
  els.systemTitle.value = 'Call Center System';
  els.includeProducts.checked = true;
  els.includeTechnical.checked = true;
  els.includeTerms.checked = true;
  els.includeFeatures.checked = true;
  els.quoteFontFamily.value = 'arial';
  els.quoteFontSize.value = '100';
  els.enableTextColor.checked = false;
  els.quoteTextColor.value = '#111111';
  els.enableAccentColor.checked = false;
  els.quoteAccentColor.value = '#e10d0d';
  syncTypographyControls();
  adjustments = [];
  renderAdjustmentsEditor();
  items = [];
  addItem();
}


function safeFilePart(value) {
  return String(value || '')
    .trim()
    .replace(/[\\/:*?"<>|]+/g, '-')
    .replace(/\s+/g, ' ')
    .slice(0, 70) || 'Quotation';
}

function collectDocumentCss() {
  let css = '';
  Array.from(document.styleSheets).forEach((sheet) => {
    try {
      Array.from(sheet.cssRules || []).forEach((rule) => { css += `${rule.cssText}\n`; });
    } catch (error) {
      // Same-origin stylesheet. Ignore any inaccessible optional sheet.
    }
  });
  return css;
}

function embeddedImageSource(src) {
  const value = String(src || '');
  if (!value) return '';
  if (value.startsWith('data:')) return value;

  // Handle normal project-relative asset paths even when the document is
  // opened from file:// or rendered in an about:blank print iframe.
  const normalized = value.replace(/^\.\//, '').split(/[?#]/)[0];
  if (window.SOKRAT_ASSET_DATA && window.SOKRAT_ASSET_DATA[normalized]) {
    return window.SOKRAT_ASSET_DATA[normalized];
  }
  const directName = decodeURIComponent(normalized.split('/').pop() || '');
  const directKey = `assets/${directName}`;
  if (window.SOKRAT_ASSET_DATA && window.SOKRAT_ASSET_DATA[directKey]) {
    return window.SOKRAT_ASSET_DATA[directKey];
  }

  try {
    const parsed = new URL(value, document.baseURI);
    const fileName = decodeURIComponent(parsed.pathname.split('/').pop() || '');
    const key = `assets/${fileName}`;
    return (window.SOKRAT_ASSET_DATA && window.SOKRAT_ASSET_DATA[key]) || value;
  } catch (error) {
    return value;
  }
}

function quotationHtmlWithEmbeddedImages() {
  const clone = els.quotePreview.cloneNode(true);
  const sourceImages = Array.from(els.quotePreview.querySelectorAll('img'));
  const clonedImages = Array.from(clone.querySelectorAll('img'));
  clonedImages.forEach((img, index) => {
    const source = sourceImages[index];
    const embedded = embeddedImageSource(source?.getAttribute('src') || source?.src || img.getAttribute('src'));
    if (embedded) img.setAttribute('src', embedded);
  });
  return clone.innerHTML;
}

function printQuote() {
  buildPreview();

  // Print the SAME quotation DOM that the employee is previewing.
  // Previous iframe cloning could lose CSS/media-query state and image sizing.
  // Printing in-place is more reliable because Chrome/Edge use the exact
  // computed layout already visible on screen.
  const body = document.body;
  const oldTitle = document.title;
  const exportTitle = `${safeFilePart(els.quotationNo.value || 'Quotation')} - ${safeFilePart(els.clientName.value || 'Client')}`;

  const cleanup = () => {
    body.classList.remove('sokrat-printing');
    document.documentElement.classList.remove('sokrat-printing');
    document.title = oldTitle;
  };

  body.classList.add('sokrat-printing');
  document.documentElement.classList.add('sokrat-printing');
  document.title = exportTitle;

  window.addEventListener('afterprint', cleanup, { once: true });

  // Two animation frames make sure the final print-only layout has been
  // applied before Chrome/Edge takes its print snapshot.
  requestAnimationFrame(() => requestAnimationFrame(() => {
    try {
      window.print();
    } catch (error) {
      console.error('Print failed:', error);
      cleanup();
      alert('تعذر فتح نافذة الطباعة. حاول مرة أخرى من Chrome أو Edge.');
    }
  }));

  // Safety cleanup for browsers that do not fire afterprint reliably.
  setTimeout(() => {
    if (body.classList.contains('sokrat-printing') && !(window.matchMedia && window.matchMedia('print').matches)) cleanup();
  }, 15000);
}

function xmlEscape(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

function svgDataUrl(svgText) {
  const bytes = new TextEncoder().encode(svgText);
  let binary = '';
  const chunk = 0x8000;
  for (let i = 0; i < bytes.length; i += chunk) {
    binary += String.fromCharCode(...bytes.subarray(i, i + chunk));
  }
  return `data:image/svg+xml;base64,${btoa(binary)}`;
}

const RASTER_STYLE_PROPS = [
  'display','position','box-sizing','top','right','bottom','left','z-index',
  'width','height','min-width','min-height','max-width','max-height',
  'margin-top','margin-right','margin-bottom','margin-left',
  'padding-top','padding-right','padding-bottom','padding-left',
  'border-top-width','border-right-width','border-bottom-width','border-left-width',
  'border-top-style','border-right-style','border-bottom-style','border-left-style',
  'border-top-color','border-right-color','border-bottom-color','border-left-color',
  'border-top-left-radius','border-top-right-radius','border-bottom-right-radius','border-bottom-left-radius',
  'border-collapse','border-spacing','table-layout',
  'background-color','background-image','background-size','background-position','background-repeat','background-clip','background-origin',
  'color','opacity','visibility','overflow','overflow-x','overflow-y','clip-path',
  'font-family','font-size','font-weight','font-style','font-variant','line-height','letter-spacing','word-spacing',
  'text-align','text-align-last','text-decoration-line','text-decoration-color','text-decoration-style','text-decoration-thickness',
  'text-underline-offset','text-transform','text-indent','text-shadow','white-space','overflow-wrap','word-break','direction','unicode-bidi','vertical-align',
  'object-fit','object-position',
  'box-shadow','transform','transform-origin','filter',
  'list-style-type','list-style-position','list-style-image',
  'flex','flex-basis','flex-grow','flex-shrink','flex-direction','flex-wrap','align-items','align-content','align-self','justify-content','justify-items','justify-self','order',
  'grid-template-columns','grid-template-rows','grid-column','grid-row','grid-auto-flow','grid-auto-columns','grid-auto-rows','column-gap','row-gap','gap','place-items','place-content'
];

function copyRasterStyle(source, target, pseudo = null) {
  const style = getComputedStyle(source, pseudo);
  RASTER_STYLE_PROPS.forEach((prop) => {
    const value = style.getPropertyValue(prop);
    if (value) target.style.setProperty(prop, value);
  });
}

function pseudoText(content) {
  if (!content || content === 'none' || content === 'normal' || content === '""' || content === "''") return '';
  let text = content;
  if ((text.startsWith('"') && text.endsWith('"')) || (text.startsWith("'") && text.endsWith("'"))) text = text.slice(1, -1);
  return text.replace(/\\A/g, '\n').replace(/\\(["'\\])/g, '$1');
}

function materializePseudo(source, clone, pseudo) {
  const computed = getComputedStyle(source, pseudo);
  const text = pseudoText(computed.content);
  if (!text) return;
  const span = document.createElement('span');
  span.textContent = text;
  span.setAttribute('aria-hidden', 'true');
  copyRasterStyle(source, span, pseudo);
  // Pseudo elements do not participate as real DOM nodes; this materializes
  // the few bullets/dashes used by the quotation template for raster export.
  if (pseudo === '::before') clone.insertBefore(span, clone.firstChild);
  else clone.appendChild(span);
}

function clonePageWithComputedStyles(page) {
  const clone = page.cloneNode(true);
  const sourceNodes = [page, ...page.querySelectorAll('*')];
  const clonedNodes = [clone, ...clone.querySelectorAll('*')];

  for (let i = 0; i < sourceNodes.length; i += 1) {
    const source = sourceNodes[i];
    const target = clonedNodes[i];
    if (!target) continue;
    copyRasterStyle(source, target);

    if (source.tagName === 'IMG') {
      const embedded = embeddedImageSource(source.getAttribute('src') || source.src);
      if (embedded) target.setAttribute('src', embedded);
      target.removeAttribute('srcset');
      target.removeAttribute('loading');

      // Product photos use intrinsic dimensions constrained by max-width /
      // max-height. If the live <img> has not finished loading yet, its
      // computed width can collapse to the broken-image icon size. Let the
      // embedded data URI provide its real intrinsic size during SVG render.
      if (source.closest('.product-card')) {
        target.style.setProperty('width', 'auto', 'important');
        target.style.setProperty('height', 'auto', 'important');
        target.style.setProperty('max-width', '120mm', 'important');
        target.style.setProperty('max-height', '75mm', 'important');
        target.style.setProperty('object-fit', 'contain', 'important');
      }
    }
  }

  // Materialize the quotation's CSS-generated dashes/bullets after the main
  // node walk so the source/clone indexes remain aligned.
  for (let i = 0; i < sourceNodes.length; i += 1) {
    const source = sourceNodes[i];
    const target = clonedNodes[i];
    if (!target || target.nodeType !== 1) continue;
    materializePseudo(source, target, '::before');
    materializePseudo(source, target, '::after');
  }

  clone.style.setProperty('zoom', '1', 'important');
  clone.style.setProperty('transform', 'none', 'important');
  clone.style.setProperty('transform-origin', 'top left', 'important');
  clone.style.setProperty('margin', '0', 'important');
  clone.style.setProperty('box-shadow', 'none', 'important');
  clone.style.setProperty('width', '210mm', 'important');
  clone.style.setProperty('height', '297mm', 'important');
  clone.style.setProperty('min-height', '297mm', 'important');
  clone.style.setProperty('max-height', '297mm', 'important');
  clone.style.setProperty('overflow', 'hidden', 'important');
  clone.style.setProperty('background', '#fff', 'important');
  return clone;
}

async function quotePageToPng(page, scale = 1.8) {
  // Rasterize the exact computed page, not a fresh CSS clone. This avoids the
  // CSS/iframe/foreignObject differences that previously enlarged logos and
  // dropped the red/black header/footer in Word exports.
  const clone = clonePageWithComputedStyles(page);

  const width = 794;
  const height = 1123;
  const serialized = new XMLSerializer().serializeToString(clone);
  const svg = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
  <foreignObject x="0" y="0" width="${width}" height="${height}">
    <div xmlns="http://www.w3.org/1999/xhtml" style="width:${width}px;height:${height}px;margin:0;padding:0;background:#fff;overflow:hidden;">
      ${serialized}
    </div>
  </foreignObject>
</svg>`;

  const image = new Image();
  const svgUrl = svgDataUrl(svg);
  await new Promise((resolve, reject) => {
    image.onload = resolve;
    image.onerror = () => reject(new Error('تعذر تجهيز صفحة عرض السعر للتصدير.'));
    image.src = svgUrl;
  });

  const canvas = document.createElement('canvas');
  canvas.width = Math.round(width * scale);
  canvas.height = Math.round(height * scale);
  const ctx = canvas.getContext('2d');
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.setTransform(scale, 0, 0, scale, 0, 0);
  ctx.drawImage(image, 0, 0, width, height);
  return canvas.toDataURL('image/png');
}

function dataUrlToBytes(dataUrl) {
  const base64 = String(dataUrl).split(',')[1] || '';
  const binary = atob(base64);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i += 1) bytes[i] = binary.charCodeAt(i);
  return bytes;
}

const CRC32_TABLE = (() => {
  const table = new Uint32Array(256);
  for (let n = 0; n < 256; n += 1) {
    let c = n;
    for (let k = 0; k < 8; k += 1) c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
    table[n] = c >>> 0;
  }
  return table;
})();

function crc32(bytes) {
  let c = 0xFFFFFFFF;
  for (let i = 0; i < bytes.length; i += 1) c = CRC32_TABLE[(c ^ bytes[i]) & 0xFF] ^ (c >>> 8);
  return (c ^ 0xFFFFFFFF) >>> 0;
}

function concatBytes(parts) {
  const length = parts.reduce((sum, part) => sum + part.length, 0);
  const out = new Uint8Array(length);
  let offset = 0;
  parts.forEach((part) => { out.set(part, offset); offset += part.length; });
  return out;
}

function zipStored(files) {
  const encoder = new TextEncoder();
  const localParts = [];
  const centralParts = [];
  let localOffset = 0;
  const now = new Date();
  const dosTime = ((now.getHours() & 31) << 11) | ((now.getMinutes() & 63) << 5) | ((Math.floor(now.getSeconds() / 2)) & 31);
  const dosDate = (((Math.max(now.getFullYear(), 1980) - 1980) & 127) << 9) | (((now.getMonth() + 1) & 15) << 5) | (now.getDate() & 31);

  files.forEach(({ name, data }) => {
    const nameBytes = encoder.encode(name);
    const bytes = typeof data === 'string' ? encoder.encode(data) : data;
    const crc = crc32(bytes);

    const local = new Uint8Array(30 + nameBytes.length);
    const lv = new DataView(local.buffer);
    lv.setUint32(0, 0x04034b50, true);
    lv.setUint16(4, 20, true);
    lv.setUint16(6, 0x0800, true);
    lv.setUint16(8, 0, true);
    lv.setUint16(10, dosTime, true);
    lv.setUint16(12, dosDate, true);
    lv.setUint32(14, crc, true);
    lv.setUint32(18, bytes.length, true);
    lv.setUint32(22, bytes.length, true);
    lv.setUint16(26, nameBytes.length, true);
    lv.setUint16(28, 0, true);
    local.set(nameBytes, 30);
    localParts.push(local, bytes);

    const central = new Uint8Array(46 + nameBytes.length);
    const cv = new DataView(central.buffer);
    cv.setUint32(0, 0x02014b50, true);
    cv.setUint16(4, 20, true);
    cv.setUint16(6, 20, true);
    cv.setUint16(8, 0x0800, true);
    cv.setUint16(10, 0, true);
    cv.setUint16(12, dosTime, true);
    cv.setUint16(14, dosDate, true);
    cv.setUint32(16, crc, true);
    cv.setUint32(20, bytes.length, true);
    cv.setUint32(24, bytes.length, true);
    cv.setUint16(28, nameBytes.length, true);
    cv.setUint16(30, 0, true);
    cv.setUint16(32, 0, true);
    cv.setUint16(34, 0, true);
    cv.setUint16(36, 0, true);
    cv.setUint32(38, 0, true);
    cv.setUint32(42, localOffset, true);
    central.set(nameBytes, 46);
    centralParts.push(central);

    localOffset += local.length + bytes.length;
  });

  const centralData = concatBytes(centralParts);
  const eocd = new Uint8Array(22);
  const ev = new DataView(eocd.buffer);
  ev.setUint32(0, 0x06054b50, true);
  ev.setUint16(4, 0, true);
  ev.setUint16(6, 0, true);
  ev.setUint16(8, files.length, true);
  ev.setUint16(10, files.length, true);
  ev.setUint32(12, centralData.length, true);
  ev.setUint32(16, localOffset, true);
  ev.setUint16(20, 0, true);
  return concatBytes([...localParts, centralData, eocd]);
}

function buildDocx(pagePngDataUrls) {
  const pageW = 7560310;
  const pageH = 10692130;
  const rels = pagePngDataUrls.map((_, i) =>
    `<Relationship Id="rId${i + 1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/page${i + 1}.png"/>`
  ).join('');

  const pageXml = pagePngDataUrls.map((_, i) => {
    const id = i + 1;
    const pageBreak = i < pagePngDataUrls.length - 1
      ? '<w:p><w:pPr><w:spacing w:before="0" w:after="0"/></w:pPr><w:r><w:br w:type="page"/></w:r></w:p>'
      : '';
    return `<w:p>
      <w:pPr><w:spacing w:before="0" w:after="0" w:line="1" w:lineRule="exact"/></w:pPr>
      <w:r><w:drawing>
        <wp:anchor distT="0" distB="0" distL="0" distR="0" simplePos="0" relativeHeight="0" behindDoc="0" locked="1" layoutInCell="1" allowOverlap="1">
          <wp:simplePos x="0" y="0"/>
          <wp:positionH relativeFrom="page"><wp:posOffset>0</wp:posOffset></wp:positionH>
          <wp:positionV relativeFrom="page"><wp:posOffset>0</wp:posOffset></wp:positionV>
          <wp:extent cx="${pageW}" cy="${pageH}"/>
          <wp:effectExtent l="0" t="0" r="0" b="0"/>
          <wp:wrapNone/>
          <wp:docPr id="${id}" name="Quotation Page ${id}"/>
          <wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>
          <a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
            <pic:pic>
              <pic:nvPicPr><pic:cNvPr id="${id}" name="page${id}.png"/><pic:cNvPicPr/></pic:nvPicPr>
              <pic:blipFill><a:blip r:embed="rId${id}"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>
              <pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="${pageW}" cy="${pageH}"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>
            </pic:pic>
          </a:graphicData></a:graphic>
        </wp:anchor>
      </w:drawing></w:r>
    </w:p>${pageBreak}`;
  }).join('');

  const documentXml = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
 xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
 xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
 xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
 xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
 <w:body>${pageXml}
  <w:sectPr>
    <w:pgSz w:w="11906" w:h="16838"/>
    <w:pgMar w:top="0" w:right="0" w:bottom="0" w:left="0" w:header="0" w:footer="0" w:gutter="0"/>
  </w:sectPr>
 </w:body>
</w:document>`;

  const contentTypes = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
 <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
 <Default Extension="xml" ContentType="application/xml"/>
 <Default Extension="png" ContentType="image/png"/>
 <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
 <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
 <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>`;
  const rootRels = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
 <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
 <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>`;
  const documentRels = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">${rels}</Relationships>`;
  const now = new Date().toISOString();
  const core = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
 <dc:title>${xmlEscape(els.quotationNo.value || 'Quotation')}</dc:title>
 <dc:creator>Sokrat Pro Tech</dc:creator>
 <dcterms:created xsi:type="dcterms:W3CDTF">${now}</dcterms:created>
 <dcterms:modified xsi:type="dcterms:W3CDTF">${now}</dcterms:modified>
</cp:coreProperties>`;
  const app = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
 <Application>Sokrat Pro Tech Quotation Generator</Application>
 <Pages>${pagePngDataUrls.length}</Pages>
</Properties>`;

  const files = [
    { name: '[Content_Types].xml', data: contentTypes },
    { name: '_rels/.rels', data: rootRels },
    { name: 'word/document.xml', data: documentXml },
    { name: 'word/_rels/document.xml.rels', data: documentRels },
    { name: 'docProps/core.xml', data: core },
    { name: 'docProps/app.xml', data: app },
    ...pagePngDataUrls.map((url, i) => ({ name: `word/media/page${i + 1}.png`, data: dataUrlToBytes(url) }))
  ];
  return zipStored(files);
}

async function exportWord() {
  buildPreview();
  const pages = Array.from(els.quotePreview.querySelectorAll('.quote-page'));
  if (!pages.length) {
    alert('لا توجد صفحات لتصديرها.');
    return;
  }

  const wordButtons = [document.getElementById('wordBtn'), document.getElementById('wordTopBtn')].filter(Boolean);
  const labels = wordButtons.map(btn => btn.textContent);
  wordButtons.forEach(btn => { btn.disabled = true; btn.textContent = 'جارٍ تجهيز Word...'; });

  try {
    const images = [];
    for (let i = 0; i < pages.length; i += 1) {
      wordButtons.forEach(btn => { btn.textContent = `Word ${i + 1}/${pages.length}`; });
      images.push(await quotePageToPng(pages[i]));
    }

    const bytes = buildDocx(images);
    const blob = new Blob([bytes], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${safeFilePart(els.quotationNo.value || 'Quotation')} - ${safeFilePart(els.clientName.value || 'Client')}.docx`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 2500);
  } catch (error) {
    console.error('Word export failed:', error);
    alert(`تعذر إنشاء ملف Word. ${error?.message || 'حاول مرة أخرى.'}`);
  } finally {
    wordButtons.forEach((btn, i) => { btn.disabled = false; btn.textContent = labels[i]; });
  }
}


// Buttons
document.getElementById('addItemBtn').addEventListener('click', () => addItem({ inFinancial: true, showProduct: false }));
document.getElementById('addAccessoryBtn').addEventListener('click', () => addAccessory());
document.getElementById('addAdjustmentBtn').addEventListener('click', () => addAdjustment());
document.getElementById('previewBtn').addEventListener('click', () => {
  buildPreview();
  const stage = document.querySelector('.preview-stage');
  if (!stage) return;

  // Desktop uses two independent full-height panes. Scroll only the preview
  // pane so the editor scrollbar and its bottom action bar never detach.
  if (window.innerWidth > 980) {
    stage.scrollTo({ top: 0, behavior: 'smooth' });
  } else {
    // On tablet/mobile the preview sits below the full-height editor.
    stage.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
});
document.getElementById('printBtn').addEventListener('click', printQuote);
document.getElementById('printTopBtn').addEventListener('click', printQuote);
document.getElementById('wordBtn').addEventListener('click', exportWord);
document.getElementById('wordTopBtn').addEventListener('click', exportWord);
document.getElementById('loadDemoBtn').addEventListener('click', loadDemo);
document.getElementById('resetBtn').addEventListener('click', resetForm);
document.getElementById('resetTypographyBtn').addEventListener('click', resetTypography);

[els.quoteFontFamily, els.quoteFontSize, els.quoteTextColor, els.quoteAccentColor].forEach(control => {
  control.addEventListener('input', () => { syncTypographyControls(); updateAll(); });
  control.addEventListener('change', () => { syncTypographyControls(); updateAll(); });
});
[els.enableTextColor, els.enableAccentColor].forEach(control => {
  control.addEventListener('change', () => { syncTypographyControls(); updateAll(); });
});

document.querySelectorAll('.collapse-trigger').forEach(btn => {
  btn.addEventListener('click', () => btn.closest('.collapsible').classList.toggle('open'));
});

// Live preview for non-item fields.
els.form.addEventListener('input', event => {
  if (!event.target.closest('.item-row') && !event.target.closest('.adjustment-editor-row')) updateAll();
});
els.form.addEventListener('change', event => {
  if (!event.target.closest('.item-row') && !event.target.closest('.adjustment-editor-row')) updateAll();
});

// Start with a useful blank line item.
syncTypographyControls();
addItem({ productId: 'basic-line', qty: 1, price: 5500, inFinancial: true, showProduct: false });
addItem({ productId: 'custom', name: '', qty: 1, price: 0, inFinancial: true, showProduct: false });
updateAll();

// --- Resizable builder workspace (V2.5) ---
(function initResizableBuilder() {
  const resizer = document.getElementById('builderResizer');
  const resetLayoutBtn = document.getElementById('resetLayoutBtn');
  if (!resizer) return;

  const DEFAULT_WIDTH = 520;
  const STORAGE_KEY = 'sokratBuilderWidth';
  const root = document.documentElement;

  function getLimits() {
    const viewport = window.innerWidth;
    const min = Math.min(420, Math.max(360, viewport * 0.27));
    // Always leave a useful preview area on desktop/laptop screens.
    const previewReserve = Math.min(760, Math.max(500, viewport * 0.42));
    const max = Math.max(min, Math.min(760, viewport - previewReserve - 10));
    return { min, max };
  }

  function clampWidth(value) {
    const { min, max } = getLimits();
    return Math.round(Math.min(max, Math.max(min, Number(value) || DEFAULT_WIDTH)));
  }

  function applyWidth(value, save = true) {
    if (window.innerWidth <= 980) {
      root.style.removeProperty('--builder-width');
      return;
    }
    const width = clampWidth(value);
    root.style.setProperty('--builder-width', `${width}px`);
    if (save) {
      try { localStorage.setItem(STORAGE_KEY, String(width)); } catch (_) {}
    }
  }

  function restoreSavedWidth() {
    let saved = DEFAULT_WIDTH;
    try { saved = Number(localStorage.getItem(STORAGE_KEY)) || DEFAULT_WIDTH; } catch (_) {}
    applyWidth(saved, false);
  }

  function resetWidth() {
    try { localStorage.removeItem(STORAGE_KEY); } catch (_) {}
    applyWidth(DEFAULT_WIDTH, false);
  }

  let dragging = false;

  function beginDrag(event) {
    if (window.innerWidth <= 980) return;
    dragging = true;
    resizer.classList.add('is-dragging');
    document.body.classList.add('is-resizing');
    if (resizer.setPointerCapture && event.pointerId != null) {
      try { resizer.setPointerCapture(event.pointerId); } catch (_) {}
    }
    event.preventDefault();
  }

  function drag(event) {
    if (!dragging) return;
    // The builder occupies the left side of the LTR app shell, so pointer X is its width.
    applyWidth(event.clientX, false);
    event.preventDefault();
  }

  function endDrag(event) {
    if (!dragging) return;
    dragging = false;
    resizer.classList.remove('is-dragging');
    document.body.classList.remove('is-resizing');
    const current = parseFloat(getComputedStyle(root).getPropertyValue('--builder-width')) || DEFAULT_WIDTH;
    applyWidth(current, true);
    if (resizer.releasePointerCapture && event?.pointerId != null) {
      try { resizer.releasePointerCapture(event.pointerId); } catch (_) {}
    }
  }

  resizer.addEventListener('pointerdown', beginDrag);
  window.addEventListener('pointermove', drag, { passive: false });
  window.addEventListener('pointerup', endDrag);
  window.addEventListener('pointercancel', endDrag);
  resizer.addEventListener('dblclick', resetWidth);
  resetLayoutBtn?.addEventListener('click', resetWidth);

  // Keyboard accessibility: arrows resize, Home resets.
  resizer.tabIndex = 0;
  resizer.addEventListener('keydown', (event) => {
    if (window.innerWidth <= 980) return;
    const current = parseFloat(getComputedStyle(root).getPropertyValue('--builder-width')) || DEFAULT_WIDTH;
    if (event.key === 'ArrowLeft') { applyWidth(current - 24); event.preventDefault(); }
    if (event.key === 'ArrowRight') { applyWidth(current + 24); event.preventDefault(); }
    if (event.key === 'Home') { resetWidth(); event.preventDefault(); }
  });

  window.addEventListener('resize', restoreSavedWidth);
  restoreSavedWidth();
})();

// --- Fully responsive A4 preview scaling (V2.6) ---
(function initResponsivePreview() {
  const root = document.documentElement;
  const stage = document.querySelector('.preview-stage');
  const preview = document.getElementById('quotePreview');
  if (!stage || !preview) return;

  // CSS absolute units resolve at 96 CSS px/in on screen.
  const A4_WIDTH_PX = (210 / 25.4) * 96;
  let rafId = 0;

  function updatePreviewZoom() {
    cancelAnimationFrame(rafId);
    rafId = requestAnimationFrame(() => {
      if (window.matchMedia && window.matchMedia('print').matches) {
        root.style.setProperty('--preview-zoom', '1');
        return;
      }

      const stageStyles = getComputedStyle(stage);
      const horizontalPadding =
        (parseFloat(stageStyles.paddingLeft) || 0) +
        (parseFloat(stageStyles.paddingRight) || 0);

      // Keep a tiny safety gap so browser scrollbars/rounding never crop the A4 page.
      const availableWidth = Math.max(240, stage.clientWidth - horizontalPadding - 8);
      const scale = Math.min(1, Math.max(0.28, availableWidth / A4_WIDTH_PX));
      root.style.setProperty('--preview-zoom', scale.toFixed(4));
    });
  }

  if ('ResizeObserver' in window) {
    const resizeObserver = new ResizeObserver(updatePreviewZoom);
    resizeObserver.observe(stage);
  }

  // Quote pages are regenerated often; recalculate after every preview update.
  if ('MutationObserver' in window) {
    const mutationObserver = new MutationObserver(updatePreviewZoom);
    mutationObserver.observe(preview, { childList: true });
  }

  window.addEventListener('resize', updatePreviewZoom, { passive: true });
  window.addEventListener('orientationchange', updatePreviewZoom, { passive: true });
  window.addEventListener('beforeprint', () => root.style.setProperty('--preview-zoom', '1'));
  window.addEventListener('afterprint', updatePreviewZoom);

  updatePreviewZoom();
})();
