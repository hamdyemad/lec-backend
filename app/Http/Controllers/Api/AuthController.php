<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\ResetPassword;
use App\Mail\VerifyRegister;
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
    protected $user_type_id = 3;



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



    // Register
    public function register(Request $request) {

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email', 'string', 'max:255'],
            'phone_code' => ['required', 'string', 'exists:countries,call_key'],
            'phone' => ['required', 'string'],
            'country_id' => ['required', 'exists:countries,id'],
            'city_id' => ['required', 'string', 'max:255', 'exists:cities,id'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string','min:8', 'confirmed'],
            'verfication_link' => ['required', 'string'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'phone_code' => $request->phone_code,
            'password' => Hash::make($request->password),
            'country_id' => $request->country_id,
            'city_id' => $request->city_id,
            'national_id' => $request->national_id,
            'address' => $request->address,
        ];
        $code = $this->generate_rand_code();
        $data['uuid'] = \Str::uuid();
        $data['user_type_id'] = $this->user_type_id;
        $data['active'] = true;
        $data['code'] = Hash::make($code);
        $data['last_code'] = Carbon::now()->addMinutes(15);
        $client = DemoUser::create($data);

        $verfication_link = $request->verfication_link . '/' . $client->uuid;
        $data = [
            'code' => $code,
            'email' => $client->email,
            'verfication_link' => $verfication_link
        ];
        try {
            Mail::to($client->email)->send(new VerifyRegister($data));
        } catch(Exception $e) {
        }
        $message = translate('your code has been sent to your email please check it');
        return $this->sendRes($message, true, [], [], 200);

    }

    public function verify_register(Request $request) {
        $rules = [
            'uuid' => ['required', 'string', 'exists:demo_users,uuid'],
            'code' => ['required'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $demo_user = DemoUser::where('uuid', $request->uuid)->first();

        $attributes = $demo_user->toArray();

        unset($attributes['id']);
        $attributes['password'] = $demo_user->password;

        $user = User::where('uuid', $demo_user->uuid)->first();

        if($user) {
            return $this->sendRes(translate('this user is already in use'), false, [], [], 400);
        }

        if($demo_user->last_code < Carbon::now()) {
            return $this->sendRes(translate('code is expired please make one again'), false, [], [], 400);
        }

        if (!Hash::check($request->code, $demo_user->code)) {
            return $this->sendRes(translate('code is incorrect'), false, [], [], 400);
        } else {
            $user = User::create($attributes);
            $user->update([
                'code' => '',
                'last_code' => null,
                'email_verified_at' => Carbon::now(),
                'active' => true,
            ]);
            $demo_user->update([
                'code' => '',
                'last_code' => null,
                'email_verified_at' => Carbon::now()
            ]);
            return $this->sendRes(translate('your account has been verified success please try to login'), true, [], [], 200);
        }
    }


    // Profile
    public function profile(Request $request)
    {
        $auth = auth()->user()->load('country', 'city');
        $auth = new UserResource($auth);
        return $this->sendRes(translate('profile data'), true, $auth, [], 200);
    }

    public function update_profile(Request $request) {


        $auth = auth()->user();
        $rules = [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:users,email', 'string', 'max:255'],
            'phone_code' => ['required_with:phone', 'string', 'exists:countries,call_key'],
            'phone' => ['required_with:phone_code', 'string'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return $this->errorResponse($validator);
        }


        $data = [];
        ($request->name) ? $data['name'] = $request->name : null;
        ($request->email) ? $data['email'] = $request->email : null;
        ($request->phone_code) ? $data['phone_code'] = $request->phone_code : null;
        ($request->phone) ? $data['phone'] = $request->phone : null;
        ($request->country_id) ? $data['country_id'] = $request->country_id : null;
        ($request->city_id) ? $data['city_id'] = $request->city_id : null;
        ($request->address) ? $data['address'] = $request->address : null;
        ($request->national_id) ? $data['national_id'] = $request->national_id : null;


        if($request->image) {
            if(file_exists($auth->image)) {
                unlink($auth->image);
            }
            $image = $this->uploadFile($request, $this->users_path . $auth->uuid . '/', 'image');
            $data['image'] = $image;
        }

        $auth->update($data);
        $message = translate('profile updated success');

        return $this->sendRes($message, true, [], [], 200);

    }

    public function update_profile_password(Request $request) {
        $auth = auth()->user();
        $rules = [
            'old_password' => ['required', 'string', 'max:255'],
            'new_password' => ['required', 'string', 'max:255'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return $this->errorResponse($validator);
        }


        if(!Hash::check($request->old_password, $auth->password)) {
            return $this->sendRes(translate('the current password is incorrect'), false, [], [], 400);
        }
        $auth->update([
            'password' => Hash::make($request->new_password)
        ]);
        return $this->sendRes(translate('the password has changed success'), true, [], [], 200);

    }






    public function forget_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'exists:users,email'],
            'verfication_link' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }


        $user = User::where([
            'email' => $request->email
        ])->first();
        if ($user) {
            $code = $this->generate_rand_code();
            $verfication_link = $request->verfication_link . '/' . $user->uuid;
            $data = [
                'code' => $code,
                'email' => $user->email,
                'verfication_link' => $verfication_link
            ];

            try {
                Mail::to($user->email)->send(new ResetPassword($data));
            } catch(Exception $e) {

            }
            $user->code = Hash::make($code);
            $user->last_code = Carbon::now()->addMinutes(15);
            $user->save();
            return $this->sendRes(translate('reset code has been sent to: ' . $user->email), true, [], [], 200);
        } else {
            return $this->sendRes(translate('user not found'), false, [], [], 400);
        }
    }

    public function reset_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uuid' => ['required', 'exists:users,uuid'],
            'code' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }


        $user = User::where([
            'uuid' => $request->uuid,
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
