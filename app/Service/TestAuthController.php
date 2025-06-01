<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerificationOrResetEmail;
use App\Models\Business;
use App\Models\BusinessGroup;
use App\Models\BusinessModule;
use App\Models\BusinessSetting;
use App\Models\Module;
use App\Models\moduleLinks;
use App\Models\Permession;
use App\Models\Role;
use App\Models\RoleCountry;
use App\Models\Setting;
use App\Models\SubModule;
use App\Models\User;
use App\Models\Log;
use App\Traits\Accounting\Apis\Accounting;
use App\Traits\Apis;
use App\Traits\businessModules;
use App\Traits\Calendar\Apis\Calendar;
use App\Traits\FileUploads;
use App\Traits\HR\Apis\HR;
use App\Traits\Res;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TestAuthController extends Controller
{
    use Apis, Res, FileUploads,Accounting,businessModules,Calendar,HR;


    public function index()
    {
        //
    }


    public function login(Request $request)
    {
        app()->setLocale($request->header('Accept-Language'));
        $lang = 'en';
        if(app()->getLocale() == 'ar') {
            $lang = 'ar';
        }


        $rules = [
            'user_name' => 'required',
            'password' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $messages =  implode("<br>",$validator->messages()->all());

            return $this->sendRes($messages, false, $validator->errors(),400);
        }


        $credentials = request(['user_name', 'password']);
        if (! $token = auth('api')->attempt($credentials)) {
            return $this->sendRes(__('validation.auth.incorrect user_name or password'), false,[],400);
        }

        $user_data=User::with('roles')->where(['user_name'=>$request->user_name])->first();
        if(!$user_data){
            return $this->sendRes('user not found', false, [],400);
        }
        if($user_data->user_type->id == 4){
            $user_business=Business::where('id',$user_data->business_id)->first();

        }elseif($user_data->user_type->id == 3){
            $user_business=Business::where('user_id',$user_data->id)->orderby('id','asc')->first();
        }
        $user_role=$user_data->roles()->pluck('roles.id')->toArray();
        $role_data=Role::whereIn('id',$user_role)->first();
        if(!$role_data){
            return $this->sendRes('user not have any roles', false, [],401);
        }
        if($user_data->status=='0'){
            return $this->sendRes('user not active', false, [],400);
        }
        $role_models=$role_data->modules->pluck('id')->toArray();

        $user_business_arr=[];
        $front_modules_arr=[];

        $front_modules=Module::whereIn('id',$role_models)->where('type','front')->get();
        foreach($front_modules as $front_module){
            if($front_module->image!='')
                $module_img=url($front_module->image);
            else
                $module_img='';
            if($request->header('Accept-Language') == 'en')
                $module_name=$front_module->name;
            else
                $module_name=$front_module->name_ar;
            $front_modules_arr[]=['key'=>$front_module->key_value,'image'=>$module_img,'name'=>$module_name,'status'=>$front_module->status];
        }
        if(isset($user_business)&&!empty($user_business)){
            $have_business=true;
            $business_settings=BusinessSetting::where('business_id',$user_business['id'])->first();
            $business_commercial_number=(isset($business_settings))?$business_settings->commercial_number:'';
            $business_logo=(isset($business_settings))?ENV('APP_URL').'/'.$business_settings->logo:'';
            $business_name=($request->header('Accept-Language') == 'en')?$user_business['name_en']:$user_business['name'];
            $business_data=['id'=>$user_business['id'],'name'=>$business_name,'uuid'=>$user_business['uuid'],'secure_key'=>$user_business['secure_key'],'business_logo'=>$business_logo,'business_commercial_number'=>$business_commercial_number];
            $user_business_arr=['business_data'=>$business_data,'business_modules_route'=>$this->default_modules_links($user_business['id']),'front_module_routes'=>$front_modules_arr];
        }else{
            $have_business=false;
        }
        $user_data->tokens->each(function($token) {
            $token->delete();
        });
        $tokenResult = $user_data->createToken('Personal Access Token');
        $tokenData = \DB::table('personal_access_tokens')
            ->select('token')
            ->where('tokenable_id', $user_data->id)
            ->first();
        $personal_access_token = $tokenData ->token;

        $user_id=($user_data->ecomm_user_id!=null)?$user_data->ecomm_user_id:$user_data->id;
        $user_branch=$user_data->branch_id;
        $response_Data = [
            'user_id'=>$user_id,
            'name'=>$user_data->name,
            'user_name' => $user_data->user_name,
            'avatar' => env('APP_URL') . '/' . $user_data->avatar,
            'user_type'=>isset($user_data->user_type) ? $user_data->user_type->name : '',
            'user_type_id'=>isset($user_data->user_type) ? $user_data->user_type->id : '',
            'user_branch'=>$user_branch,
            'have_business'=>$have_business,
            'business'=>$user_business_arr,
            'personal_access_token'=>$personal_access_token
        ];


        if(isset($user_data->roles) && count($user_data->roles) > 0) {
            if($lang == 'en') {
                $response_Data['role_name'] = $user_data->roles[0]['name'];
            } else {
                $response_Data['role_name'] = $user_data->roles[0]['name_ar'];
            }
        }
        return $this->respondWithToken($token, true, __('validation.auth.login success'), $response_Data);
    }


    public function retrive_auth(Request $request)
    {

        $group_by = 'group_by';

        if($request->header('Accept-Language')) {
            if($request->header('Accept-Language') == 'en') {
                app()->setLocale('en');
            } else {
                app()->setLocale('ar');
                $group_by = 'group_by_ar';

            }
        }


        $user = auth('api')->user();




        $permessions = [];
        $roles = [];
        $array_roles = [];
        $modules_links = [];


        if($user->business) {
            $name = 'name';
            if(app()->getLocale() == 'ar') {
                $name = 'name_ar';
            }
            $modules = Module::select([
                $name,
                'link',
                'key_value',
            ])->get();

            foreach($modules as $module) {
                if($module->key_value == 'accounting') {
                    $modules_links['accounting_url'] = $module->link;
                }
                if($module->key_value == 'logistics') {
                    $modules_links['logistics_url'] = $module->link;
                }
                if($module->key_value == 'hr') {
                    $modules_links['hr_url'] = $module->link;
                }
                if($module->key_value == 'calendar') {
                    $modules_links['calendar_url'] = $module->link;
                }
            }


            if($user->business->setting) {
                $modules_links = [];
                $modules_links['accounting_url'] = $user->business->setting->accounting_url;
                $modules_links['logistics_url'] = $user->business->setting->logistics_url;
                $modules_links['hr_url'] = $user->business->setting->hr_url;
                $modules_links['calendar_url'] = $user->business->setting->calendar_url;
            }
        }

        $data = [
            'user' => $user,
            'modules_links' => $modules_links,
        ];


        if($request->permessions == 'true') {
            $finder = [
                'type' => 'permession',
                'business_id' => $user->business_id,
            ];

            $roles =  $user->roles;


            foreach($roles as $role) {
                array_push($array_roles, $role->permessions->pluck('id')->toArray());
            }
            $array_roles = array_merge(...$array_roles);
            $permessions = Permession::whereIn('id', $array_roles);

            if($request->permession) {
                $module = Module::where('name', $request->permession)->first();
                if($module) {
                    $permessions = $permessions->where('module_id', $module->id);
                }
            }

            $data['permessions'] = $permessions->get()->pluck('key_value');;

        }


        return $this->sendRes('', true, $data);
    }


    public function logout()
    {
        $user_id=Auth::user()->id;
        $user_data=User::where(['id'=>$user_id])->first();
        $user_data->tokens->each(function($token) {
            $token->delete();
        });
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }


    // Registers

    public function register_step_1(Request $request) {

        $rules = [
            'email' => ['required', 'email','max:255', 'unique:users,email'],
            'user_name' => ['required', 'unique:users,user_name','max:255'],
            'phone' => ['required','max:255'],
            'phone_code' => ['numeric'],
        ];


        app()->setLocale($request->header('Accept-Language'));

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            if($validator->errors()->has('user_name')==1&&$request->user_name!=''){
                $sugg_arr=[];
                for($i=0;$i<3;$i++){
                    $user_name = preg_replace('/[0-9]+/', '', $request->user_name);
                    $sugg_user_name=$user_name.$this->generate_rand_code() ;
                    $not_av_user_name=User::where('user_name',$sugg_user_name)->first();
                    if(!$not_av_user_name)
                        $sugg_arr[]=$user_name.$this->generate_rand_code()  ;
                }
                $message = implode('<br>', $validator->errors()->all());
                $data=['errors'=>$validator->errors(),'available_user names'=>$sugg_arr];
               // return $this->sendRes($message, false, $data);
                return response()->json([
                    'status' => false,
                    'message' => $message,
                    'data' => $data
                ],400);
            }
            $message = implode('<br>', $validator->errors()->all());

            //return $this->sendRes($message, false, ['errors'=>$validator->errors()]);
            return response()->json([
                'status' => false,
                'message' => $message,
                'data' => ['errors'=>$validator->errors()]
            ],400);
        }

        $phone_code=(isset($request->phone_code))?$request->phone_code:'';
        $api_data = [
            'email' => $request->email,
            'phone' => $request->phone,
            'user_name'=>$request->user_name,
            'status'=>'0',
            'super_admin_role'=>1,

        ];

        // Create User In Accounting Sys
        $response = $this->create_user($api_data);

        if(!$response['status']&&$response['status']==false) {
          //  return $this->sendRes($response['message'], false,['errors'=>$response['data']]);
            return response()->json([
                'status' => false,
                'message' => $response['message'],
                'data' => ['errors'=>$response['data']]
            ],400);

        }else{
            $user_id=$response['data']['id'];
            $user = User::create([
                'id'=>$user_id,
                'uuid' => \Str::uuid(),
                'email' => $request->email,
                'phone' => $request->phone,
                'user_name'=>$request->user_name,
                'email_verification_code' => rand(1000000, 100000000),
                'status' => '0',
                'user_type_id'=> 3,
                'phone_code'=>$phone_code,
            ]);
            $cs_admin_role=Role::where(['user_type' => 'cs_admin'])->first();
            if(isset($cs_admin_role))
                $user->roles()->attach($cs_admin_role->id);

            $data = [
                'user' =>  $user,
                'subject' => __('auth.vodo erp verification email'),
                'link' => env('VODO_ERP_URL') . '/register/confirmation' .'?uuid=' . $user['uuid'],
                'link_title' =>  __('auth.verification link'),
                'code' => $user->email_verification_code,
                'code_title' => __('auth.there is the verification code'),
            ];
            try {
                Mail::to($user->email)->send(new VerificationOrResetEmail($data));
            } catch (\Throwable $th) {
                //return $this->sendRes('error in send mail', false,[]);
                return response()->json([
                    'status' => false,
                    'message' => 'error in send mail',
                    'data' =>null
                ],400);

            }
            return $this->sendRes(__('auth.register has been success please findout your email link to confirm'), true, $user);
        }





    }

    public function register_step_2(Request $request, $uuid,$code)
    {
        app()->setLocale($request->header('Accept-Language'));


        $user =  User::where([
            'uuid' => $uuid,
        ])->first();

        if($user) {

            if($user->status == '0') {
                if($user->email_verification_code == $code) {
                    $user->update([
                        'status' => '1',
                        'email_verification_code' => null,
                    ]);

                    return $this->sendRes(__('auth.verification code has been verified'), true);
                } else {
                    return $this->sendRes(__('auth.verification code is incorrect'), false);
                }
            } else {
                return $this->sendRes(__('auth.The code has been verified before'), false);
            }
        } else {
            return $this->sendRes(__('auth.user not found'), false);
        }

    }

    public function register_step_3(Request $request, $uuid)
    {
        app()->setLocale($request->header('Accept-Language'));
        $rules = [
            'password' => ['required', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\d]).+$/','max:12'],
        ];
        app()->setLocale($request->header('Accept-Language'));
        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, ['errors'=>$validator->errors()]);
        }


        $user =  User::where([
            'uuid' => $uuid,
        ])->first();

        if($user) {
            if($user->status == '1') {
                $user->update([
                    'password' => Hash::make($request->password)
                ]);
                return $this->sendRes(__('auth.password has changed successfully'), true);
            } else {
                return $this->sendRes(__('auth.The user is not activated'), false);
            }
        } else {
            return $this->sendRes(__('auth.user not found'), false);
        }

    }

    public function register_step_4(Request $request, $uuid)
    {
        app()->setLocale($request->header('Accept-Language'));


        $rules = [
            'avatar' => ['nullable', 'image', 'max:5120'],
            'name' => ['required','max:255'],
            'address'=>['required','max:255'],
            'country_id'=>['required'],
            // 'occupation' => ['required','max:255'],
        ];




        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, ['errors'=>$validator->errors()]);
        }


        $user =  User::where([
            'uuid' => $uuid,
        ])->first();

        if($user) {
            if($user->status == '1') {
                $avatar = null;
                if(isset($request->avatar)) {
                    $avatar = $this->uploadFile($request, $this->users_path . $user->uuid . '/', 'avatar');
                }
                $user->update([
                    'name' => $request->name,
                    'avatar' => $avatar,
                    'address'=>$request->address,
                    'country_id'=>$request->country_id,
                    'city'=>(isset($request->city_id))?$request->city_id:'',
                    'postcode'=>(isset($request->postcode))?$request->postcode:'',
                    'state'=>(isset($request->district_id))?$request->district_id:''
                ]);


                //add to log
                $data_log = $user;
                $data_log['business_id'] = $user->business_id;
                $data_log['transaction_id'] = $user->id;
                $data_log['ip']=$request->ip;
                $data_log['device']=$request->device;
                $data_log['os']=$request->os;
                $data_log['long_lat']=$request->long_lat;
                (new Log)->addToLog($user->id,'User', $data_log, NULL, 'Create');
                $api_data = [

                    // 'email' => $user->email,
                    'name' => $user->name,
                    'password' => $user->password,
                    //'phone' => $user->phone,
                    'country_id' => $user->country_id,
                    'city' => $user->city,
                    'address' => $user->address,
                    // 'user_name'=>$user->user_name,
                    'id'=>$user->id,
                    'postcode'=>$user->postcode,

                ];
                $response = $this->update_user($api_data);

                if($response['status']&&$response['status']) {

                    $user->createToken('Personal Access Token');
                    //
                    $tokenData = \DB::table('personal_access_tokens')
                        ->select('token')
                        ->where('tokenable_id', $user->id)
                        ->first();
                    $personal_access_token = $tokenData ->token;
                    return $this->sendRes(__('auth.user information has been updated'), true,['personal_access_token'=>$personal_access_token]);
                }else{
                    return $this->sendRes($response['message'], false,['errors'=>$response['data']]);

                }
            } else {
                return $this->sendRes(__('auth.The user is not activated'), false);
            }
        } else {
            return $this->sendRes(__('auth.user not found'), false);
        }

    }


    public function reset_password(Request $request){

        app()->setLocale($request->header('Accept-Language'));

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'user_name' => 'required',
        ]);

        if($validator->fails()) {
            $messages = implode('<br>', $validator->errors()->all());

            return $this->sendRes($messages, false,['errors'=>$validator->errors()]);
        }

        $user = User::where('email', $request->email)->where('user_name',$request->user_name)->first();

        $reset_code = rand(1000000, 100000000);


        if($user) {
            $user->update([
                'reset_code' => $reset_code
            ]);

            $data = [
                'user' =>  $user,
                'subject' => __('auth.vodo erp reset password'),
                'link' => env('VODO_ERP_URL') . '/reset-password' .'?uuid=' . $user['uuid'],
                'link_title' =>  __('auth.reset password link'),
                'code' => $user->reset_code,
                'code_title' => __('auth.there is the reset password code'),
            ];

            try {
                Mail::to($user->email)->send(new VerificationOrResetEmail($data));
            } catch (\Throwable $th) {
                //throw $th;
            }

            return $this->sendRes(__('auth.reset link has been sent success'), true);

        } else {
            return $this->sendRes(__('auth.user not found'), false);

        }

    }
    public function forgot_password(Request $request, $uuid,$code){


        $validator = Validator::make($request->all(), [
            'password' => ['required', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\d]).+$/','max:12']
        ]);

        if($validator->fails()) {
            $messages = implode('<br>', $validator->errors()->all());
            return $this->sendRes($messages, false,$validator->errors());
        }

        $user =  User::where([
            'uuid' => $uuid,
        ])->first();

        if($user) {
            if($user->reset_code == $code) {
                $user->update([
                    'reset_code' => null,
                    'password' => Hash::make($request->password),
                ]);
                return $this->sendRes(__('auth.password has changed successfully'), true);

            } else {
                return $this->sendRes(__('auth.reset password code is incorrect'), false);
            }
        } else {
            return $this->sendRes(__('auth.user not found'), false);
        }


    }


    public function generate_rand_code($length = 4){
        $chars ='0123456789';

        $str = '';
        $max = strlen($chars) - 1;

        for ($i=0; $i < $length; $i++)
            $str .= $chars[random_int(0, $max)];

        return $str;
    }
    public function show_profile(){
        //  app()->setLocale($request->header('Accept-Language'));
        $user_data=User::where('id',Auth::user()->id)->first();
        return $this->sendRes('show profile', true, $user_data);


    }
    public function update_profile(Request $request){
        $user_id=Auth::user()->id;
        $rules ['email'] = [ 'email','max:255'];
        $rules ['user_name'] =[ Rule::unique('users')->where(function ($query) use ($user_id) {
            return $query->where('id','!=', $user_id);
        })];
        //$rules['phone'] = ['required','max:255'];

        $rules['avatar']= ['nullable', 'image', 'max:5120'];
        $rules['name'] = ['required', 'min:8','max:255'];
        $rules['address']=['required','min:8','max:255'];
        $rules['country_id']=['required'];




        app()->setLocale($request->header('Accept-Language'));

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, ['errors'=>$validator->errors()]);
        }
        $upadted_data=['user_name'=>$request->user_name,
            'name'=>$request->full_name];
        User::where('id',$user_id)->update($upadted_data);
    }

    public  function store_accounting_users(){
        $all_users_response=  Http::withHeaders([
            'apiKey' => env('ACCOUNTING_API_KEY')
        ])->post( env('ACCOUNTING_API_URL') .'/api/all_users');
        $all_users= $all_users_response['data'];
        foreach($all_users as $user){
            $user_data[]=[
                'id'=>$user['id'],
                'uuid'=>$user['uuid'],
                'name'=>$user['name'],
                'type'=>'cs_admin',
                'status'=>'1',
                'email'=>$user['email'],
                'user_name'=>$user['user_name'],
                'password'=>$user['password'],
                'phone'=>$user['phone'],
                'address'=>$user['address'],
                'country_id'=>$user['country_id'],
                'city'=>$user['city'],
                'state'=>$user['state'],
                'postcode'=>$user['postcode'],
                'created_at'=>date('Y-m-d h:i:s',strtotime($user['created_at'])),
            ];
        }
        User::insert($user_data);

    }
    public function save_accounting_new_user(Request $user){
        $user_type=($user->super_admin_role==1)?'cs_admin':'user';
        $user_data=[
            'id'=>$user->id,
            'uuid'=>Str::uuid(),
            'name'=>$user['name'],
            'status'=>'1',
            'email'=>$user['email'],
            //'password'=>$user['password'],
            'phone'=>$user['phone'],
            'type'=>$user_type,
            'business_id'=>(isset($user->business_id))?$user->business_id:'',
            'address'=>(isset($user['address']))?$user['address']:'',
            'country_id'=>(isset($user['country_id']))?$user['country_id']:'',
            'city'=>(isset($user['city']))?$user['city']:'',
            //'state'=>$user['state'],
            //'postcode'=>$user['postcode'],
            //'created_at'=>date('Y-m-d h:i:s',strtotime($user['created_at'])),
        ];
        $acc_user=User::create($user_data);
        if($user_type=='cs_admin'){
            $cs_admin_role=Role::where(['user_type' => 'cs_admin'])->first();
            if(isset($cs_admin_role))
                $acc_user->roles()->attach($cs_admin_role->id);
        }
        return $this->sendRes('user created', true, $acc_user);


    }
    public function update_accounting_user(Request $request){
      $user_id=$request->id;
      $userInfo=User::where('id',$user_id)->first();
      if($userInfo) {
          User::where('id',$user_id)->update([
              'name'=>(isset($request->name))?$request->name:$userInfo->name,
              'email'=>(isset($request->email))?$request->email:$userInfo->email,
              'password'=>(isset($request->password))?$request->password:$userInfo->password,
              'address'=>(isset($request->address))?$request->address:$userInfo->address,
              'country_id'=>(isset($request->country_id))?$request->country_id:$userInfo->country_id,
              'city'=>(isset($request->city))?$request->city:$userInfo->city,
          ]);
            return $this->sendRes('user updated', true, $userInfo);
      } else {
        return $this->sendRes('user not found', false);
      }
    }
    public function get_user_from_token(Request $request){
        $user_token=($request->user_token)??$request->userToken;
        $result = \DB::table('personal_access_tokens')
            ->select('tokenable_id as user_id')
            ->where('token', $user_token)
            ->first();
        if($result){
            $user_data=User::where('id',$result->user_id)->first();
            $final_result=['user_id'=>$result->user_id,'type'=>$user_data->user_type->name,'business_id'=>$user_data->business_id];
            return $this->sendRes('user_data', true, $final_result);

        }else{
            return $this->sendRes('user not found', false, []);

        }

    }
    public function save_hr_new_user(Request $user){
        $rules = [
            'user_name' => ['unique:users,user_name'],
        ];



        $validator = Validator::make($user->all(), $rules);
        $message = implode('<br>', $validator->errors()->all());

        if($validator->fails()) {
            return $this->sendRes($message, false, ['errors'=>$validator->errors()]);

        }
        $user_data=[
            //'id'=>$request['id'],
            'business_id'=>$user['buiness_id'],
            'uuid'=>\Str::uuid(),
            'name'=>$user['name'],
            'user_type_id'=> 4,
            'status'=>'1',
            'email'=>$user['email'],
            'user_name'=>$user['user_name'],
            'password'=>$user['password'],
            'phone'=>$user['phone'],
            'address'=>$user['address'],
            'country_id'=>$user['country_id'],
            'city'=>$user['city'],
            'state'=>$user['state'],
            'postcode'=>$user['postcode'],
            'created_at'=>date('Y-m-d h:i:s',strtotime($user['created_at'])),
        ];
        User::create($user_data);
        return $this->sendRes('user created success', true, []);

    }
    public function get_user_permissions(Request $request){
        $module_key=$request->module_key;

        $user_role = User::with('roles')->where('id', $request->user_id)->first();

        if(count($user_role->roles)==0){
            return $this->sendRes('no role set to this user', false, []);

        }else {
            $module_data = Module::where('key_value', $module_key)->where('type','front')->first();
            if (!$module_data) {
                return $this->sendRes('module not found', false, []);
            } else {
                $module_permissions = Permession::where('module_id', $module_data->id)->get();
                $user_permission=[];
                $module_permissions_arr=$module_permissions->pluck('id')->toArray();
                for($i=0;$i<count($module_permissions_arr);$i++){
                    $roles_permessions = \DB::table('roles_permessions')
                        ->select('*')
                        ->where('permession_id',$module_permissions_arr[$i])
                        ->where('role_id', $user_role->roles[0]->id)
                        ->first();
                    $permission=Permession::where('id',$module_permissions_arr[$i])->first();
                    if($request->version == '2') {
                        if($roles_permessions) {
                            $user_permission['permessions'][] = $permission->key_value;
                        }
                    } else {
                        if($roles_permessions){
                            $user_permission[$permission->key_value]=1;
                        }else{
                            $user_permission[$permission->key_value]=0;
                        }
                    }
                }
                $user_permission['user_type']= $user_role->user_type->name;

                return $this->sendRes('user module permissions', true, $user_permission);

            }
        }
    }

    public function get_user_permission_by_code(Request $request, $key_value) {
        $user_role = User::with('roles')->where('id', $request->user_id)->first();

        if($user_role['roles'][0]) {
            $permession = $user_role['roles'][0]->permessions->where('key_value', $key_value)->first();
            if($permession) {
                return $this->sendRes('', true, $permession);
            } else {
                return $this->sendRes('the code not success', false);
            }

        } else {
            return $this->sendRes('no role set to this user', false);
        }
    }




    public function get_user_sub_modules(Request $request){
        $module_key=$request->module_key;

        $user_role = User::with('roles')->where('id', $request->user_id)->first();

        if(count($user_role->roles)==0){
            return $this->sendRes('no role set to this user', false, []);

        }else {
            $module_data = Module::where('key_value', $module_key)->where('type','front')->first();
            if (!$module_data) {
                return $this->sendRes('module not found', false, []);
            } else {
                $module_sub_modules = SubModule::where('module_id', $module_data->id)->get();
                $module_sub_modules_arr=[];
                foreach($module_sub_modules as $module_sub_module){
                    $module_sub_modules_arr[]=$module_sub_module->id;
                }
                $user_sub_modules=[];
                for($i=0;$i<count($module_sub_modules_arr);$i++){
                    $roles_sub_modules = \DB::table('roles_sub_modules')
                        ->select('*')
                        ->where('sub_module_id',$module_sub_modules_arr[$i])
                        ->where('role_id', $user_role->roles[0]->id)
                        ->first();
                    $sub_module=SubModule::where('id',$module_sub_modules_arr[$i])->first();
                    if($roles_sub_modules){
                        $user_sub_modules[$sub_module->key_value]=1;
                    }else{
                        $user_sub_modules[$sub_module->key_value]=0;

                    }
                }

                return $this->sendRes('user sub modules', true, $user_sub_modules);

            }
        }
        //  app()->setLocale($request->header('Accept-Language'));
//        $user_data=User::with('roles')->where('id',Auth::user()->id)->first();
//        $user_roles=$user_data->roles;
//        foreach($user_roles as $role) {
//            $account_module = Module::with('sub_modules')->where('key_value', $module_key)->first();
//            $submodule_arr=$account_module->sub_modules->pluck('id')->toArray();
//            return $submodule_arr;
//            if (!$account_module) {
//                return $this->sendRes('no module found', false, []);
//
//            }   else {
//            $role_data = Role::with('permessions')->where('uuid', $role->uuid)->firstOrFail();
//            return $role_data;
//            return $role_data->permessions->pluck('id')->toArray();
//
//            $group_by = 'group_by';
//            $permessions = [];
//            $groups_permessions = [];
//
//
//
//                if ($account_module) {
//                    if ($account_module->sub_modules) {
//                        foreach ($account_module->sub_modules as $submodule) {
//                            if ($submodule->name != 'modules' && $submodule->name != 'sub modules') {
//                                array_push($groups_permessions, $submodule->permessions->pluck('id')->toArray());
//                            }
//                        }
//                    }
//                }
//                $merged = array_merge(...$groups_permessions);
//                $permessions = Permession::whereIn('id', $merged)->get()->groupBy(['module_id', $group_by]);
//            }
//            return $permessions;
//        }


    }
    public function business_employees(Request $request){
        $keyword=$request->keyword;
        $business_id=$request->business_id;
        $branch_id=$request->branch_id;
        $city_id=$request->city_id;
        $country_id=$request->country_id;
        $role=$request->role_id;
        $employees_ids=$request->employees_ids;
        $business_secure_key=$request->header('business-secure-key')?? $request->header('business_secure_key');
        /*  if(!$business_secure_key){
              return $this->sendRes('business secure key not found', false, []);

          }else{*/
        $busines_data= Business::where(['id'=>$business_id])->first();
        if(!$busines_data){
            return $this->sendRes('business  not found', false, []);

        }else{
            if(isset($request->select_option) && $request->select_option == 'true') {
                $select = [
                    'id',
                    'name',
                ];
            } else {
                $select = [
                    'id',
                    'name as name',
                    'user_name',
                    'password as user_password',
                    'phone',
                    'uuid',
                    'branch_id',
                    'business_id'
                ];
            }

            $business_user= User::select($select)->where(['business_id'=>$business_id]);
            if(isset($branch_id)&&$branch_id!=null){
                $business_user=$business_user->where('branch_id',$branch_id);
            }
            if(isset($keyword)){
                $business_user=$business_user->whereRaw('(name like "%' . $keyword . '%" )')->orwhereRaw('(name_ar like "%' . $keyword . '%" )');;
            }
            if(isset($role)){
                $business_user->whereHas('roles', function($q) use($role){
                    $q->where('role_id',$role);
                });
            }
            if(isset($country_id)){
                $business_user=$business_user->where('country_id',$country_id);

            }
            if(isset($city_id)){
                $business_user=$business_user->where('city',$city_id);

            }
            if(isset($employees_ids)&&!empty($employees_ids)){
                $employees_arr=explode(",",$employees_ids);
                $business_user=$business_user->whereIn('id', $employees_arr);

            }

            if(isset($request->select_option) && $request->select_option == 'true') {
                $business_user=$business_user->get();
            } else {
                $business_user=$business_user->with('roles')->with('branch')->get();

            }



            return $this->sendRes('business employees', true, $business_user);

        }
        //}
    }
    public function business_employee_save(Request $request){
        app()->setLocale($request->header('Accept-Language'));
        $store_keeper=$request->store_keeper;
        $login_user_id=(isset($request->login_user_id))??'';
        $secure_business_key=$request->header('business-secure-key');
        $business = Business::where('secure_key', $secure_business_key)->first();
        if($login_user_id==''){
            if (!($request->header('user_token'))) {
                return $this->sendRes('error', false, 'User Token is required as a header.');
            }
            $result = \DB::table('personal_access_tokens')
                ->select('tokenable_id as user_id')
                ->where('token', $request->header('user_token'))
                ->first();
            if (!$result)
                return $this->sendRes('user not found', false, []);
            $result_login_user= $result->user_id;
        }else{
            $result_login_user=$login_user_id;
        }
        $user_password = $this->generate_password();
        if (isset($store_keeper) && $store_keeper == 1) {
            $store_keeper_role = Role::where('name', 'Store keeper')->where('type', 'standard')->first();
            if (isset($store_keeper_role))
                $user_job_id = $store_keeper_role->id;

            else {
                $store_keeper_role = Role::create(['uuid' => Str::uuid(), 'name' => 'Store Keeper', 'name_ar' => 'أمين مستودع', 'type' => 'standard', 'view_type' => 'admin']);
                $user_job_id = $store_keeper_role->id;

            }
        } else {
            $user_job_id = 0;
            $rules['job_id'] = ['required'];
            $rules['company_id'] = ['required'];
        }
        $rules = [
            'user_name' => ['unique:users,user_name'],
            'name' => ['required'],
            'phone' => ['required'],
           // 'country_id' => ['required'],
           // 'city_id' => ['required'],
            'email' => ['required'],
            'currency_id' => ['required'],
        ];


        $validator = Validator::make($request->all(), $rules);
        $message = implode('<br>', $validator->errors()->all());

        if ($validator->fails()) {
            return $this->sendRes($message, false, ['errors' => $validator->errors()]);

        }
        $api_data = [
            'email' => $request->email,
            'phone' => $request->phone,
            'user_name'=>$request->user_name,
            'name' => $request->name,
           // 'password'=>Hash::make($user_password),
            'business_id'=>$business->id,
            'address' => $request->address,
            'country_id' => $request->country_id,
            'city' => $request->city_id,
            'currency'=>(isset($request->currency_id))?$request->currency_id:'',
            'super_admin_role'=>0,
        ];

        $response = $this->create_user($api_data);
        if(!$response['status']&&$response['status']==false) {
            return $this->sendRes($response['message'], false,['errors'=>$response['data']]);

        }else {
            $user_id = $response['data']['id'];


           /* try {
                Mail::send('emails.confirm_register_email', ['password' => $user_password, 'username' => $request->user_name], function ($message) use ($request) {
                    $message->to($request->email);
                    $message->subject(trans('users.login_info'));
                });

            } catch (Exception $e) {
                return $this->sendRes('error in send mail to user', false, []);

            }*/
            $user_data = [
                'id'=>$user_id ,
                'business_id' => $business->id,
                'branch_id' => (isset($request->branch_id)) ? $request->branch_id : '',
                'currency_id' => (isset($request->currency_id)) ? $request->currency_id : '',
                'uuid' => \Str::uuid(),
                'name' => $request->name,
                'status' => '0',
                'email' => $request->email,
                'user_name' => $request->user_name,
              //  'password' => Hash::make($user_password),
                'phone' => $request->phone,
                'address' => $request->address,
                'country_id' => $request->country_id,
                'city' => $request->city_id,
                //'state'=>$user['state'],
                'postcode' => $request->postcode,
            ];


            $user_insert = User::create($user_data);
            if ($user_job_id == 0)
                $user_insert->roles()->attach($request->job_id);
            else
                $user_insert->roles()->attach($user_job_id);
            if($request->job_id!=''){
                $user_job=Role::where('id',$request->job_id)->first();
                if(isset($user_job)&&$user_job->create_cash_account==1){
                    ///add cash account
                    $cash_account_data = ['business_id' => $business->id, 'type' => 'cash', 'name' => $user_insert->user_name, 'representative_id' => $user_insert->id];

                    $cash_acc = $this->create_cash_account($secure_business_key, $cash_account_data);
                    if ($cash_acc['success'] == false) {
                        return $this->sendRes('error in create cash account', false, $cash_acc['message']);

                    } else {
                        $cash_account_id = $cash_acc['data']['id'];
                        User::where('id', $user_insert->id)->update(['cash_account_id' => $cash_account_id]);
                    }
                }

            }

            //add to log
            $data_log = $user_insert;
            $data_log['business_id'] = $user_insert->business_id;
            $data_log['transaction_id'] = $user_insert->id;
            $data_log['ip'] = $request->ip;
            $data_log['device'] = $request->device;
            $data_log['os'] = $request->os;
            $data_log['long_lat'] = $request->long_lat;
            (new Log)->addToLog($result_login_user, 'User', $data_log, NULL, 'Create');
            ////end log
            $business_data = Business::where('id', $user_insert->business_id)->first();
            $calendar_data = ['secure_business_key' => (isset($business_data)) ? $business_data->secure_key : '', 'user_id' => $user_insert->id, 'type' => 'user', 'name' => $user_insert->name];
            $this->create_default_calendar($calendar_data);
            Http::get(env('ACCOUNTING_API_URL') . '/api/check_currency_exist_business', ['currency_id' => $user_insert->currency_id, 'business_id' => $user_insert->business_id]);

            /// end user currency
            return $this->sendRes('user created success', true, $user_insert);
        }
    }
    public function business_employee_show($emp_id){
        $user_data=User::with('roles')->with('business')->with('branch')->where('id',$emp_id)->first();
        if(!$user_data){
            return $this->sendRes('user not found', false);

        }
        return $this->sendRes('user data', true, $user_data);
    }
    public function business_employee_update(Request $request,$emp_id){
        $user_data=User::where('id',$emp_id)->first();
        $business_id=$user_data->business_id;
        $secure_key='';
        $user_token=$request->header('user_token')??$request->header('user-token');
        $business_data=Business::where('id',$business_id)->first();
        if(isset($business_data))
            $secure_key=$business_data->secure_key;
        $store_keeper=$request->store_keeper;

        if (!($user_token)) {
            return $this->sendRes('error', false, 'User Token is required as a header.');
        }
        $result = \DB::table('personal_access_tokens')
            ->select('tokenable_id as user_id')
            ->where('token', $user_token)
            ->first();
        if (!$result)
            return $this->sendRes('user token expire please login again', false, []);
        /*if($result->user_id!=$emp_id){
            return response()->json([
                'status' => false,
                'message' => 'not authorize',
                'data' => []
            ],401);

        }*/
        if(!$user_data){
            return $this->sendRes('user not found', false);

        }
       /* if($emp_id!=$result->user_id){
            return $this->sendRes('not authorize', false);

        }*/
        app()->setLocale($request->header('Accept-Language'));
        $rules = [
            'user_name' => [Rule::unique('users')->where(function ($query) use ($emp_id) {
                return $query->where('id', '!=', $emp_id);
            })],
            'name' => ['required'],
            'phone' => ['required'],
           // 'country_id' => ['required'],
           // 'city_id' => ['required'],
            'email' => ['required'],
            'currency_id' => ['required']

        ];
        if (!isset($store_keeper)) {

            $rules['job_id'] = ['required'];
            $rules['company_id'] = ['required'];

        }
        $validator = Validator::make($request->all(), $rules);
        $message = implode('<br>', $validator->errors()->all());

        if ($validator->fails()) {
            return $this->sendRes($message, false, ['errors' => $validator->errors()]);

        }
        $api_data = [
            'id'=>$emp_id,
            'name'=>$request->name,
            'email'=>$request->email,
            'user_name'=>$request->user_name,
            'phone'=>$request->phone,
            'address'=>$request->address,
            'country_id'=>$request->country_id,
            'city'=>$request->city_id,
            'postcode'=>$request->postcode,
            'business_id'=>$request->company_id,
            'branch_id'=>(isset($request->branch_id))?$request->branch_id:'',
            'currency'=>(isset($request->currency_id))?$request->currency_id:'',

        ];

        $response = $this->update_user($api_data);
        if(!$response['status']&&$response['status']==false) {
            return $this->sendRes($response['message'], false,['errors'=>$response['data']]);

        }else {


            $updated_data = [

                'name' => $request->name,
                'email' => $request->email,
                'user_name' => $request->user_name,
                'phone' => $request->phone,
                'address' => $request->address,
                'country_id' => $request->country_id,
                'city' => $request->city_id,
                'postcode' => $request->postcode,
                'business_id' => $request->company_id,
                'branch_id' => (isset($request->branch_id)) ? $request->branch_id : '',
                'currency_id' => (isset($request->currency_id)) ? $request->currency_id : '',
            ];
            $user_data->update($updated_data);
            $user_data->roles()->sync($request->job_id);
            if($request->job_id!='') {
                $user_job = Role::where('id', $request->job_id)->first();
                if (isset($user_job) && $user_job->create_cash_account == 1&&$secure_key!='') {
                   $acc_response= $this->check_account_exists($secure_key,$user_data->id);
                   if($acc_response['success']==true){
                       $user_data_arr=['user_id'=>$user_data->id,'user_name'=>$user_data->name];
                      $this->cash_account_update($secure_key,$user_data_arr);
                   }else{
                       $user_job=Role::where('id',$request->job_id)->first();
                       if(isset($user_job)&&$user_job->create_cash_account==1){
                           ///add cash account
                           $cash_account_data = ['business_id' => $business_id, 'type' => 'cash', 'name' => $request->name, 'representative_id' => $user_data->id];

                           $cash_acc = $this->create_cash_account($secure_key, $cash_account_data);
                           if ($cash_acc['success'] == false) {
                               return $this->sendRes('error in create cash account', false, $cash_acc['message']);

                           } else {
                               $cash_account_id = $cash_acc['data']['id'];
                               User::where('id', $user_data->id)->update(['cash_account_id' => $cash_account_id]);
                           }
                       }
                   }
                }
                $this->update_update_occupation_id($emp_id,$secure_key);
            }

            return $this->sendRes('User update success', true, [$user_data]);
        }

    }
    function generate_password($length = 8){
        $chars =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.
            '0123456789*-=!@#$%^_+,.<>&';

        $str = '';
        $max = strlen($chars) - 1;

        for ($i=0; $i < $length; $i++)
            $str .= $chars[random_int(0, $max)];

        return $str;
    }
    public function all_user_permission()
    {
        $module_key = '';
        if (!($request->header('user_token'))) {
            return $this->sendRes('error', false, 'User Token is required as a header.');
        }
        $result = \DB::table('personal_access_tokens')
            ->select('tokenable_id as user_id')
            ->where('token', $request->header('user_token'))
            ->first();
        if (!$result)
            return $this->sendRes('user not found', false, []);
        $user_role = User::with('roles')->where('id', $result->user_id)->first();

        if (count($user_role->roles) == 0) {
            return $this->sendRes('no role set to this user', false, []);

        } else {
            $module_data = Module::where('key_value', $module_key)->first();
            if (!$module_data) {
                return $this->sendRes('module not found', false, []);
            } else {
                $module_permissions = Permession::where('module_id', $module_data->id)->get();
                $module_permissions_arr = [];
                foreach ($module_permissions as $module_permission) {
                    $module_permissions_arr[] = $module_permission->id;
                }
                $user_permission = [];
                for ($i = 0; $i < count($module_permissions_arr); $i++) {
                    $roles_permessions = \DB::table('roles_permessions')
                        ->select('*')
                        ->where('permession_id', $module_permissions_arr[$i])
                        ->where('role_id', $user_role->roles[0]->id)
                        ->first();
                    $permission = Permession::where('id', $module_permissions_arr[$i])->first();
                    if ($roles_permessions) {
                        $user_permission[$permission->key_value] = 1;
                    } else {
                        $user_permission[$permission->key_value] = 0;

                    }
                }

                return $this->sendRes('user module permissions', true, $user_permission);

            }
        }
    }
    public function get_user_type(Request $request){

    }
    //get user cash account info from accounting
    public function get_cash_account($user_id){
        $user_data=User::where('id',$user_id)->first();
        if (!$user_data) {
            return response()->json(
                [
                    'status' => 2,
                    'data' => 'User not found'

                ], 404);
        }else{
            if($user_data->cash_account_id==NULL){
                return response()->json(
                    [
                        'status' => 2,
                        'data' => 'No cash account for this user'

                    ], 404);
            }
            $url = env('ACCOUNTING_API_URL') . '/api/bank_cash_accounts/' . $user_data->cash_account_id;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_POST, 1);
            // curl_setopt($curl, CURLOPT_POSTFIELDS, $api_data);
            // return json_decode(curl_exec($curl), true);
            $result = json_decode(curl_exec($curl), true)['data'];

            return response()->json(
                [
                    'status' => 1,
                    'data' => ['cash_acc_id' => $user_data->cash_account_id, 'cash_acc_data' => $result]

                ], 200);
        }

    }
    public function latest_user(){
        $last_user=User::latest()->first();
        return $this->sendRes('user data', true, $last_user);

    }
    public function confirm_old_password(Request $request,$id){
        $user_data=User::where('id',$id)->first();
        $old_password=$request->old_password;
        app()->setLocale($request->header('Accept-Language'));
        $rules = [
            'old_password'=>['required'],

        ];

        $validator = Validator::make($request->all(), $rules);
        $message = implode('<br>', $validator->errors()->all());

        if($validator->fails()) {
            return $this->sendRes($message, false, ['errors'=>$validator->errors()]);

        }
        if (!($request->header('user_token'))) {
            return $this->sendRes('error', false, 'User Token is required as a header.');
        }
        $result = \DB::table('personal_access_tokens')
            ->select('tokenable_id as user_id')
            ->where('token', $request->header('user_token'))
            ->first();
        if (!$result)
            return $this->sendRes('user token expire please login again', false, []);
        if(!$user_data){
            return $this->sendRes('user not found', false);

        }
        if($id!=$result->user_id){
            return $this->sendRes('not authorize', false);

        }
        if (password_verify($old_password, $user_data->password)) {
            return $this->sendRes('password  correct', true);

        }else{
            return $this->sendRes('password not correct', false);


        }
    }
    public function employee_password_update(Request $request,$id){
        $user_data=User::where('id',$id)->first();
        if (!($request->header('user_token'))) {
            return $this->sendRes('error', false, 'User Token is required as a header.');
        }
        $result = \DB::table('personal_access_tokens')
            ->select('tokenable_id as user_id')
            ->where('token', $request->header('user_token'))
            ->first();
        if (!$result)
            return $this->sendRes('user token expire please login again', false, []);
        /*if($result->user_id!=$emp_id){
            return response()->json([
                'status' => false,
                'message' => 'not authorize',
                'data' => []
            ],401);

        }*/
        if(!$user_data){
            return $this->sendRes('user not found', false);

        }
        if($id!=$result->user_id){
            return $this->sendRes('not authorize', false);

        }
        app()->setLocale($request->header('Accept-Language'));

        $rules = [
            'password'=>['required'],

        ];

        $validator = Validator::make($request->all(), $rules);
        $message = implode('<br>', $validator->errors()->all());

        if($validator->fails()) {
            return $this->sendRes($message, false, ['errors'=>$validator->errors()]);

        }
        $updated_data=[
            'password' => Hash::make($request->password)
        ];
        $user_data->update($updated_data);
        $api_data = [
            'password'=>Hash::make($request->password),
            'change_password'=>1,
            'id'=>$id,

        ];

        $response = $this->update_user($api_data);
        return $this->sendRes('User Password update success', true, [$user_data]);

        /*if(!$response['status']&&$response['status']==false) {
            return $this->sendRes($response['message'], false,['errors'=>$response['data']]);

        }else {
            return $this->sendRes('User Password update success', true, [$user_data]);
        }*/
    }

    public function accounting_user_save(Request $request){
        $business_id=$request->business_id;
        $user_id=$request->user_id;
        $user_data = [
            'id'=>$user_id ,
            'business_id' => $business_id,
            //'branch_id' => (isset($request->branch_id)) ? $request->branch_id : '',
            'currency_id' => (isset($request->currency_id)) ? $request->currency_id : '',
            'uuid' => \Str::uuid(),
            'name' => $request->name,
            'type' => 'user',
            'status' => '1',
            'email' => $request->email,
            'user_name' => $request->user_name,
            'password' => $request->password,
            'phone' => $request->phone,
            'address' => $request->address,
            'country_id' => $request->country_id,
            'city' => $request->city_id,
            //'state'=>$user['state'],
            'postcode' => $request->postcode,
        ];


        $user_insert = User::create($user_data);
    }
   /* public function get_warehouse_employees($business_id){
     $warhouse_role=
    }*/
    public function register_super_admin(Request $request){
        $user_id=$request->user_id;
        $user = User::create([
            'id'=>$user_id,
            'uuid' => \Str::uuid(),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'user_name'=>$request->user_name,
            'email_verification_code' => rand(1000000, 100000000),
            'status' => '0',
            'user_type_id'=> 3,

            //'phone_code'=>$phone_code,
        ]);
        $cs_admin_role=Role::where(['user_type' => 'cs_admin'])->first();
        if(isset($cs_admin_role))
            $user->roles()->attach($cs_admin_role->id);
        return $this->sendRes('User created', true, []);

    }
}
