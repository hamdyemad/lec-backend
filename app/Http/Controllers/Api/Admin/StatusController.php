<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\SendMessage;
use App\Http\Controllers\Controller;
use App\Http\Resources\StatusResource;
use App\Models\ApiKey;
use App\Models\Cart;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\Message;
use App\Models\Order;
use App\Models\Status;
use App\Models\Translation;
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

        $statuses->getCollection()->transform(function($status) {
            return new StatusResource($status);
        });

        return $this->sendRes(translate('all statuses'), true, $statuses);
    }


    // public function form(Request $request, $status = null) {
    //     $rules = [
    //         'type' => ['required', 'in:default,accept,reject,other'],
    //         'lang_id' => ['required', 'array'],
    //         'lang_id.*' => ['required', 'exists:languages,id'],
    //         'name' => ['required', 'array'],
    //         'name.*' => ['required', 'string', 'max:255'],
    //         'color' => ['required', 'string', 'max:255'],
    //         'border' => ['required', 'string', 'max:255'],
    //         'bg' => ['required', 'string', 'max:255'],
    //     ];
    //     $validator = Validator::make($request->all(), $rules);
    //     if($validator->fails()) {
    //         return $this->errorResponse($validator);
    //     }

    //     $oldStatusType = Status::where('type', $request->type)->first();
    //     if($oldStatusType){
    //         $oldStatusType->type = 'other';
    //         $oldStatusType->save();
    //     }

    //     $data = [
    //         'type' => $request->type,
    //         'color' => $request->color,
    //         'border' => $request->border,
    //         'bg' => $request->bg,
    //     ];

    //     if($status) {
    //         Translation::where([
    //             'translatable_model' => Status::class,
    //             'translatable_id'   => $status->id,
    //         ])->delete();

    //         $message = translate('status has been updated successfully');
    //         $status->update($data);
    //     } else {
    //         $message = translate('status has been created successfully');
    //         $data['uuid'] = \Str::uuid();
    //         $data['type'] = $request->type;
    //         $status = Status::create($data);
    //     }

    //     // Translations
    //     if ($request->lang_id) {
    //         foreach ($request->lang_id as  $i => $val) {
    //             foreach (['name'] as $key) {
    //                 Translation::create([
    //                     'translatable_model' => Status::class,
    //                     'translatable_id'   => $status->id,
    //                     'lang_id'           => $request->lang_id[$i],
    //                     'lang_key'               => $key,
    //                     'lang_value'             => $request[$key][$i],
    //                 ]);
    //             }
    //         }
    //     }

    //     return $this->sendRes($message, true, [], [], 200);
    // }

    // public function store(Request $request)
    // {
    //     return $this->form($request);
    // }

    // public function edit(Request $request, $uuid) {
    //     $status = Status::where('uuid', $uuid)->first();
    //     if(!$status) {
    //         return $this->sendRes(translate('status not found'), false, [], [], 400);
    //     }
    //     return $this->form($request, $status);
    // }

    // public function show(Request $request, $uuid) {
    //     $status = Status::where('uuid', $uuid)->first();
    //     if(!$status) {
    //         return $this->sendRes(translate('status not found'), false, [], [], 400);
    //     }
    //     $status->name = $status->translations('name');
    //     return $this->sendRes(translate('status found'), true, $status);
    // }

    // public function delete(Request $request, $uuid)
    // {
    //     $status = Status::where('uuid', $uuid)->first();
    //     if (!$status) {
    //         return $this->sendRes(translate('status not found'), false, [], [], 400);
    //     }
    //     Translation::where([
    //         'translatable_model' => Status::class,
    //         'translatable_id'   => $status->id,
    //     ])->delete();
    //     $status->delete();
    //     return $this->sendRes(translate('status deleted successfully'), true);
    // }



}
