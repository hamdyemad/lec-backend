<?php


namespace App\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Pusher\PushNotifications\PushNotifications;

class FirebaseService
{

    public $app_id;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(public GoogleCloudService $googleCloudService)
    {
        $this->app_id = "lec-app-d7b6f";
    }

    public function send_notification($token, $title, $body, $otherData = [])
    {
        $access_token = 'Bearer ' . $this->googleCloudService->getAccessToken();
        $data = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'android' => [
                    'notification' => [
                        'title' => $title,
                        'body' => $body
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'body' => $body
                            ]
                        ]
                    ]
                ],
                'data' => $otherData

            ]
        ];

        $response = Http::withHeaders([
            'Authorization' => $access_token
        ])->post("https://fcm.googleapis.com/v1/projects/$this->app_id/messages:send", $data);
        return $response;
    }
}
