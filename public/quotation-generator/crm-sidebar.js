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
  const title = collapsed ? 'توسيع القائمة الجانبية' : 'طي القائمة الجانبية';

  collapseButton.setAttribute('aria-label', title);
  collapseButton.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
  collapseButton.setAttribute('title', title);

  if (label) {
   label.textContent = collapsed ? 'توسيع القائمة' : 'طي القائمة';
  }

  if (icon) {
   icon.className = collapsed
    ? 'bi bi-chevron-double-left'
    : 'bi bi-chevron-double-right';
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
