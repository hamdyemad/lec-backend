<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
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
            'type' => ['nullable', 'in:help_and_support,about']
        ]);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $type = $request->type ?? '';
        $per_page = $request->per_page ?? 12;

        $supportPages = SupportPage::latest();


        if($type) {
            $supportPages = $supportPages->where('type', $type);
        }

        $supportPages = $supportPages->paginate($per_page);

        return $this->sendRes(translate('support pages data'), true, $supportPages);
    }


    public function form(Request $request, $supportPage = null) {

        $rules = [
            'type' => ['required', 'string', 'max:255', 'in:help_and_support,about'],
            'header' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:255'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $data = [
            'type' => $request->type,
            'header' => $request->header,
            'body' => $request->body,
        ];


        if($supportPage) {
            $message = translate('support page updated successfully');
            $supportPage->update($data);
        } else {
            $message = translate('support page added successfully');
            $data['uuid'] = \Str::uuid();
            $supportPage = SupportPage::create($data);
        }

        return $this->sendRes($message, true, $supportPage);
    }

    public function store(Request $request)
    {
        return $this->form($request);
    }

    public function edit(Request $request, $uuid)
    {
        $supportPage = SupportPage::where('uuid', $uuid)->first();
        if(!$supportPage) {
            return $this->sendRes(translate('support page not found'), false, [], [], 400);
        }
        return $this->form($request, $supportPage);

    }


    public function show(Request $request, $uuid) {
        $supportPage = SupportPage::where('uuid', $uuid)->first();
        if(!$supportPage) {
            return $this->sendRes(translate('support page not found'), false, [], [], 400);
        }

        return $this->sendRes(translate('support page found'), true, $supportPage);
    }


    public function delete(Request $request, $uuid) {
        $supportPage = SupportPage::where('uuid', $uuid)->first();
        if(!$supportPage) {
            return $this->sendRes(translate('support page not found'), false, [], [], 400);
        }
        $supportPage->delete();
        return $this->sendRes(translate('support page deleted successfully'), true);

    }




}
