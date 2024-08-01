// Give the service worker access to Firebase Messaging.
// Note that you can only use Firebase Messaging here. Other Firebase libraries
// are not available in the service worker.importScripts('https://www.gstatic.com/firebasejs/7.23.0/firebase-app.js');
importScripts("https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js");
importScripts("https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js");
/*
Initialize the Firebase app in the service worker by passing in the messagingSenderId.
*/

firebase.initializeApp({
    apiKey: "AIzaSyC2MCP6Uch-GTGrD1HHvaDu59D8aoRxNxk",
    authDomain: "smart-qr-999ef.firebaseapp.com",
    projectId: "smart-qr-999ef",
    storageBucket: "smart-qr-999ef.appspot.com",
    messagingSenderId: "279084982805",
    appId: "1:279084982805:web:ead09fcbdf27df05b8e41c",
});

const messaging = firebase.messaging();
messaging.onBackgroundMessage(function (payload) {
    const notificationTitle =
        payload.notification.title || "Hello world is awesome";
    const notificationOptions = {
        body: payload.notification.body || "Your notification message.",
        icon: payload.notification.icon || '',
        data: payload.data,
        icon: payload.notification.sound || 'default',
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});
