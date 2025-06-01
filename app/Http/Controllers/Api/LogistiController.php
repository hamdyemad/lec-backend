<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\CheckPayoutStatus;
use App\Models\ApiKey;
use App\Models\Cart;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\User;
use App\Models\UserLocation;
use App\Models\UserType;
use App\Service\LogistiService;
use App\Service\MoyasarService;
use App\Traits\Delivery;
use App\Traits\FileUploads;
use App\Traits\Location;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LogistiController extends Controller
{
    use Res, FileUploads, Location, Delivery;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct(public LogistiService $logistiService) {}



    public function identity_types_list() {
        $response = $this->logistiService->identity_types();
        if($response['status'] == true) {
            return $this->sendRes(__('validation.success'), true, $response['data']);
        } else {
            return $this->logistiError($response);
        }
    }
    public function regions() {
        $response = $this->logistiService->regions();
        if($response['status'] == true) {
            return $this->sendRes(__('validation.success'), true, $response['data']);
        }  else {
            return $this->logistiError($response);
        }
    }

    public function car_types() {
        $response = $this->logistiService->car_types();
        if($response['status'] == true) {
            return $this->sendRes(__('validation.success'), true, $response['data']);
        }  else {
            return $this->logistiError($response);
        }
    }

    public function logistiError($response) {
        if($response['status'] == false) {
            if($response['errorCodes']) {
                foreach($response['errorCodes'] as $errorCode) {
                    $codeObj = array_filter($this->logistiService->errorsCodes, function($error) use($errorCode) {
                        return $error['code'] == $errorCode;
                    });
                    if($codeObj) {
                        $filtered = array_values($codeObj);
                        $message = 'error logisti';
                        if(count($filtered) > 0) {
                            $message =  $filtered[0]['message'];
                        }
                        return $this->sendRes($message, false, [], [$message], 400);

                    }
                }
            }
            return $this->sendRes('logisti errors apis with codes', false, [], $response['errorCodes'], 400);
        }
    }

    public function cities(Request $request) {
        $validator = Validator::make($request->all(), [
            'regionId' => ['required'],
        ]);

        if($validator->fails()) {
            $messages = implode('<br>', $validator->errors()->all());
            return $this->sendRes($messages, false, [], $validator->errors(), 400);
        }

        $response = $this->logistiService->cities($request->regionId);
        if($response['status'] == true) {
            return $this->sendRes(__('validation.success'), true, $response['data']);
        } else {
            return $this->logistiError($response);
        }
    }




}
