(() => {
 if (window.__sokratCrmSidebarReady) {
  return;
 }
 window.__sokratCrmSidebarReady = true;

 const storageKey = 'sokrat.crm.sidebar.collapsed';
 const root = document.documentElement;
 const body = document.body;
 const sidebar = document.getElementById('crmSidebar');
 const collapseButton = document.getElementById('crmSidebarCollapseBtn');

 const canStore = () => {
  try {
   localStorage.setItem('__sokrat_sidebar_test__', '1');
   localStorage.removeItem('__sokrat_sidebar_test__');
   return true;
  } catch (error) {
   return false;
  }
 };

 const storageAvailable = canStore();

 const readCollapsed = () => {
  if (!storageAvailable) {
   return root.classList.contains('crm-sidebar-collapsed');
  }

  return localStorage.getItem(storageKey) === '1';
 };

 const writeCollapsed = (collapsed) => {
  if (!storageAvailable) {
   return;
  }

  localStorage.setItem(storageKey, collapsed ? '1' : '0');
 };

 const setTitleFromLabel = (element) => {
  if (!element || element.hasAttribute('title')) {
   return;
  }

  const label = element.querySelector('.crm-label, .label');
  const text = label ? label.textContent.trim().replace(/\s+/g, ' ') : '';

  if (text) {
   element.setAttribute('title', text);
  }
 };

 const updateCollapseButton = (collapsed) => {
  if (!collapseButton) {
   return;
  }

  const icon = collapseButton.querySelector('i');
  const label = collapseButton.querySelector('span');
  const isEn = (root.getAttribute('lang') || '').toLowerCase().startsWith('en');
  const isRtl = (root.getAttribute('dir') || '').toLowerCase() === 'rtl';
  const title = collapsed
   ? (isEn ? 'Expand Menu' : 'توسيع القائمة الجانبية')
   : (isEn ? 'Collapse Menu' : 'طي القائمة الجانبية');

  collapseButton.setAttribute('aria-label', title);
  collapseButton.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
  collapseButton.setAttribute('title', title);

  if (label) {
   label.textContent = collapsed
    ? (isEn ? 'Expand Menu' : 'توسيع القائمة')
    : (isEn ? 'Collapse Menu' : 'طي القائمة');
  }

  if (icon) {
   if (isRtl) {
    icon.className = collapsed
     ? 'bi bi-chevron-double-left'
     : 'bi bi-chevron-double-right';
   } else {
    icon.className = collapsed
     ? 'bi bi-chevron-double-right'
     : 'bi bi-chevron-double-left';
   }
  }
 };

 const setCollapsed = (collapsed, persist = true) => {
  root.classList.toggle('crm-sidebar-collapsed', collapsed);
  if (body) {
   body.classList.toggle('crm-sidebar-collapsed', collapsed);
  }

  updateCollapseButton(collapsed);

  if (persist) {
   writeCollapsed(collapsed);
  }
 };

 const isCollapsed = () => root.classList.contains('crm-sidebar-collapsed');

 let animationTimer = null;

 const toggleCollapsedAnimated = (targetState) => {
  if (animationTimer) {
   clearTimeout(animationTimer);
   animationTimer = null;
  }

  const collapsing = targetState !== undefined ? targetState : !isCollapsed();

  if (collapsing === isCollapsed()) {
   return;
  }
  if (collapsing) {
   root.classList.add('crm-sidebar-animating', 'crm-sidebar-fading-out');
   setCollapsed(true);
   setTimeout(() => {
    root.classList.remove('crm-sidebar-fading-out');
   }, 90);
   animationTimer = setTimeout(() => {
    root.classList.remove('crm-sidebar-animating');
    animationTimer = null;
   }, 220);
  } else {
   root.classList.add('crm-sidebar-animating', 'crm-sidebar-fading-in');
   setCollapsed(false);

   animationTimer = setTimeout(() => {
    root.classList.remove('crm-sidebar-animating', 'crm-sidebar-fading-in');
    animationTimer = null;
   }, 220);
  }
 };
 const setMenuOpen = (button, target, open) => {
  target.classList.toggle('open', open);
  button.setAttribute('aria-expanded', open ? 'true' : 'false');
 };

 document.querySelectorAll('.crm-link, .crm-toggle').forEach(setTitleFromLabel);
 setCollapsed(readCollapsed(), false);

 if (collapseButton) {
  collapseButton.addEventListener('click', () => {
   toggleCollapsedAnimated();
  });
 }

 if (sidebar) {
  sidebar.addEventListener(
   'click',
   (event) => {
    const button = event.target.closest('[data-crm-menu]');

    if (!button || !sidebar.contains(button)) {
     return;
    }

    const menuId = button.dataset.crmMenu || button.dataset.menu;
    const target = menuId ? document.getElementById(menuId) : null;

    if (!target) {
     return;
    }

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    if (isCollapsed() && window.matchMedia('(min-width: 901px)').matches) {
     toggleCollapsedAnimated(false);
     setMenuOpen(button, target, true);
     return;
    }

    setMenuOpen(button, target, !target.classList.contains('open'));
   },
   true
  );
 }
})();
/* ==========================================================================
   CRM UNIVERSAL SEARCHABLE & MULTI-SELECT ENHANCER (>15 options or multiple)
   ========================================================================== */
