(() => {
 'use strict';

 const centerInstances = Array.from(document.querySelectorAll('#crmNotificationCenter'));
 if (!centerInstances.length) return;
 const center = centerInstances[0];
 for (let i = 1; i < centerInstances.length; i++) {
  centerInstances[i].remove();
 }
 if (center.parentElement && center.parentElement !== document.body) {
  document.body.appendChild(center);
 }

 const triggers = () => Array.from(document.querySelectorAll('.crm-notification-trigger, #crmNotificationTrigger'));
 const badges = () => Array.from(document.querySelectorAll('.crm-notification-badge, #crmNotificationBadge'));

 const drawer = document.getElementById('crmNotificationDrawer');
 const list = document.getElementById('crmNotificationList');
 const readAll = document.getElementById('crmNotificationReadAll');
 const loadMore = document.getElementById('crmNotificationLoadMore');
 const feedback = document.getElementById('crmNotificationFeedback');
 const toast = document.getElementById('crmNotificationToast');
 const toastTitle = document.getElementById('crmNotificationToastTitle');
 const toastBody = document.getElementById('crmNotificationToastBody');
 const config = center.dataset;
 const locale = config.locale === 'en' ? 'en' : 'ar';
 const baseUrl = config.baseUrl.replace(/\/$/, '');
 const pollMs = Math.max(15, Number(config.pollSeconds || 60)) * 1000;
 let filter = 'unread';
 let page = 1;
 let lastPage = 1;
 let unreadCount = null;
 let loading = false;
 let toastTimer = null;
 let feedbackTimer = null;
 let eventSource = null;
 let streamConnected = false;
 let realtimeSignature = null;

 const request = async (url, options = {}) => {
  const response = await fetch(url, {
   credentials: 'same-origin',
   ...options,
   headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...(options.method && options.method !== 'GET' ? {
     'Content-Type': 'application/json',
     'X-CSRF-TOKEN': config.csrf,
    } : {}),
    ...(options.headers || {}),
   },
  });

  if (!response.ok) {
   const payload = await response.json().catch(() => ({}));
   throw new Error(payload.message || config.labelError);
  }

  return response.status === 204 ? {} : response.json();
 };

 const announce = (message, type = 'success', retry = null) => {
  if (!feedback) return;
  clearTimeout(feedbackTimer);
  feedback.replaceChildren();
  feedback.className = `crm-notification-feedback ${type}`;
  const text = document.createElement('span');
  text.textContent = message;
  feedback.append(text);
  if (typeof retry === 'function') {
   const retryButton = document.createElement('button');
   retryButton.type = 'button';
   retryButton.textContent = config.labelRetry;
   retryButton.addEventListener('click', retry, { once: true });
   feedback.append(retryButton);
  }
  feedback.hidden = false;
  if (type !== 'working') {
   feedbackTimer = setTimeout(() => { feedback.hidden = true; }, 5000);
  }
 };

 const setItemBusy = (article, busy) => {
  if (!article) return;
  article.classList.toggle('is-busy', busy);
  article.setAttribute('aria-busy', busy ? 'true' : 'false');
  article.querySelectorAll('.crm-notification-actions button, .crm-notification-actions select')
   .forEach(control => { control.disabled = busy; });
  article.querySelectorAll('.crm-notification-actions a').forEach(link => {
   link.classList.toggle('is-disabled', busy);
   link.setAttribute('aria-disabled', busy ? 'true' : 'false');
  });
 };

 const clearItemError = article => {
  article?.querySelector('.crm-notification-action-error')?.remove();
 };

 const showItemError = (article, message, retry) => {
  if (!article) return;
  clearItemError(article);
  const error = document.createElement('div');
  error.className = 'crm-notification-action-error';
  error.setAttribute('role', 'alert');
  const text = document.createElement('span');
  text.textContent = message;
  const retryButton = document.createElement('button');
  retryButton.type = 'button';
  retryButton.textContent = config.labelRetry;
  retryButton.addEventListener('click', retry, { once: true });
  error.append(text, retryButton);
  article.append(error);
 };

 const setBadge = count => {
  const safeCount = Math.max(0, Number(count || 0));
  badges().forEach(badgeNode => {
   badgeNode.textContent = safeCount > 99 ? '99+' : String(safeCount);
   badgeNode.hidden = safeCount === 0;
  });
  triggers().forEach(triggerNode => {
   triggerNode.setAttribute('aria-label', `${triggerNode.dataset.label || 'Notifications'} (${safeCount})`);
  });
 };

 const showToast = item => {
  if (!item || center.classList.contains('is-open')) return;
  toastTitle.textContent = item.title;
  const stageSuffix = item.stage_name ? ` (${locale === 'ar' ? 'المرحلة: ' : 'Stage: '}${item.stage_name})` : '';
  toastBody.textContent = (item.body || '') + stageSuffix;
  toast.hidden = false;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { toast.hidden = true; }, 5500);
 };

 const refreshCount = async () => {
  if (document.hidden) return;
  try {
   const payload = await request(config.countUrl);
   const nextCount = Number(payload.count || 0);
   if (unreadCount !== null && nextCount > unreadCount) {
    const latest = await request(`${config.indexUrl}?filter=unread&per_page=1`);
    showToast(latest.data && latest.data[0]);
   }
   unreadCount = nextCount;
   setBadge(nextCount);
   if (center.classList.contains('is-open') && filter === 'unread') loadNotifications(1);
  } catch (_) {}
 };

 const resolveActionUrl = rawUrl => {
  if (!rawUrl || typeof rawUrl !== 'string') return '';
  try {
   const parsed = new URL(rawUrl, window.location.origin);
   if (parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1' || parsed.origin === window.location.origin) {
    return `${parsed.pathname}${parsed.search}${parsed.hash}`;
   }
   return parsed.href;
  } catch (_) {
   return rawUrl;
  }
 };

 const relativeTime = iso => {
  if (!iso) return '';
  const seconds = Math.round((new Date(iso).getTime() - Date.now()) / 1000);
  const formatter = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });
  if (Math.abs(seconds) < 60) return formatter.format(seconds, 'second');
  const minutes = Math.round(seconds / 60);
  if (Math.abs(minutes) < 60) return formatter.format(minutes, 'minute');
  const hours = Math.round(minutes / 60);
  if (Math.abs(hours) < 24) return formatter.format(hours, 'hour');
  return formatter.format(Math.round(hours / 24), 'day');
 };

 const stateNode = (icon, title, body = '', action = null) => {
  const state = document.createElement('div');
  state.className = 'crm-notification-state';
  const wrap = document.createElement('div');
  const iconNode = document.createElement('i');
  iconNode.className = `bi ${icon}`;
  iconNode.setAttribute('aria-hidden', 'true');
  const strong = document.createElement('strong');
  strong.textContent = title;
  wrap.append(iconNode, strong);
  if (body) {
   const span = document.createElement('span');
   span.textContent = body;
   wrap.append(span);
  }
  if (typeof action === 'function') {
   const retry = document.createElement('button');
   retry.type = 'button';
   retry.className = 'crm-notification-state-action';
   retry.textContent = config.labelRetry;
   retry.addEventListener('click', action, { once: true });
   wrap.append(retry);
  }
  state.append(wrap);
  return state;
 };

 const mutate = async (
  item,
  action,
  method = 'PATCH',
  body = null,
  context = {},
 ) => {
  const article = context.article || null;
  const retry = () => mutate(item, action, method, body, context);
  clearItemError(article);
  setItemBusy(article, true);
  announce(config.labelActionWorking, 'working');

  try {
   await request(`${baseUrl}/${encodeURIComponent(item.id)}${action}`, {
    method,
    body: body ? JSON.stringify(body) : undefined,
   });
   if (context.successMessage) {
    announce(context.successMessage);
   }
   await Promise.all([loadNotifications(1), refreshCount()]);
   if (typeof context.onSuccess === 'function') {
    context.onSuccess();
   }
  } catch (error) {
   const message = `${config.labelActionFailed} ${error.message}`.trim();
   showItemError(article, message, retry);
   announce(message, 'error', retry);
  } finally {
   if (article?.isConnected) {
    setItemBusy(article, false);
   }
  }
 };

 const createItem = item => {
  const article = document.createElement('article');
  article.className = `crm-notification-item${item.read_at ? '' : ' is-unread'}`;
  article.dataset.priority = item.priority;
  article.dataset.notificationId = item.id;

  const main = document.createElement('div');
  main.className = 'crm-notification-item-main';
  const dot = document.createElement('span');
  dot.className = 'crm-notification-priority';
  dot.setAttribute('aria-hidden', 'true');
  const copy = document.createElement('div');
  copy.className = 'crm-notification-copy';
  const title = document.createElement('strong');
  title.textContent = item.title;
  const body = document.createElement('p');
  body.textContent = item.body;

  let stageNode = null;
  if (item.stage_name) {
   stageNode = document.createElement('div');
   stageNode.className = 'crm-notification-stage';
   const stageLabel = document.createElement('span');
   stageLabel.className = 'crm-notification-stage-label';
   stageLabel.textContent = locale === 'ar' ? 'المرحلة: ' : 'Stage: ';

   const stageBadge = document.createElement('span');
   stageBadge.className = 'crm-notification-stage-badge';
   if (item.stage_color) {
    stageBadge.style.color = item.stage_color;
    stageBadge.style.borderColor = `${item.stage_color}40`;
    stageBadge.style.backgroundColor = `${item.stage_color}14`;
   }
   if (item.stage_icon) {
    const icon = document.createElement('i');
    icon.className = `bi ${item.stage_icon}`;
    icon.setAttribute('aria-hidden', 'true');
    stageBadge.append(icon);
   }
   const stageText = document.createElement('span');
   stageText.textContent = item.stage_name;
   stageBadge.append(stageText);
   stageNode.append(stageLabel, stageBadge);
  }

  const meta = document.createElement('div');
  meta.className = 'crm-notification-meta';
  meta.textContent = relativeTime(item.created_at);
  if (stageNode) {
   copy.append(title, body, stageNode, meta);
  } else {
   copy.append(title, body, meta);
  }
  main.append(dot, copy);
  article.append(main);

  const actions = document.createElement('div');
  actions.className = 'crm-notification-actions';
  const resolvedUrl = resolveActionUrl(item.action_url);
  if (resolvedUrl) {
   const open = document.createElement('a');
   open.className = 'primary';
   open.href = resolvedUrl;
   open.textContent = config.labelOpen;
   open.addEventListener('click', event => {
    if (article.classList.contains('is-busy')) {
     event.preventDefault();
     return;
    }
    if (!item.read_at) {
     event.preventDefault();
     void mutate(item, '/read', 'PATCH', null, {
      article,
      successMessage: config.labelMarkedRead,
      onSuccess: () => { window.location.assign(resolvedUrl); },
     });
    }
   });
   actions.append(open);
  }

  const readToggle = document.createElement('button');
  readToggle.type = 'button';
  readToggle.textContent = item.read_at ? config.labelMarkUnread : config.labelMarkRead;
  readToggle.addEventListener('click', () => {
   const markUnread = Boolean(item.read_at);
   void mutate(item, markUnread ? '/unread' : '/read', 'PATCH', null, {
    article,
    successMessage: markUnread ? config.labelMarkedUnread : config.labelMarkedRead,
   });
  });
  actions.append(readToggle);

  if (item.can_snooze) {
   const snooze = document.createElement('select');
   snooze.setAttribute('aria-label', config.labelSnooze);
   [
    ['', config.labelSnooze],
    ['15', locale === 'ar' ? '15 دقيقة' : '15 minutes'],
    ['60', locale === 'ar' ? 'ساعة' : '1 hour'],
    ['1440', locale === 'ar' ? 'غدًا' : 'Tomorrow'],
   ].forEach(([value, label]) => {
    const option = document.createElement('option');
    option.value = value;
    option.textContent = label;
    option.disabled = value === '';
    option.selected = value === '';
    snooze.append(option);
   });
   snooze.addEventListener('change', () => {
    const minutes = Number(snooze.value);
    void mutate(item, '/snooze', 'POST', { minutes }, {
     article,
     successMessage: config.labelSnoozed,
    });
   });
   actions.append(snooze);
  }

  const dismiss = document.createElement('button');
  dismiss.type = 'button';
  dismiss.textContent = config.labelDismiss;
  dismiss.addEventListener('click', () => {
   void mutate(item, '', 'DELETE', null, {
    article,
    successMessage: config.labelDismissed,
   });
  });
  actions.append(dismiss);
  article.append(actions);
  return article;
 };

 const loadNotifications = async (requestedPage = 1) => {
  if (loading) return;
  loading = true;
  page = requestedPage;
  loadMore.disabled = true;
  if (page === 1) list.replaceChildren(stateNode('bi-arrow-repeat', config.labelLoading));

  try {
   const payload = await request(`${config.indexUrl}?filter=${encodeURIComponent(filter)}&page=${page}`);
   lastPage = Number(payload.meta.last_page || 1);
   const fragment = document.createDocumentFragment();
   (payload.data || []).forEach(item => fragment.append(createItem(item)));

   if (page === 1) list.replaceChildren();
   if (!(payload.data || []).length && page === 1) {
    list.append(stateNode('bi-bell-slash', config.labelEmpty));
   } else {
    list.append(fragment);
   }
   loadMore.hidden = page >= lastPage;
  } catch (error) {
   const retry = () => { void loadNotifications(requestedPage); };
   if (page === 1) {
    list.replaceChildren(stateNode('bi-exclamation-circle', config.labelError, error.message, retry));
   } else {
    announce(`${config.labelError} ${error.message}`.trim(), 'error', retry);
   }
  } finally {
   loading = false;
   loadMore.disabled = false;
  }
 };

 const openCenter = () => {
  center.classList.add('is-open');
  document.body.classList.add('crm-notification-open');
  drawer.setAttribute('aria-hidden', 'false');
  center.querySelector('[data-notification-close]').hidden = false;
  loadNotifications(1);
  setTimeout(() => drawer.querySelector('.crm-notification-close').focus(), 0);
 };

 let lastActiveTrigger = null;
 const closeCenter = () => {
  center.classList.remove('is-open');
  document.body.classList.remove('crm-notification-open');
  drawer.setAttribute('aria-hidden', 'true');
  center.querySelector('.crm-notification-backdrop').hidden = true;
  if (lastActiveTrigger && typeof lastActiveTrigger.focus === 'function') {
   lastActiveTrigger.focus();
  } else {
   const firstTrigger = triggers()[0];
   if (firstTrigger) firstTrigger.focus();
  }
 };

 const applyRealtimeState = async state => {
  const nextCount = Math.max(0, Number(state.count || 0));
  const signature = `${nextCount}:${Number(state.total || 0)}:${state.latest_id || ''}`;
  if (signature === realtimeSignature) return;
  realtimeSignature = signature;
  if (unreadCount !== null && nextCount > unreadCount) {
   try {
    const latest = await request(`${config.indexUrl}?filter=unread&per_page=1`);
    showToast(latest.data && latest.data[0]);
   } catch (_) {}
  }
  unreadCount = nextCount;
  setBadge(nextCount);
  if (center.classList.contains('is-open')) {
   void loadNotifications(1);
  }
 };

 const stopStream = () => {
  eventSource?.close();
  eventSource = null;
  streamConnected = false;
 };

 const startStream = () => {
  if (!config.streamUrl || !('EventSource' in window) || document.hidden || eventSource) return;
  const source = new EventSource(config.streamUrl, { withCredentials: true });
  eventSource = source;
  source.addEventListener('open', () => { streamConnected = true; });
  source.addEventListener('notifications', event => {
   try {
    void applyRealtimeState(JSON.parse(event.data));
   } catch (_) {}
  });
  source.addEventListener('error', () => { streamConnected = false; });
 };

 document.addEventListener('click', event => {
  const target = event.target.closest('.crm-notification-trigger, #crmNotificationTrigger');
  if (target) {
   event.preventDefault();
   lastActiveTrigger = target;
   openCenter();
  }
 });
 center.querySelectorAll('[data-notification-close]').forEach(node => node.addEventListener('click', closeCenter));
 center.querySelectorAll('[data-notification-filter]').forEach(button => {
  button.addEventListener('click', () => {
   filter = button.dataset.notificationFilter;
   center.querySelectorAll('[data-notification-filter]').forEach(candidate => {
    const active = candidate === button;
    candidate.classList.toggle('active', active);
    candidate.setAttribute('aria-selected', active ? 'true' : 'false');
   });
   loadNotifications(1);
  });
 });
 readAll.addEventListener('click', () => {
  const run = async () => {
   readAll.disabled = true;
   announce(config.labelActionWorking, 'working');
   try {
    await request(config.readAllUrl, { method: 'PATCH' });
    announce(config.labelAllRead);
    await Promise.all([loadNotifications(1), refreshCount()]);
   } catch (error) {
    announce(`${config.labelActionFailed} ${error.message}`.trim(), 'error', run);
   } finally {
    readAll.disabled = false;
   }
  };
  void run();
 });
 loadMore.addEventListener('click', () => { void loadNotifications(page + 1); });
 document.addEventListener('keydown', event => {
  if (!center.classList.contains('is-open')) return;
  if (event.key === 'Escape') {
   closeCenter();
   return;
  }
  if (event.key !== 'Tab') return;
  const focusable = [...drawer.querySelectorAll('button:not([disabled]),a[href],select:not([disabled])')]
   .filter(node => !node.hidden && node.offsetParent !== null);
  if (!focusable.length) return;
  const first = focusable[0];
  const last = focusable[focusable.length - 1];
  if (event.shiftKey && document.activeElement === first) {
   event.preventDefault();
   last.focus();
  } else if (!event.shiftKey && document.activeElement === last) {
   event.preventDefault();
   first.focus();
  }
 });
 document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
   stopStream();
   return;
  }
  void refreshCount();
  startStream();
 });
 window.addEventListener('pagehide', stopStream);

 void refreshCount();
 startStream();
 setInterval(() => {
  if (!streamConnected) void refreshCount();
 }, pollMs);
})();
