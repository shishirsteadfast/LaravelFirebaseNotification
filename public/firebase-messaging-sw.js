importScripts("https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js");
importScripts("https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js");

firebase.initializeApp({
    apiKey: "AIzaSyClet_kwi9fHIfPncjxADJQdh9C8zXDOSI",
    authDomain: "laravelfirebasenotificat-757c1.firebaseapp.com",
    projectId: "laravelfirebasenotificat-757c1",
    storageBucket: "laravelfirebasenotificat-757c1.appspot.com",
    messagingSenderId: "35403695754",
    appId: "1:35403695754:web:3e64224336a3be70108f73",
    measurementId: "G-H6HVRW6VM1",
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(({ data: { title, body, icon } }) => {
    self.registration.showNotification(title, { body, icon });
});
