<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ResetPassword;
use App\Models\DemoUser;
use App\Models\User;
use App\Service\WatsappService;
use App\Traits\FileUploads;
use App\Traits\Res;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    use  Res, FileUploads;




    public function __construct() {}



    public function login(Request $request)
    {
        $rules = [
            'email' => ['required', 'string', 'exists:users,email'],
            'password' => ['required', 'string'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $user = User::where([
            'email' => $request->email
        ])->first();
        if ($user) {
            if (!$user->active) {
                return $this->sendRes(translate('user is not active'), false, [], [], 400);
            }

            $checked_password = Hash::check($request->password, $user->password);
            if ($checked_password) {
                $token = $user->createToken('login');
                $userToken = $token->plainTextToken;
                $user->token = $userToken;
                return $this->sendRes(translate('login success'), true, $user, [], 200);
            } else {
                return $this->sendRes(translate('password is incorrect'), false, [], [], 400);
            }
        } else {
            return $this->sendRes(translate('user not found'), false, [], [], 400);
        }
    }

    public function profile(Request $request)
    {
        $auth = auth()->user();
        return $this->sendRes(translate('profile data'), true, $auth, [], 200);
    }

    public function update_profile(Request $request) {

        $auth = auth()->user();
        $rules = [
            'name' => ['nullable', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'string', 'exists:countries,id'],
            'city_id' => ['nullable', 'string', 'exists:cities,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone_code' => ['required_with:phone', 'string', 'exists:countries,call_key'],
            'phone' => ['required_with:phone_code', 'string', Rule::unique('rc_users', 'mobile')->ignore($auth->id)],
            'current_password' => ['required_with:new_password', 'string', 'max:255'],
            'new_password' => ['required_with:current_password', 'string', 'max:255'],

        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return $this->errorResponse($validator);
        }

        (isset($request->name)) ?  $auth->name = $request->name : null;
        (isset($request->national_id)) ?  $auth->national_id = $request->national_id : null;
        (isset($request->country_id)) ?  $auth->country_id = $request->country_id : null;
        (isset($request->city_id)) ?  $auth->city_id = $request->city_id : null;
        (isset($request->address)) ?  $auth->address = $request->address : null;
        (isset($request->phone_code)) ?  $auth->phone_code = $request->phone_code : null;
        (isset($request->phone)) ?  $auth->phone = $request->phone : null;

        if($request->current_password) {
            if(Hash::check($request->current_password, $auth->password)) {
                $auth->password = Hash::make($request->new_password);
            }  else {
                return $this->sendRes(translate('current password is incorrect'), false, [], [], 400);
            }
        }

        $auth->save();
        if($auth) {
            return $this->sendRes(translate('profile updated'), true);
        }
    }



    public function forget_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'exists:users,email'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }


        $user = User::where([
            'email' => $request->email
        ])->first();
        if ($user) {
            $code = $this->generate_rand_code();
            try {
                Mail::to($user->email)->send(new ResetPassword($code));
            } catch(Exception $e) {
            }
            $user->code = Hash::make($code);
            $user->last_code = Carbon::now()->addMinutes(10);
            $user->save();
            return $this->sendRes(translate('reset code has been sent to: ' . $user->email), true, [], [], 200);
        } else {
            return $this->sendRes(translate('user not found'), false, [], [], 400);
        }
    }

    public function reset_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'exists:users,email'],
            'code' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }


        $user = User::where([
            'email' => $request->email,
        ])->first();
        if ($user) {
            if($user->last_code < Carbon::now()) {
                return $this->sendRes(translate('code is expired please make one again'), false, [], [], 400);
            }
            if (!Hash::check($request->code, $user->code)) {
                return $this->sendRes(translate('code is incorrect'), false, [], [], 400);
            } else {
                $new_hashed_password = Hash::make($request->password);
                $user->update([
                    'password' => $new_hashed_password,
                    'code' => '',
                    'last_code' => null,
                ]);
                return $this->sendRes(translate('password has been reset success please try to login'), true, [], [], 200);
            }
        } else {
            return $this->sendRes(translate('user not found'), false, [], [], 400);
        }
    }



    public function generate_rand_code($length = 6)
    {
        $chars = '0123456789';

        $str = '';
        $max = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++)
            $str .= $chars[random_int(0, $max)];

        return $str;
    }

    function generate_password($length = 10)
    {
        $chars =  '0123456789' . 'abcd';

        $str = '';
        $max = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++)
            $str .= $chars[random_int(0, $max)];
        return $str;
    }
}
