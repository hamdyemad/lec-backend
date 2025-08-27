<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use Res, FileUploads;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct() {}


    public function index(Request $request)
    {

        $paginate = $request->paginate;

        $rules = [
            'paginate' => ['nullable', 'integer'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }


        $users = User::with('category')->latest();

        $select = ['id', 'uuid', 'type', 'status', 'username', 'image', 'email', 'dateOfBirth',
        'mobile', 'mobile_code', 'category_id', 'latitude', 'longitude', 'created_at', 'updated_at'];

        if($request->username) {
            $users = $users->where('username', 'like', "%$request->username%");
        }
        if($request->email) {
            $users = $users->where('email', 'like', "%$request->email%");
        }
        if($request->dateOfBirth) {
            $users = $users->where('dateOfBirth', 'like', "%$request->dateOfBirth%");
        }
        if($request->mobile) {
            $users = $users->where('mobile', 'like', "%$request->mobile%");
        }
        if($request->category_id) {
            $users = $users->where('category_id', 'like', "%$request->category_id%");
        }
        if($request->latitude) {
            $users = $users->where('latitude', 'like', "%$request->latitude%");
        }
        if($request->longitude) {
            $users = $users->where('longitude', 'like', "%$request->longitude%");
        }

        $users = $users->select($select);
        if($paginate) {
            $users = $users->paginate($paginate);
        } else {
            $users = $users->get();
        }

        return $this->sendRes(__('validation.success'), true, $users);

    }

    public function users_types()
    {

        $user_types = UserType::all();

        return $this->sendRes(__('validation.success'), true, $user_types);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $user = auth()->user();
        $user_data = [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'type' => $user->type,
            'username' => $user->username,
            'email' => $user->email,
            'image' => $user->image,
            'status' => $user->status,
            'mobile_code' => $user->mobile_code,
            'mobile' => $user->mobile,
            'tax_num' => $user->tax_num,
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
            'section' => $user->section,
            'wallet' => $user->wallet,
            'dateOfBirth' => $user->dateOfBirth,
        ];

        if($user->type == '3') {
            $user_data['driver'] = $user->driver;
        }

        return $this->sendRes('done', true, $user_data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update_profile(Request $request) {

        $user = auth()->user();
        $rules = [
            // 'mobile_code' => ['nullable', 'string'],
            // 'mobile' => ['nullable', 'numeric', 'unique:rc_users,mobile'],
            'name' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
            'tax_num' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'string', 'max:255'],
            'longitude' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email',  Rule::unique('rc_users', 'email')->ignore($user->id),'max:255'],
            'dateOfBirth' => ['nullable', 'date','max:255'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $updated_array = [];
        // ($request->mobile_code) ? $updated_array['mobile_code'] = $request->mobile_code : '';
        // ($request->mobile) ? $updated_array['mobile'] = $request->mobile : '';
        ($request->name) ? $updated_array['username'] = $request->name : '';
        ($request->tax_num) ? $updated_array['tax_num'] = $request->tax_num : '';
        ($request->latitude) ? $updated_array['latitude'] = $request->latitude : '';
        ($request->longitude) ? $updated_array['longitude'] = $request->longitude : '';
        ($request->email) ? $updated_array['email'] = $request->email : '';
        ($request->dateOfBirth) ? $updated_array['dateOfBirth'] = $request->dateOfBirth : '';

        if(isset($request->image)) {
            (file_exists($user->image)) ? unlink($user->image) : '';
            $image = $this->uploadFile($request, $this->users_path . $user->id . '/', 'image');
            $updated_array['image'] = $image;
        }

        (count($updated_array) > 0) ? $user->update($updated_array) : '';
        return $this->sendRes(__('auth.user information has been updated'), true, $user);

    }

    public function update_password(Request $request) {

        $user = auth()->user();
        $rules = [
            'old_password' => ['required', 'min:8'],
            'password' => ['required', 'confirmed','min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\d]).+$/','max:12'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $checked_password = Hash::check($request->old_password,$user->password);
        if($checked_password) {
            $user->update([
                'password' => Hash::make($request->password)
            ]);
            return $this->sendRes(__('auth.password has been changed successfully'), true);
        } else {
            return $this->sendRes(__('auth.old password is incorrect'), false, [], [], 400);
        }

    }

    public function delete_account(Request $request){
        $user = auth()->user();
        if($user) {
            $user->delete();
            // If the user is driver
            if($user->type == '3') {
                $response = $this->logistiService->deActivate($user->driver->idNumber);
                // return $response;
                if($response['status'] == false) {
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
                                    return $this->sendRes('logisti errors', false, [], [$message], 400);
                                }
                            }
                        }
                        return $this->sendRes('logisti errors apis with codes', false, [], $response['errorCodes'], 400);
                    }
                }
            }
            return $this->sendRes(__('validation.success'), true);
        } else {
            return $this->sendRes(__('auth.user not found'), false, [], [], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
