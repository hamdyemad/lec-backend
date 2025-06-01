<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemoUser;
use App\Models\User;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    use  Res, FileUploads;




    public function __construct() {
    }



    public function login(Request $request)
    {

        $rules = [
            'mobile_code' => ['required', 'string'],
            'mobile' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $user = User::where([
            'mobile' => $request->mobile,
            'mobile_code' => $request->mobile_code,
            'status' => 1
        ])->first();
        if($user) {
            $checked_password = Hash::check($request->password,$user->password);
                if($checked_password) {
                    $user->tokens()->delete();
                    $token = $user->createToken('login');
                    $userToken = $token->plainTextToken;

                    $data = [
                        'id' => $user->id,
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'email' => $user->email,
                        'status' => $user->status,
                        'mobile_code' => $user->mobile_code,
                        'mobile' => $user->mobile,
                        'token' => $userToken
                    ];
                    // // Make User Online With Event
                    // broadcast(new UserOnlineChanged($user, true));
                    return $this->sendRes(translate('login success'), true, $data, [], 200);
                } else {
                    return $this->sendRes(translate('password is incorrect'), false, [], [], 400);
                }

        } else {
            return $this->sendRes(translate('user not found'), false, [], [], 400);
        }

    }

    // Registers
    // Step 1
    public function register_step_1(Request $request) {

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'string', 'max:255'],
            'password' => ['required', 'string','min:8', 'confirmed'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $user = DemoUser::create([
            'uuid' => Str::uuid(),
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        if($user) {
            return $this->sendRes(translate('please confirm the step2 of registration to confirm'), true, $user);
        }


    }

    // Step 2
    public function register_step_2(Request $request)
    {

        $rules = [
            'uuid' => ['required', 'exists:demo_users,uuid'],
            'mobile_code' => ['required', 'string', 'exists:countries,call_key'],
            'mobile' => ['required', 'string']
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }
        $demo_user =  DemoUser::where([
            'uuid' => $request->uuid,
            'status' => false
        ])->first();
        if($demo_user) {
            $demo_user->update([
                'code' => $this->generate_rand_code(),
                'mobile_code' => $request->mobile_code,
                'mobile' => $request->mobile,
            ]);
            return $this->sendRes(translate('please confirm the step3 of registration to confirm'), true, $demo_user, [], 200);
        } else {
            return $this->sendRes(translate('user not found'), false, [], [], 400);
        }

    }
    // Step 2
    public function register_step_3(Request $request)
    {
        $rules = [
            'uuid' => ['required', 'string', 'exists:demo_users,uuid'],
            'code' => ['required', 'string'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $demo_user =  DemoUser::where([
            'uuid' => $request->uuid,
            'code' => $request->code,
        ])->first();
        if($demo_user) {
            if($demo_user->status == false) {
                if($demo_user->code == $request->code) {
                    $demo_user->update([
                        'status' => true,
                    ]);
                    $user = User::create([
                        'uuid' => $demo_user->uuid,
                        'name' => $demo_user->name,
                        'email' => $demo_user->email,
                        'mobile' => $demo_user->mobile,
                        'mobile_code' => $demo_user->mobile_code,
                        'password' => $demo_user->password,
                        'status' => true,
                    ]);
                    $token = $user->createToken('login');
                    $userToken = $token->plainTextToken;
                    $data = [
                        'user' => $user,
                        'token' => $userToken
                    ];
                    return $this->sendRes(translate('verification code has been verified'), true, $data, [], 200);
                } else {
                    return $this->sendRes(translate('verification code is incorrect'), false, [], [], 400);
                }
            } else {
                return $this->sendRes(translate('This user has been verified before'), false, [], [], 400);
            }
        } else {
            return $this->sendRes(translate('user not found'), false, [], [], 400);
        }
    }



    public function forget_password(Request $request){
        $validator = Validator::make($request->all(), [
            'mobile_code' => ['required', 'string'],
            'mobile' => ['required', 'string', Rule::exists('rc_users', 'mobile')->where(function($query) {
                $query->where('status', true);
            }) ],
        ]);

        if($validator->fails()) {
            $messages = implode('<br>', $validator->errors()->all());
            return $this->sendRes($messages, false, [], $validator->errors(), 400);
        }


        $user = User::where([
            'mobile' => $request->mobile,
            'mobile_code' => $request->mobile_code,
            'status' => 1,
        ])->first();
        if($user) {
            $user->code = $this->generate_rand_code();
            $user->save();
            $full_mobile = $user->mobile_code . $user->mobile;
            $data = [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'mobile_code' => $user->mobile_code,
                'mobile' => $user->mobile,
                'code' => $user->code
            ];

            return $this->sendRes(translate('vefication code has been sent to: ' . $full_mobile), true, $data, [], 200);
        } else {
            return $this->sendRes(translate('user not found'), false, [], [], 400);

        }

    }

    public function reset_password(Request $request){


        $validator = Validator::make($request->all(), [
            'uuid' => ['required', 'exists:rc_users,uuid'],
            'code' => ['required', 'string'],
            'password' => ['required','string','min:8', 'confirmed'],
        ]);

        if($validator->fails()) {
            $messages = implode('<br>', $validator->errors()->all());
            return $this->sendRes($messages, false, [], $validator->errors(), 400);
        }


        $user = User::where([
            'uuid' => $request->uuid,
            'status' => 1,
        ])->first();
        if($user) {
            if($user->code != $request->code) {
                return $this->sendRes(translate('code is incorrect'), false, [], [], 400);
            } else {
                $new_hashed_password = Hash::make($request->password);
                $user->update([
                   'password' => $new_hashed_password,
                   'code' => ''
                ]);
                return $this->sendRes(translate('password has been reset success please try to login'), true, [], [], 200);
            }
        } else {
            return $this->sendRes(translate('user not found'), false, [], [], 400);
        }

    }


    public function generate_rand_code($length = 6){
        $chars ='0123456789';

        $str = '';
        $max = strlen($chars) - 1;

        for ($i=0; $i < $length; $i++)
            $str .= $chars[random_int(0, $max)];

        return $str;
    }

    function generate_password($length = 10){
        $chars =  '0123456789' . 'abcd';

        $str = '';
        $max = strlen($chars) - 1;

        for ($i=0; $i < $length; $i++)
            $str .= $chars[random_int(0, $max)];
        return $str;
    }


}