(() => {
    const isEn = () => (document.documentElement.getAttribute('lang') || '').toLowerCase().startsWith('en');

    const normalizeText = (str) => {
        return (str || '')
            .toString()
            .trim()
            .toLowerCase()
            .replace(/[أإآ]/g, 'ا')
            .replace(/ة/g, 'ه')
            .replace(/[\u064B-\u065F]/g, '');
    };

    const i18n = {
        searchPlaceholder: () => isEn() ? 'Search options...' : 'بحث في الخيارات...',
        noResults: () => isEn() ? 'No matching options found.' : 'لا توجد نتائج تطابق البحث.',
        selectOption: () => isEn() ? 'Select an option' : 'اختر خياراً',
        selectAll: () => isEn() ? 'Select All' : 'تحديد الكل',
        clear: () => isEn() ? 'Clear' : 'إلغاء التحديد',
        selectedCount: (n) => isEn() ? `${n} selected` : `${n} محدد`,
    };

    class CrmSelectWidget {
        constructor(selectEl) {
            this.select = selectEl;
            this.isMulti = selectEl.hasAttribute('multiple') || selectEl.dataset.multi === 'true';
            this.isOpen = false;
            this.highlightedIndex = -1;
            this.optionItems = [];

            this.init();
        }

        init() {
            if (this.select.dataset.crmEnhanced) return;
            this.select.dataset.crmEnhanced = 'true';
            this.select.classList.add('crm-select-native-hidden');

            this.wrap = document.createElement('div');
            this.wrap.className = 'crm-select-wrap' + (this.isMulti ? ' is-multi' : '');

            if (this.select.style.width) this.wrap.style.width = this.select.style.width;
            if (this.select.style.maxWidth) this.wrap.style.maxWidth = this.select.style.maxWidth;
            if (this.select.className && this.select.className.includes('filter-control')) {
                this.wrap.classList.add('filter-control-wrap');
            }

            // 1. Create Trigger
            this.trigger = document.createElement('button');
            this.trigger.type = 'button';
            this.trigger.className = 'crm-select-trigger';
            this.trigger.setAttribute('aria-haspopup', 'listbox');
            this.trigger.setAttribute('aria-expanded', 'false');
            if (this.select.id) this.trigger.id = this.select.id + '_crmTrigger';
            if (this.select.disabled) this.trigger.disabled = true;

            this.labelContainer = document.createElement('span');
            this.labelContainer.className = 'crm-select-trigger-label';

            const chevron = document.createElement('i');
            chevron.className = 'bi bi-chevron-down crm-select-chevron';
            chevron.setAttribute('aria-hidden', 'true');

            this.trigger.append(this.labelContainer, chevron);

            // 2. Create Dropdown
            this.dropdown = document.createElement('div');
            this.dropdown.className = 'crm-select-dropdown';
            this.dropdown.setAttribute('role', 'listbox');

            // 3. Options List container
            this.optionsList = document.createElement('div');
            this.optionsList.className = 'crm-select-options-list';

            this.noResults = document.createElement('div');
            this.noResults.className = 'crm-select-no-results';
            this.noResults.textContent = i18n.noResults();
            this.noResults.style.display = 'none';

            this.dropdown.append(this.optionsList, this.noResults);
            this.wrap.append(this.trigger, this.dropdown);

            // Insert into DOM
            this.select.parentNode.insertBefore(this.wrap, this.select.nextSibling);
            this.wrap.appendChild(this.select);

            this.readOptions();
            this.updateTrigger();

            // Event Listeners
            this.trigger.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
            });

            this.trigger.addEventListener('keydown', (e) => this.handleKeydown(e));

            // Sync on native change
            this.select.addEventListener('change', () => {
                this.syncSelectedUI();
            });

            // Watch for changes in disabled state or options
            const observer = new MutationObserver(() => {
                if (this.select.disabled !== this.trigger.disabled) {
                    this.trigger.disabled = this.select.disabled;
                }
                this.readOptions();
                this.updateTrigger();
            });
            observer.observe(this.select, { attributes: true, childList: true, subtree: true });
        }

        ensureSearchAndMultiHeader() {
            const optionCount = this.select.options.length;
            this.hasSearch = optionCount > 15 || this.select.dataset.searchable === 'true' || this.isMulti;

            // Search bar
            if (this.hasSearch && !this.searchWrap) {
                this.searchWrap = document.createElement('div');
                this.searchWrap.className = 'crm-select-search-wrap';
                const searchIcon = document.createElement('i');
                searchIcon.className = 'bi bi-search';
                searchIcon.setAttribute('aria-hidden', 'true');
                this.searchInput = document.createElement('input');
                this.searchInput.type = 'search';
                this.searchInput.className = 'crm-select-search-input';
                this.searchInput.placeholder = i18n.searchPlaceholder();
                this.searchInput.autocomplete = 'off';

                this.searchWrap.append(searchIcon, this.searchInput);
                this.dropdown.insertBefore(this.searchWrap, this.dropdown.firstChild);

                this.searchInput.addEventListener('input', () => this.filterOptions(this.searchInput.value));
                this.searchInput.addEventListener('keydown', (e) => this.handleKeydown(e));
            } else if (!this.hasSearch && this.searchWrap) {
                this.searchWrap.remove();
                this.searchWrap = null;
                this.searchInput = null;
            }

            // Multi-select header
            if (this.isMulti && !this.multiHeader) {
                this.multiHeader = document.createElement('div');
                this.multiHeader.className = 'crm-select-multi-header';
                this.multiCountLabel = document.createElement('span');
                const btnGroup = document.createElement('div');
                btnGroup.style.display = 'flex';
                btnGroup.style.gap = '6px';

                const selectAllBtn = document.createElement('button');
                selectAllBtn.type = 'button';
                selectAllBtn.className = 'crm-select-batch-btn';
                selectAllBtn.textContent = i18n.selectAll();
                selectAllBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.selectAll();
                });

                const clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.className = 'crm-select-batch-btn';
                clearBtn.textContent = i18n.clear();
                clearBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.clearAll();
                });

                btnGroup.append(selectAllBtn, clearBtn);
                this.multiHeader.append(this.multiCountLabel, btnGroup);

                if (this.searchWrap) {
                    this.dropdown.insertBefore(this.multiHeader, this.searchWrap.nextSibling);
                } else {
                    this.dropdown.insertBefore(this.multiHeader, this.dropdown.firstChild);
                }
            }
        }

        readOptions() {
            this.ensureSearchAndMultiHeader();
            this.optionsList.replaceChildren();
            this.optionItems = [];

            Array.from(this.select.options).forEach((opt, idx) => {
                const item = document.createElement('div');
                item.className = 'crm-select-option';
                item.dataset.index = idx;
                item.dataset.value = opt.value;
                item.dataset.normalizedText = normalizeText(opt.text);

                if (this.isMulti) {
                    const label = document.createElement('label');
                    label.className = 'crm-select-checkbox-label';
                    const cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.checked = opt.selected;
                    cb.tabIndex = -1;
                    const txt = document.createElement('span');
                    txt.textContent = opt.text;

                    label.append(cb, txt);
                    item.append(label);

                    if (opt.selected) {
                        item.classList.add('is-selected');
                    }

                    item.addEventListener('click', (e) => {
                        e.preventDefault();
                        opt.selected = !opt.selected;
                        cb.checked = opt.selected;
                        item.classList.toggle('is-selected', opt.selected);
                        this.triggerChange();
                    });
                } else {
                    const txt = document.createElement('span');
                    txt.textContent = opt.text;
                    item.append(txt);

                    if (opt.selected) {
                        item.classList.add('is-selected');
                        const check = document.createElement('i');
                        check.className = 'bi bi-check2 crm-select-option-check';
                        item.append(check);
                    }

                    item.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.selectOption(opt);
                    });
                }

                this.optionsList.append(item);
                this.optionItems.push(item);
            });
        }

        updateTrigger() {
            if (this.isMulti) {
                const selectedOpts = Array.from(this.select.selectedOptions).filter(o => o.value !== '');
                this.labelContainer.replaceChildren();

                if (selectedOpts.length === 0) {
                    this.labelContainer.textContent = this.select.options[0]?.text || i18n.selectOption();
                    this.labelContainer.classList.add('is-placeholder');
                } else {
                    this.labelContainer.classList.remove('is-placeholder');
                    const chipsContainer = document.createElement('span');
                    chipsContainer.className = 'crm-select-trigger-chips';

                    if (selectedOpts.length <= 2) {
                        selectedOpts.forEach(o => {
                            const chip = document.createElement('span');
                            chip.className = 'crm-select-chip';
                            chip.textContent = o.text;
                            chipsContainer.appendChild(chip);
                        });
                    } else {
                        const chip = document.createElement('span');
                        chip.className = 'crm-select-chip';
                        chip.textContent = selectedOpts[0].text;
                        const count = document.createElement('span');
                        count.className = 'crm-select-chip-count';
                        count.textContent = `+${selectedOpts.length - 1}`;
                        chipsContainer.append(chip, count);
                    }
                    this.labelContainer.appendChild(chipsContainer);
                }

                if (this.multiCountLabel) {
                    this.multiCountLabel.textContent = i18n.selectedCount(selectedOpts.length);
                }
            } else {
                const sel = this.select.selectedOptions[0] || this.select.options[0];
                this.labelContainer.textContent = sel ? sel.text : i18n.selectOption();
                this.labelContainer.classList.toggle('is-placeholder', !sel || sel.value === '');
            }
        }

        selectOption(opt) {
            this.select.value = opt.value;
            this.triggerChange();
            this.close();
            this.trigger.focus();
        }

        selectAll() {
            Array.from(this.select.options).forEach(o => {
                if (o.value !== '') o.selected = true;
            });
            this.triggerChange();
        }

        clearAll() {
            Array.from(this.select.options).forEach(o => { o.selected = false; });
            this.triggerChange();
        }

        syncSelectedUI() {
            this.updateTrigger();
            this.optionItems.forEach(item => {
                const idx = Number(item.dataset.index);
                const opt = this.select.options[idx];
                if (!opt) return;

                item.classList.toggle('is-selected', opt.selected);
                const cb = item.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = opt.selected;

                const check = item.querySelector('.crm-select-option-check');
                if (!this.isMulti) {
                    if (opt.selected && !check) {
                        const newCheck = document.createElement('i');
                        newCheck.className = 'bi bi-check2 crm-select-option-check';
                        item.append(newCheck);
                    } else if (!opt.selected && check) {
                        check.remove();
                    }
                }
            });
        }

        triggerChange() {
            this.select.dispatchEvent(new Event('input', { bubbles: true }));
            this.select.dispatchEvent(new Event('change', { bubbles: true }));
            this.syncSelectedUI();
        }

        filterOptions(query) {
            const q = normalizeText(query);
            let visibleCount = 0;

            this.optionItems.forEach(item => {
                const text = item.dataset.normalizedText || '';
                const matches = !q || text.includes(q);
                item.style.display = matches ? '' : 'none';
                if (matches) visibleCount++;
            });

            this.noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        open() {
            document.querySelectorAll('.crm-select-wrap.is-open').forEach(el => {
                if (el !== this.wrap) {
                    el.classList.remove('is-open');
                    el.closest('.field')?.classList.remove('is-dropdown-open');
                }
            });

            this.isOpen = true;
            this.wrap.classList.add('is-open');
            this.wrap.closest('.field')?.classList.add('is-dropdown-open');
            this.trigger.setAttribute('aria-expanded', 'true');

            if (this.searchInput) {
                this.searchInput.value = '';
                this.filterOptions('');
                setTimeout(() => this.searchInput.focus(), 50);
            }
        }

        close() {
            this.isOpen = false;
            this.wrap.classList.remove('is-open');
            this.wrap.closest('.field')?.classList.remove('is-dropdown-open');
            this.trigger.setAttribute('aria-expanded', 'false');
        }

        toggle() {
            if (this.isOpen) this.close();
            else this.open();
        }

        handleKeydown(e) {
            if (e.key === 'Escape') {
                this.close();
                this.trigger.focus();
                return;
            }
            if (!this.isOpen && (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown')) {
                e.preventDefault();
                this.open();
                return;
            }
        }
    }

    window.CrmSelect = {
        enhanceAll(rootEl = document) {
            const selects = rootEl.querySelectorAll('select:not([data-crm-enhanced]):not([data-native="true"])');
            selects.forEach(select => {
                const count = select.options.length;
                const isMulti = select.hasAttribute('multiple') || select.dataset.multi === 'true';
                const isSearchable = select.dataset.searchable === 'true';

                if (count > 15 || isMulti || isSearchable) {
                    new CrmSelectWidget(select);
                }
            });
        }
    };

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.crm-select-wrap')) {
            document.querySelectorAll('.crm-select-wrap.is-open').forEach(el => {
                el.classList.remove('is-open');
                el.closest('.field')?.classList.remove('is-dropdown-open');
            });
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.CrmSelect.enhanceAll());
    } else {
        window.CrmSelect.enhanceAll();
    }

    const selectObserver = new MutationObserver(() => {
        window.CrmSelect.enhanceAll();
    });
    selectObserver.observe(document.documentElement, { childList: true, subtree: true });
})();

