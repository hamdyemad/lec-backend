<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CaseTypeResource;
use App\Models\CaseType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    use  Res, FileUploads;

    public function __construct() {}

    public function index(Request $request)
    {
        $cases_types = CaseType::all();
        $cases_types = CaseTypeResource::collection($cases_types);
        return $this->sendRes('all cases types', true, $cases_types);
    }



}
