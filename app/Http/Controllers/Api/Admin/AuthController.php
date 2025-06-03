<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemoUser;
use App\Models\User;
use App\Service\WatsappService;
use App\Traits\FileUploads;
use App\Traits\Res;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'username' => ['required', 'string', 'exists:rc_users,username'],
            'password' => ['required', 'string'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $user = User::where([
            'username' => $request->username
        ])->first();
        if($user) {
            if(!$user->status) {
                return $this->sendRes(translate('user is not active'), false, [], [], 400);
            }

            $checked_password = Hash::check($request->password,$user->password);
                if($checked_password) {
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

    // Registers
    // Step 1
    public function register(Request $request) {

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:rc_users,username'],
            'email' => ['required', 'email', 'string', 'max:255'],
            'mobile_code' => ['required', 'string', 'exists:countries,call_key'],
            'mobile' => ['required', 'string'],
            'password' => ['required', 'string','min:8', 'confirmed'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }


        $user = User::create([
            'uuid' => Str::uuid(),
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'mobile_code' => $request->mobile_code,
            'password' => Hash::make($request->password),
            'status' => true,
            'user_type_id' => '2',
        ]);
        $token = $user->createToken('login');
        $userToken = $token->plainTextToken;
        $data = [
            'user' => $user,
            'token' => $userToken
        ];
        return $this->sendRes(translate('user has been registerd successfully'), true, $data, [], 200);
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