// --- AUTO-DISMISS FLASH SUCCESS MESSAGES (5 SECONDS) ---
(() => {
    const DISMISS_DELAY = 5000;
    const ANIMATION_DURATION = 400;

    const setupAutoDismiss = (alertEl) => {
        if (!alertEl || alertEl.dataset.crmAutoDismissInit === '1') {
            return;
        }
        alertEl.dataset.crmAutoDismissInit = '1';

        // Prepare smooth transition styles
        alertEl.style.transition = `opacity ${ANIMATION_DURATION}ms ease, transform ${ANIMATION_DURATION}ms ease, max-height ${ANIMATION_DURATION}ms ease, margin ${ANIMATION_DURATION}ms ease, padding ${ANIMATION_DURATION}ms ease`;
        alertEl.style.maxHeight = `${alertEl.offsetHeight + 40}px`;
        alertEl.style.boxSizing = 'border-box';

        let timeoutId = null;
        let isDismissed = false;

        const dismiss = () => {
            if (isDismissed) return;
            isDismissed = true;
            clearTimeout(timeoutId);

            alertEl.style.opacity = '0';
            alertEl.style.transform = 'translateY(-8px)';
            alertEl.style.maxHeight = '0';
            alertEl.style.marginTop = '0';
            alertEl.style.marginBottom = '0';
            alertEl.style.paddingTop = '0';
            alertEl.style.paddingBottom = '0';
            alertEl.style.overflow = 'hidden';
            alertEl.style.pointerEvents = 'none';

            setTimeout(() => {
                alertEl.remove();
            }, ANIMATION_DURATION);
        };

        const startTimer = () => {
            if (isDismissed) return;
            timeoutId = setTimeout(dismiss, DISMISS_DELAY);
        };

        const pauseTimer = () => {
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }
        };

        // Pause countdown on mouse hover, resume on mouse leave
        alertEl.addEventListener('mouseenter', pauseTimer);
        alertEl.addEventListener('mouseleave', startTimer);

        // Close button
        if (!alertEl.querySelector('.crm-flash-close, .close')) {
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'crm-flash-close';
            closeBtn.innerHTML = '&times;';
            closeBtn.setAttribute('aria-label', 'Close');
            closeBtn.style.cssText = 'background:transparent;border:0;font-size:18px;font-weight:900;line-height:1;cursor:pointer;opacity:0.6;padding:0 6px;margin-inline-start:auto;color:inherit;vertical-align:middle;';
            closeBtn.addEventListener('mouseenter', () => { closeBtn.style.opacity = '1'; });
            closeBtn.addEventListener('mouseleave', () => { closeBtn.style.opacity = '0.6'; });
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dismiss();
            });

            const originalDisplay = window.getComputedStyle(alertEl).display;
            if (originalDisplay === 'block' || originalDisplay === 'grid') {
                alertEl.style.display = 'flex';
                alertEl.style.alignItems = 'center';
                alertEl.style.justifyContent = 'space-between';
            }
            alertEl.appendChild(closeBtn);
        }

        startTimer();
    };

    const initAllFlashes = (rootEl = document) => {
        const flashes = rootEl.querySelectorAll('.flash.success, .alert.success, .flash:not(.error):not(.danger), .alert:not(.error):not(.danger):not(.warning)');
        flashes.forEach(setupAutoDismiss);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initAllFlashes());
    } else {
        initAllFlashes();
    }

    const flashObserver = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1) {
                    if (node.matches && node.matches('.flash.success, .alert.success, .flash:not(.error):not(.danger), .alert:not(.error):not(.danger):not(.warning)')) {
                        setupAutoDismiss(node);
                    } else if (node.querySelectorAll) {
                        initAllFlashes(node);
                    }
                }
            });
        });
    });

    flashObserver.observe(document.documentElement, { childList: true, subtree: true });
})();
