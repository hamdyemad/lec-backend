<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
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




    public function __construct(public WatsappService $watsappService) {}



    public function login(Request $request)
    {
        $rules = [
            'username' => ['required', 'string', 'exists:rc_users,username'],
            'password' => ['required', 'string'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $user = User::where([
            'username' => $request->username
        ])->first();
        if ($user) {
            if (!$user->status) {
                return $this->sendRes(translate('user is not active'), false, [], [], 400);
            }

            $checked_password = Hash::check($request->password, $user->password);
            if ($checked_password) {
                $user->tokens()->delete();
                $token = $user->createToken('login');
                $userToken = $token->plainTextToken;

                $data = [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'mobile_code' => $user->mobile_code,
                    'mobile' => $user->mobile,
                    'token' => $userToken
                ];
                return $this->sendRes(translate('login success'), true, $data, [], 200);
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


    public function forget_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', Rule::exists('rc_users', 'email')->where(function ($query) {
                $query->where('status', true);
            })],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }


        $user = User::where([
            'email' => $request->email,
            'status' => 1,
        ])->first();
        if ($user) {
            $code = $this->generate_rand_code();

            $data = [
                'email' => $request->email,
                'code' => $code,
            ];
            try {
                Mail::to($request->email)->send(new ResetPasswordMail($data));
            } catch(Exception $e) {
            }
            $user->code = Hash::make($code);
            $user->last_code = Carbon::now()->addMinutes(15);
            $user->save();
            $data = [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'mobile_code' => $user->mobile_code,
                'mobile' => $user->mobile,
                'code' => $code
            ];

            return $this->sendRes(translate('vefication code has been sent to: ' . $user->email), true, $data, [], 200);
        } else {
            return $this->sendRes(translate('user not found'), false, [], [], 400);
        }
    }

    public function reset_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uuid' => ['required', 'exists:rc_users,uuid'],
            'code' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }


        $user = User::where([
            'uuid' => $request->uuid,
            'status' => 1,
        ])->first();
        if ($user) {

            // Check if the code is expired
            if(Carbon::now()->greaterThan($user->last_code)) {
                return $this->sendRes(translate('verification code has been expired'), false, [], [], 400);
            }

            // Check if the code is correct
            if(!Hash::check($request->code, $user->code)) {
                return $this->sendRes(translate('code is incorrect'), false, [], [], 400);
            }
            $new_hashed_password = Hash::make($request->password);
            $user->update([
               'password' => $new_hashed_password,
               'code' => ''
            ]);
            return $this->sendRes(translate('password has been reset success please try to login'), true, [], [], 200);
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
