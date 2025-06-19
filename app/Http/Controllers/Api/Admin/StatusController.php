<?php

namespace App\Http\Controllers\Api\Admin;

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

    public function index(Request $request)
    {
        $statuses = Status::latest();

        $keyword = request('keyword');
        $per_page = request('per_page') ?? 12;
        $default = request('default');
        $type = request('type');
        if($keyword) {
            $statuses = $statuses
            ->where('name', 'like', "%$keyword%")
            ->orwhere('color', 'like', "%$keyword%")
            ->orwhere('border', 'like', "%$keyword%")
            ->orwhere('bg', 'like', "%$keyword%");
        }

        if($default) {
            $statuses = $statuses->where('default', $default);
        }
        if($type) {
            $statuses = $statuses->where('type', $type);
        }

        $statuses = $statuses->paginate($per_page);
        return $this->sendRes(translate('all statuses'), true, $statuses);
    }


    public function form(Request $request, $status = null) {
        $rules = [
            'type' => ['required', 'in:processing,finished'],
            'default' => ['required', 'in:1,0'],
            'name' => ['required', 'string', 'max:255', 'unique:tb_status,name'],
            'color' => ['required', 'string', 'max:255'],
            'border' => ['required', 'string', 'max:255'],
            'bg' => ['required', 'string', 'max:255'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $data = [
            'default' => 0,
            'type' => $request->type,
            'name' => $request->name,
            'color' => $request->color,
            'border' => $request->border,
            'bg' => $request->bg,
        ];

        if($request->default) {
            if($request->default == 1) {
                Status::where('default', 1)->update(['default' => 0]);
                $data['default'] = 1;
            }
        }

        if($status) {
            $message = translate('status has been updated successfully');
            $status->update($data);
        } else {
            $message = translate('status has been created successfully');
            $data['uuid'] = \Str::uuid();
            $status = Status::create($data);
        }

        return $this->sendRes($message, true, $status);
    }

    public function store(Request $request)
    {
        return $this->form($request);
    }

    public function edit(Request $request, $uuid) {
        $status = Status::where('uuid', $uuid)->first();
        if(!$status) {
            return $this->sendRes(translate('status not found'), false, [], [], 400);
        }
        return $this->form($request, $status);
    }

    public function show(Request $request, $uuid) {
        $status = Status::where('uuid', $uuid)->first();
        if(!$status) {
            return $this->sendRes(translate('status not found'), false, [], [], 400);
        }
        return $this->sendRes(translate('status found'), true, $status);
    }

    public function delete(Request $request, $uuid)
    {
        $status = Status::where('uuid', $uuid)->first();
        if (!$status) {
            return $this->sendRes(translate('status not found'), false, [], [], 400);
        }

        $status->delete();
        return $this->sendRes(translate('status deleted successfully'), true);
    }



}
