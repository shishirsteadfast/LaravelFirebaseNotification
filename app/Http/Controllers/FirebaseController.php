<?php

namespace App\Http\Controllers;

use App\Services\Notifications\FireBase;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FirebaseController extends Controller
{
    public function setToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json(['message' => 'FCM token updated successfully']);
    }

    public function sendNotification(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            $response = FireBase::send($request->title, $request->message, [request()->user()->fcm_token]);

            return response()->json([
                'message' => 'Notification sent successfully',
                'response' => json_decode($response->getBody()->getContents(), true),
            ]);
        } catch (ClientException $e) {
            return response()->json(
                [
                    'error' => 'Failed to send notification: ' . $e->getMessage(),
                    'details' => json_decode($e->getResponse()->getBody()->getContents(), true),
                ],
                $e->getCode(),
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'error' => 'Error sending notification: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
}
