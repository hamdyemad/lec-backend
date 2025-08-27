<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Models\FeatureType;
use App\Models\SocialMedia;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PagesController extends Controller
{
    use Res, FileUploads;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $feature_type = FeatureType::where('name', 'page')->first();
        $pages = $feature_type->features()->select(['id', 'title', 'content', 'details'])->get();
        return $this->sendRes(__('validation.success'), true, $pages);
    }


    public function socialmedia()
    {

        $socialmedias = SocialMedia::latest()->get();
        return $this->sendRes(__('validation.success'), true, $socialmedias);
    }


}
