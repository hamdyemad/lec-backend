<?php


namespace App\Service;

use Illuminate\Support\Facades\Http;
use Pusher\PushNotifications\PushNotifications;

class PushNotificaion {

    public $beams;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct() {
        $this->beams = new PushNotifications([
            'instanceId' => env('PUSHER_BEAM_INSTANCE_ID'),
            'secretKey'  => env('PUSHER_BEAM_SECRET_KEY'),
        ]);
    }

    public function generateToken($user_id)
    {
        $token = $this->beams->generateToken($user_id);
        return $token;
    }


    public function sendPushNotificationByUsers($users_ids, $title, $body,$icon = '',$data = [])
    {

        $beam_instance_key = env('PUSHER_BEAM_INSTANCE_ID');
        $url = "https://$beam_instance_key.pushnotifications.pusher.com/publish_api/v1/instances/$beam_instance_key/publishes/users";
        $data = [
            "users" => $users_ids,
            "apns" => [
                "aps" => [
                    "alert" => [
                        "title" => $title,
                        "body" => $body,
                        "icon" => $icon,
                    ],
                    "sound" => "default",
                    "notify_data" => $data
                ]
            ],
            "fcm" => [
                "notification" => [
                    "title" => $title,
                    "body" => $body,
                    "icon" => $icon,
                    "sound" => "default",
                ],
                'data' => $data
            ]
        ];
        $response = Http::withHeaders([
            'Authorization' => "Bearer ". env('PUSHER_BEAM_SECRET_KEY')
        ])->post($url, $data);
        return $response;
    }

    public function sendWebPushNotification($users, $message)
    {

        $instanceId = env('PUSHER_BEAM_INSTANCE_ID');
        $url = "https://$instanceId.pushnotifications.pusher.com/publish_api/v1/instances/$instanceId/publishes/interests";
        $title = "Hello";
        $body = "Hello, world!";
        $interests = ["hello"];
        $data = [
            "interests" => $interests,
            "web" => [
                "notification" => [
                    "title" => $title,
                    "body" => $body,
                    "deep_link" => "https://example.com/messages?message_id=2342",
                    "icon" => "https://news.elsob7.com/wp-content/uploads/2023/11/160.webp",
                    "hide_notification_if_site_has_focus" =>  true
                ],
            ]
        ];
        $response = Http::withHeaders([
            'Authorization' => "Bearer ". env('PUSHER_BEAM_SECRET_KEY')
        ])->post($url, $data);
    }


}
