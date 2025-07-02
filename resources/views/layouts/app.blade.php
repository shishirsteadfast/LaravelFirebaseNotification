<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
</body>
<script type="module">
    import {
        initializeApp
    } from "https://www.gstatic.com/firebasejs/11.9.1/firebase-app.js";
    import {
        getMessaging,
        getToken,
        onMessage
    } from "https://www.gstatic.com/firebasejs/11.9.1/firebase-messaging.js";
    import {
        getAnalytics
    } from "https://www.gstatic.com/firebasejs/11.9.1/firebase-analytics.js";

    // Your web app's Firebase configuration
    const firebaseConfig = {
        apiKey: "AIzaSyClet_kwi9fHIfPncjxADJQdh9C8zXDOSI",
        authDomain: "laravelfirebasenotificat-757c1.firebaseapp.com",
        projectId: "laravelfirebasenotificat-757c1",
        storageBucket: "laravelfirebasenotificat-757c1.appspot.com",
        messagingSenderId: "35403695754",
        appId: "1:35403695754:web:3e64224336a3be70108f73",
        measurementId: "G-H6HVRW6VM1"
    };

    // Initialize Firebase
    const app = initializeApp(firebaseConfig);
    const analytics = getAnalytics(app);
    const messaging = getMessaging(app);

    // Request permission and get token
    async function initFirebaseMessagingRegistration() {
        try {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                const token = await getToken(messaging, {
                    // vapidKey: 'BFqRYyBoSvRAf08KAuGllpiJEe7GT1RQa6_MndhoJB8wShgZ-g_6drxCgRVNt23TIQw4yBywK71JdE5XC41yUVQ'
                    vapidKey: '{{ env('FIREBASE_VAPID_KEY') }}'
                });

                if (token) {
                    // Send token to your server
                    await axios.post("{{ route('firebase.token') }}", {
                        _method: "PATCH",
                        fcm_token: token
                    });
                } else {
                    console.warn("No registration token available. Request permission to generate one.");
                }
            } else {
                console.warn("Notification permission denied");
            }
        } catch (err) {
            console.error("An error occurred while retrieving token. ", err);
        }
    }

    // Listen for foreground messages
    onMessage(messaging, (payload) => {
        const {
            title,
            body
        } = payload.notification;
        new Notification(title, {
            body
        });
    });

    initFirebaseMessagingRegistration();
</script>

</html>
