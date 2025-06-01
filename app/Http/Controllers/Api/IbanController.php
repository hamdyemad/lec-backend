<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class IbanController extends Controller
{
    use Res, FileUploads;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function store(Request $request)
    {

        $user = auth()->user();
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'regex:/^(05\d{8}|009665\d{7}|\+9665\d{7}|9665\d{7})$/'],
            'country' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'iban_number' => ['required', 'string', 'max:255'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        if($user->iban) {
            $user->iban->update([
                'name' => $request->name,
                'mobile' => $request->mobile,
                'country' => $request->country,
                'city' => $request->city,
                'iban_number' => $request->iban_number,
            ]);

        } else {
            $user->iban()->create([
                'name' => $request->name,
                'mobile' => $request->mobile,
                'country' => $request->country,
                'city' => $request->city,
                'iban_number' => $request->iban_number,
            ]);
        }
        return $this->sendRes(__('validation.success'), true);

    }


}
