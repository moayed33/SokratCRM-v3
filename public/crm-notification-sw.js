self.addEventListener('push', event => {
 let payload = {};
 try {
  payload = event.data ? event.data.json() : {};
 } catch (_) {
  payload = { title: 'SokratCRM', body: event.data ? event.data.text() : '' };
 }

 event.waitUntil(self.registration.showNotification(payload.title || 'SokratCRM', {
  body: payload.body || '',
  icon: '/favicon.png',
  badge: '/favicon.png',
  tag: payload.tag || 'crm-notification',
  renotify: payload.priority === 'urgent',
  data: { url: payload.url || '/' },
 }));
});

self.addEventListener('notificationclick', event => {
 event.notification.close();
 const targetUrl = new URL(event.notification.data?.url || '/', self.location.origin).href;
 event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
  for (const client of windowClients) {
   if (client.url === targetUrl && 'focus' in client) return client.focus();
  }
  return clients.openWindow ? clients.openWindow(targetUrl) : undefined;
 }));
});
