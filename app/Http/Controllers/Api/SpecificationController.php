<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpecificationResource;
use App\Models\ApiKey;
use App\Models\Category;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\Specification;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SpecificationController extends Controller
{
    use Res, FileUploads;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $specifications = Specification::latest();
        $per_page = $request->get('per_page', 12);
        $keyword = $request->keyword ?? '';

        $specifications = $specifications->latest();
        if($keyword) {
            $specifications = $specifications->whereHas('translationsRelations', function ($q) use ($keyword) {
                $q->Where('lang_value', 'like', "%{$keyword}%")
                ->where(function($query) {
                    $query->where('lang_key', "header")
                    ->orWhere('lang_key', "body");
                });
            });
        }
        $specifications = $specifications->paginate($per_page);
        $specifications->getCollection()->transform(function($specification) {
            return new SpecificationResource($specification);
        });
        return $this->sendRes(translate('all specifications'), true, $specifications);
    }

}
