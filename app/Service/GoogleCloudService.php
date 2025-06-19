<?php


namespace App\Service;

use App\Traits\Res;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Pusher\PushNotifications\PushNotifications;

class GoogleCloudService {

    use Res;
    public $servicePath;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct() {
        $this->servicePath = Storage::path('lec-app-d7b6f-firebase-adminsdk-fbsvc-cd95ada3cd.json');
    }

    public function getAccessToken() {
        $client = new \Google\Client();
        $client->setAuthConfig($this->servicePath);
        $client->addScope('https://www.googleapis.com/auth/cloud-platform'); // Example scope
        $accessToken = $client->fetchAccessTokenWithAssertion();
        return $accessToken['access_token'] ?? false;




    }




}
