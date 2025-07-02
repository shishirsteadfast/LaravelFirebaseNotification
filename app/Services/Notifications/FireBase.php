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
            return throw new Exception('No device IDs provided');
        }

        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = new ServiceAccountCredentials($scopes, config('services.firebase.credentials'));
        $accessToken = $credentials->fetchAuthToken()['access_token'];
        $projectId = config('services.firebase.project_id');

        // Build the message array
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

        // Only add 'data' if it's not empty
        if (!empty($data)) {
            $messagePayload['data'] = $data;
        }

        // Decide whether to send to single token or multiple
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
