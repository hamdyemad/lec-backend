<?php

namespace App\Http\Controllers\Api;

use App\Events\SendMessage;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Cart;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\Message;
use App\Models\Order;
use App\Models\Status;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StatusController extends Controller
{
    use Res, FileUploads;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {

    }


    function index(Request $request) {
        $paginate = $request->paginate;
        $statuses = Status::latest();

        if($paginate) {
            $statuses = $statuses->paginate($paginate);
        } else {
            $statuses = $statuses->get();
        }

        return $this->sendRes(__('statuses.list'), true, $statuses);

    }


}
