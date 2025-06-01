<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Cart;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\User;
use App\Models\UserType;
use App\Service\PushNotificaion;
use App\Traits\FileUploads;
use App\Traits\Res;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    use Res, FileUploads;

    public $pushNotificationService;
    public function __construct(PushNotificaion $pushNotificationService) {
        $this->pushNotificationService = $pushNotificationService;
    }

    public function sendNotification()
    {
        $users = ['web-0481d9c6-e2ef-4c81-8a91-adaa132b676b', 'hello'];  // Array of user identifiers
        $message = 'This is a push notification';

        $response = $this->pushNotificationService->sendPushNotification($users, $message);

        return response()->json($response);
    }

    public function generateToken(Request $request)
    {

        $rules = [
            'user_id' => ['required'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }


        $userID = $request->user()->id; // If you use a different auth system, do your checks here
        $userIDInQueryParam = $request->user_id;

        if ($userID != $userIDInQueryParam) {
            return response('Inconsistent request', 401);
        } else {
            $beamsToken = $this->pushNotificationService->generateToken($userID);
            return response()->json($beamsToken);
        }

        // $userID = $request->user()->id; // If you use a different auth system, do your checks here
        // $userIDInQueryParam = $request->user_id;

        // if ($userID != $userIDInQueryParam) {
        //     return response('Inconsistent request', 401);
        // } else {
        //     $beamsToken = $this->pushNotificationService->generateToken($userID);
        //     return response()->json($beamsToken);
        // }


    }



    public function generatePusherTokenBeam($user_id)
    {
        $beamsToken = $this->pushNotificationService->generateToken($user_id);

        return $beamsToken['token'];
        // return response()->json($beamsToken);

        // $userID = $request->user()->id; // If you use a different auth system, do your checks here
        // $userIDInQueryParam = $request->user_id;

        // if ($userID != $userIDInQueryParam) {
        //     return response('Inconsistent request', 401);
        // } else {
        //     $beamsToken = $this->pushNotificationService->generateToken($userID);
        //     return response()->json($beamsToken);
        // }


    }


}
