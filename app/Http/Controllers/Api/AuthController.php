<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ResetPassword;
use App\Mail\ResetPasswordMail;
use App\Mail\VerifyAuth;
use App\Mail\VerifyAuthMail;
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




    public function __construct(public WatsappService $watsappService) {
    }



    public function login(Request $request)
    {

        $rules = [
            'email' => ['required', 'exists:rc_users,email'],
            'password' => ['required', 'string'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $user = User::where([
            'email' => $request->email,
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

    // Profiles
    public function profile(Request $request)
    {

        $user = auth()->user();
        return $this->sendRes(translate('profile data'), true, $user, [], 200);
    }

    public function update_profile(Request $request) {

        $auth = auth()->user();
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'string', 'max:255', Rule::unique('rc_users', 'email')->ignore($auth->id)],
            'mobile_code' => ['required', 'string', 'exists:countries,call_key'],
            'mobile' => ['required', 'string', Rule::unique('rc_users', 'mobile')->ignore($auth->id)]
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $auth->update([
            'name' => $request->name,
            'email' => $request->email,
            'mobile_code' => $request->mobile_code,
            'mobile' => $request->mobile,
        ]);
        if($auth) {
            return $this->sendRes(translate('profile updated'), true);
        }
    }



    // Registers
    // Step 1
    public function register_step_1(Request $request) {

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'mobile_code' => ['required', 'string', 'exists:countries,call_key'],
            'mobile' => ['required', 'string'],
            'password' => ['required', 'string','min:8', 'confirmed'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $user = DemoUser::create([
            'uuid' => Str::uuid(),
            'name' => $request->name,
            'mobile_code' => $request->mobile_code,
            'mobile' => $request->mobile,
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
            'email' => ['required', 'email', 'unique:rc_users,email', 'string', 'max:255'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }
        $code = $this->generate_rand_code();
        $demo_user =  DemoUser::where([
            'uuid' => $request->uuid,
            'status' => false
        ])->first();
        if($demo_user) {
            $demo_user->update([
                'code' => Hash::make($code),
                'last_code' => Carbon::now()->addMinutes(15),
                'email' => $request->email,
            ]);

            $data = [
                'email' => $request->email,
                'code' => $code
            ];

            try {
                Mail::to($request->email)->send(new VerifyAuthMail($data));
            } catch(Exception $e) {
            }

            $data = [
                'id' => $demo_user->id,
                'uuid' => $demo_user->uuid,
                'name' => $demo_user->name,
                'email' => $demo_user->email,
                'status' => $demo_user->status,
                'mobile_code' => $demo_user->mobile_code,
                'mobile' => $demo_user->mobile,
                'code' => $code,
                'last_code' => $demo_user->last_code,
            ];
            return $this->sendRes(translate('please confirm the step3 of registration to confirm'), true, $data, [], 200);
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
            'status' => false
        ])->first();
        if($demo_user) {
            // Check if the code is expired
            if(Carbon::now()->greaterThan($demo_user->last_code)) {
                return $this->sendRes(translate('verification code has been expired'), false, [], [], 400);
            }
            // Check if the code is correct
            if(!Hash::check($request->code, $demo_user->code)) {
                return $this->sendRes(translate('verification code is incorrect'), false, [], [], 400);
            }
            if($demo_user->status == false) {
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
                    'user_type_id' => '3',
                ]);
                $token = $user->createToken('login');
                $userToken = $token->plainTextToken;
                $data = [
                    'user' => $user,
                    'token' => $userToken
                ];
                return $this->sendRes(translate('verification code has been verified'), true, $data, [], 200);
            } else {
                return $this->sendRes(translate('This user has been verified before'), false, [], [], 400);
            }
        } else {
            return $this->sendRes(translate('user not found'), false, [], [], 400);
        }
    }

    public function forget_password(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', Rule::exists('rc_users', 'email')->where(function($query) {
                $query->where('status', true);
            }) ],
        ]);

        if($validator->fails()) {
            $messages = implode('<br>', $validator->errors()->all());
            return $this->sendRes($messages, false, [], $validator->errors(), 400);
        }


        $user = User::where([
            'email' => $request->email,
            'status' => 1,
        ])->first();
        if($user) {
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


    public function firebase_save_token(Request $request){

        $auth = auth()->user();

        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
        ]);
        if($validator->fails()) {
            $messages = implode('<br>', $validator->errors()->all());
            return $this->sendRes($messages, false, [], $validator->errors(), 400);
        }

        if($auth->device_token) {
            $auth->device_token->update([
                'token' => $request->token
            ]);
        } else {
            $auth->device_token()->create([
                'token' => $request->token
            ]);
        }

        return $this->sendRes(translate('your token has been saved'), true);


    }



}
