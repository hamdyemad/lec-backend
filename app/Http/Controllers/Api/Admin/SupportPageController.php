<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupportPageResource;
use App\Models\ApiKey;
use App\Models\Category;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\Product;
use App\Models\SupportPage;
use App\Models\Translation;
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
        $keyword = $request->keyword ?? '';
        $supportPages = SupportPage::latest();


        if($type) {
            $supportPages = $supportPages->where('type', $type);
        }

        if ($keyword) {
            $supportPages = $supportPages->whereHas('translationsRelations', function ($q) use ($keyword) {
                $q->Where('lang_value', 'like', "%{$keyword}%")
                ->where(function($query) use($keyword) {
                    $query->where('lang_key', "header")
                    ->orWhere('lang_key', 'body');
                });
            });
        }

        $supportPages = $supportPages->paginate($per_page);

        $supportPages->getCollection()->transform(function ($item) {
            return new SupportPageResource($item);
        });


        return $this->sendRes(translate('support pages data'), true, $supportPages);
    }


    public function form(Request $request, $supportPage = null) {

        $rules = [
            'translations' => ['required', 'array'],
            'translations.*' => ['required', 'array'],
            'type' => ['required',  'in:help_and_support,about'],
            'translations.*.lang_id' => ['required', 'exists:languages,id'],
            'translations.*.header' => ['required', 'string'],
            'translations.*.body' => ['required', 'string'],

        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->errorResponse($validator);
        }


        $data = [
            'type' => $request->type,
        ];

        if($supportPage) {
            $message = translate('support page updated successfully');

            Translation::where([
                'translatable_model' => SupportPage::class,   // ✅ fix: not "translatable_model"
                'translatable_id'   => $supportPage->id,
            ])->delete();

            $supportPage->update($data);
        } else {
            $message = translate('support page added successfully');
            $data['uuid'] = \Str::uuid();
            $supportPage = SupportPage::create($data);
        }

        if($request->translations) {
            foreach($request->translations as  $translation) {
                foreach (['header', 'body'] as $key) {
                    Translation::create([
                        'translatable_model' => SupportPage::class,   // ✅ fix: not "translatable_model"
                        'translatable_id'   => $supportPage->id,
                        'lang_id'           => $translation['lang_id'],
                        'lang_key'               => $key,
                        'lang_value'             => $translation[$key],
                    ]);
                }
            }
        }


        return $this->sendRes($message, true);
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

        $supportPage = [
            "id" => $supportPage->id,
            "uuid" => $supportPage->uuid,
            "header" => $supportPage->translations('header'),
            "body" => $supportPage->translations('body'),
            "image" => $supportPage->image,
            "created_at" => $supportPage->created_at,
        ];


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
