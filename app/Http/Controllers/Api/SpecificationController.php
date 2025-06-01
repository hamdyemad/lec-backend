<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $specifications = $specifications->paginate($per_page);

        return $this->sendRes('all specifications', true, $specifications);
    }


    public function form(Request $request, $specification = null) {

        $rules = [
            'header' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $data = [
            'header' => $request->header,
            'body' => $request->body,
        ];

        if(isset($request->image)) {
            if($specification) {
                (file_exists($specification->image)) ? unlink($specification->image) : '';
            }
            $image = $this->uploadFile($request, $this->specifications_path, 'image');
            $data['image'] = $image;
        }

        if($specification) {
            $message = translate('specification updated successfully');
            $specification->update($data);
        } else {
            $message = translate('specification added successfully');

            $data['uuid'] = \Str::uuid();
            $specification = specification::create($data);
        }

        return $this->sendRes($message, true, $specification);
    }

    public function store(Request $request)
    {
        return $this->form($request);
    }

    public function edit(Request $request, $uuid)
    {
        $specification = Specification::where('uuid', $uuid)->first();
        if(!$specification) {
            return $this->sendRes(translate('specification not found'), false, [], [], 400);
        }
        return $this->form($request, $specification);

    }


    public function show(Request $request, $uuid) {
        $specification = Specification::where('uuid', $uuid)->first();
        if(!$specification) {
            return $this->sendRes(translate('specification not found'), false, [], [], 400);
        }
        return $this->sendRes(translate('specification found'), true, $specification);
    }


    public function delete(Request $request, $uuid) {
        $specification = Specification::where('uuid', $uuid)->first();
        if(!$specification) {
            return $this->sendRes(translate('specification not found'), false, [], [], 400);
        }
        (file_exists($specification->image)) ? unlink($specification->image) : '';
        $specification->delete();
        return $this->sendRes(translate('specification deleted successfully'), true, [], [], 200);
    }




}
