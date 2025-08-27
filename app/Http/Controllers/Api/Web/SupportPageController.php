<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupportPageResource;
use App\Models\ApiKey;
use App\Models\Category;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\Product;
use App\Models\SupportPage;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SupportPageController extends Controller
{
    use Res, FileUploads;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */



    public function index(Request $request)
    {
        $authUser = auth()->user();

        $validator = Validator::make($request->all(), [
            'per_page' => ['nullable', 'integer', 'min:1'],
        ]);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }
        $per_page = $request->per_page ?? 12;
        $supportPages = SupportPage::latest();
        $supportPages->getCollection()->transform(function ($item) {
            return new SupportPageResource($item);
        });

        $supportPages = $supportPages->paginate($per_page);

        return $this->sendRes(translate('support pages data'), true, $supportPages);
    }




    public function show(Request $request, $uuid) {
        $supportPage = SupportPage::where('uuid', $uuid)->first();
        if(!$supportPage) {
            return $this->sendRes(translate('support page not found'), false, [], [], 400);
        }

        return $this->sendRes(translate('support page found'), true, $supportPage);
    }



}
