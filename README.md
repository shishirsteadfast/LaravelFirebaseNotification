# Laravel Firebase Push Notifications

This guide provides step-by-step instructions to integrate Firebase Cloud Messaging (FCM) into a Laravel application for sending push notifications to web and mobile devices.

## Table of Contents
- [Prerequisites](#prerequisites)
- [Setup Firebase Project](#setup-firebase-project)
- [Configure Laravel Project](#configure-laravel-project)
- [Frontend Setup for Web Notifications](#frontend-setup-for-web-notifications)
- [Usage](#usage)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

## Prerequisites
- A Laravel project (version 8.x or higher recommended).
- A Firebase account and access to the [Firebase Console](https://console.firebase.google.com/).
- Composer installed for managing PHP dependencies.
- Node.js and npm for managing JavaScript dependencies.
- Basic knowledge of Laravel, PHP, and JavaScript.

## Setup Firebase Project

1. **Create a Firebase Project**:
   - Go to the [Firebase Console](https://console.firebase.google.com/).
   - Click **Add Project**, provide a project name (e.g., `ExampleAPP`), and follow the prompts to create the project.
   - Note the **Project ID** from the **Project Settings** > **General** tab (e.g., `example-app`).

2. **Generate Service Account Key**:
   - In the Firebase Console, navigate to **Project Settings** > **Service Accounts**.
   - Click **Generate new private key** and confirm. This will download a JSON file (e.g., `serviceAccountKey.json`).
   - Save this file in your Laravel project under `storage/app/firebase/` and rename it to `firebase_credentials.json`.

3. **Add Web App to Firebase**:
   - In **Project Settings** > **General**, scroll to **Your apps** and click the web icon (`</>`).
   - Register a web app (e.g., `Laravel Web App`) and copy the Firebase configuration details (e.g., `apiKey`, `authDomain`, `projectId`, etc.).
   - Example configuration:
     ```json
     {
       "apiKey": "your-api-key",
       "authDomain": "your-auth-domain",
       "projectId": "your-project-id",
       "storageBucket": "your-storage-bucket",
       "messagingSenderId": "your-messaging-sender-id",
       "appId": "your-app-id",
       "measurementId": "your-measurement-id"
     }
     ```

4. **Get VAPID Key**:
   - Go to **Project Settings** > **Cloud Messaging** > **Web Push certificates**.
   - Copy the **Public Key** (VAPID key) for use in the frontend.

## Configure Laravel Project

1. **Set Environment Variables**:
   - Open your `.env` file and add the following:
     ```env
     FIREBASE_CREDENTIALS=app/firebase/firebase_credentials.json
     FIREBASE_PROJECT_ID=example-app
     ## For web application
     FIREBASE_VAPID_KEY=your-vapid-key-here 
     ```
   - Replace `your-vapid-key-here` with the VAPID key from the Firebase Console.

2. **Update Services Configuration**:
   - In `config/services.php`, add the Firebase configuration:
     ```php
     'firebase' => [
         'credentials' => storage_path(env('FIREBASE_CREDENTIALS')),
         'project_id' => env('FIREBASE_PROJECT_ID'),
     ],
     ```

3. **Add FCM Token Column to Users Table**:
   - Create a migration to add an `fcm_token` column to the `users` table:
     ```php
     use Illuminate\Database\Migrations\Migration;
     use Illuminate\Database\Schema\Blueprint;
     use Illuminate\Support\Facades\Schema;

     class AddFcmTokenToUsersTable extends Migration
     {
         public function up()
         {
             Schema::table('users', function (Blueprint $table) {
                 $table->string('fcm_token')->nullable();
             });
         }

         public function down()
         {
             Schema::table('users', function (Blueprint $table) {
                 $table->dropColumn('fcm_token');
             });
         }
     }
     ```
   - Run the migration:
     ```bash
     php artisan migrate
     ```

4. **Install Google Auth Library**:
   - Install the required package for OAuth authentication:
     ```bash
     composer require google/auth
     ```

5. **Create Firebase Notification Service**:
   - Create a file `app/Services/Notifications/FireBase.php` with the following content:
     ```php
     <?php

     namespace App\Services\Notifications;

     use Exception;
     use Google\Auth\Credentials\ServiceAccountCredentials;
     use GuzzleHttp\Client;

     class FireBase
     {
         public static function send($heading, $message, $deviceIds, $data = [])
         {
             $deviceIds = array_values(array_filter($deviceIds));
             if (empty($deviceIds)) {
                 throw new Exception('No device IDs provided');
             }

             $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
             $credentials = new ServiceAccountCredentials($scopes, config('services.firebase.credentials'));
             $accessToken = $credentials->fetchAuthToken()['access_token'];
             $projectId = config('services.firebase.project_id');

             $messagePayload = [
                 'notification' => [
                     'title' => $heading,
                     'body' => $message,
                 ],
                 'android' => [
                     'priority' => 'high',
                     'notification' => [
                         'sound' => 'default',
                     ],
                 ],
                 'apns' => [
                     'payload' => [
                         'aps' => [
                             'sound' => 'default',
                         ],
                     ],
                 ],
             ];

             if (!empty($data)) {
                 $messagePayload['data'] = $data;
             }

             if (count($deviceIds) > 1) {
                 $messagePayload['tokens'] = $deviceIds;
             } else {
                 $messagePayload['token'] = $deviceIds[0];
             }

             $payload = ['message' => $messagePayload];
             $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

             $client = new Client();

             $response = $client->request('POST', $url, [
                 'headers' => [
                     'Authorization' => 'Bearer ' . $accessToken,
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json',
                 ],
                 'json' => $payload,
             ]);

             return $response;
         }
     }
     ```

## Frontend Setup for Web Notifications

1. **Create Service Worker**:
   - Create a file `public/firebase-messaging-sw.js` with the following content:
     ```javascript
     importScripts("https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js");
     importScripts("https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js");

     firebase.initializeApp({
         apiKey: "your-api-key",
         authDomain: "your-auth-domain",
         projectId: "your-project-id",
         storageBucket: "your-storage-bucket",
         messagingSenderId: "your-messaging-sender-id",
         appId: "your-app-id",
         measurementId: "your-measurement-id",
     });

     const messaging = firebase.messaging();

     messaging.onBackgroundMessage(({ data: { title, body, icon } }) => {
         self.registration.showNotification(title, { body, icon });
     });
     ```
   - Replace the Firebase configuration values with those from **Project Settings** > **General** > **Your apps** > **Web app**.

2. **Add Firebase Messaging Script**:
   - In your frontend (e.g., in a Blade template like `resources/views/layouts/app.blade.php`), add the following script:
     ```html
     <script type="module">
         import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js";
         import { getMessaging, getToken, onMessage } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-messaging.js";
         import { getAnalytics } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-analytics.js";

         const firebaseConfig = {
             apiKey: "your-api-key",
             authDomain: "your-auth-domain",
             projectId: "your-project-id",
             storageBucket: "your-storage-bucket",
             messagingSenderId: "your-messaging-sender-id",
             appId: "your-app-id",
             measurementId: "your-measurement-id"
         };

         const app = initializeApp(firebaseConfig);
         const analytics = getAnalytics(app);
         const messaging = getMessaging(app);

         async function initFirebaseMessagingRegistration() {
             try {
                 const permission = await Notification.requestPermission();
                 if (permission === 'granted') {
                     const token = await getToken(messaging, {
                         vapidKey: "{{ env('FIREBASE_VAPID_KEY') }}"
                     });

                     if (token) {
                         await axios.post("/firebase/token", {
                             _method: "PATCH",
                             fcm_token: token
                         });
                     } else {
                         console.warn("No registration token available.");
                     }
                 } else {
                     console.warn("Notification permission denied.");
                 }
             } catch (err) {
                 console.error("Error retrieving token: ", err);
             }
         }

         onMessage(messaging, (payload) => {
             const { title, body } = payload.notification;
             new Notification(title, { body });
         });

         initFirebaseMessagingRegistration();
     </script>
     ```
   - Replace the Firebase configuration values with those from **Project Settings** > **General** > **Your apps** > **Web app**.
   - Ensure Axios is included in your project (e.g., via CDN or npm).
   - Update the `/firebase/token` route to match your Laravel route for storing the FCM token (e.g., `route('firebase.token')`).

3. **Create Route for Storing FCM Token**:
   - In `routes/web.php`, add a route to handle FCM token storage:
     ```php
     Route::patch('/firebase/token', [App\Http\Controllers\FirebaseController::class, 'updateToken'])->name('firebase.token');
     ```

4. **Create Controller for FCM Token**:
   - Create a controller `app/Http/Controllers/FirebaseController.php`:
     ```php
     <?php

     namespace App\Http\Controllers;

     use Illuminate\Http\Request;
     use Illuminate\Support\Facades\Auth;

     class FirebaseController extends Controller
     {
         public function updateToken(Request $request)
         {
             $user = Auth::user();
             $user->update(['fcm_token' => $request->fcm_token]);
             return response()->json(['success' => true]);
         }
     }
     ```

## Usage

To send a push notification, use the `FireBase` service class. Example:

```php
use App\Services\Notifications\FireBase;

try {
    $deviceIds = ['user-fcm-token-here']; // Array of FCM tokens
    $response = FireBase::send(
        heading: 'Test Notification',
        message: 'This is a test push notification!',
        deviceIds: $deviceIds,
        data: ['key' => 'value'] // Optional data payload
    );
    echo 'Notification sent successfully!';
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

## Troubleshooting

- **Permission Denied**: Ensure the user has granted notification permissions in the browser.
- **Invalid Token**: Verify that the FCM token is correctly stored in the `users` table.
- **Firebase Config Errors**: Double-check the Firebase configuration in `firebase-messaging-sw.js` and the frontend script.
- **Service Account Issues**: Ensure the `firebase_credentials.json` file is correctly placed in `storage/app/firebase/` and is accessible.
- **CORS Issues**: Ensure your Laravel routes allow CORS if the frontend is hosted separately.

## Contributing

Contributions are welcome! Please submit a pull request or open an issue for any bugs, improvements, or feature requests.

## License

This project is licensed under the MIT License.