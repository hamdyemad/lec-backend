<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\CategoryResource;
use App\Http\Resources\Web\ReviewResource;
use App\Models\ApiKey;
use App\Models\Category;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\NewsLetter;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NewsLetterController extends Controller
{
    use Res;


    public function store(Request $request)
    {

        $rules = [
            'email' => ['required', 'email', 'string', 'max:255', 'unique:newsletters,email'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        NewsLetter::create([
            'email' => $request->email,
        ]);
        return $this->sendRes(__('main.subscribed successfully'), true);

    }



}
