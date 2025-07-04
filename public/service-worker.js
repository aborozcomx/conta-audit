self.addEventListener('push', function (event) {
    const data = event.data.json();
    const title = data.title || 'Nueva Notificación';
    const options = {
        body: data.body,
        actions: data.actions || [],
    };
    event.waitUntil(self.registration.showNotification(title, options));
});
