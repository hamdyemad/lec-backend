<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpecificationResource;
use App\Models\ApiKey;
use App\Models\Category;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\Specification;
use App\Models\Translation;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Resources\CategoryResource;


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

        if($keyword) {
            $specifications = $specifications->whereHas('translationsRelations', function ($q) use ($keyword) {
                $q->Where('lang_value', 'like', "%{$keyword}%")
                ->where(function($query) {
                    $query->where('lang_key', "header")
                    ->orWhere('lang_key', "bodyu");
                });
            });
        }

        $specifications = $specifications->paginate($per_page);
        $specifications->getCollection()->transform(function ($item) {
            return new SpecificationResource($item);
        });


        return $this->sendRes('all specifications', true, $specifications);
    }


    public function form(Request $request, $specification = null) {

        $rules = [
            'translations' => ['required', 'array'],
            'translations.*' => ['required', 'array'],
            'translations.*.lang_id' => ['required', 'exists:languages,id'],
            'translations.*.header' => ['required', 'string', 'max:255'],
            'translations.*.body' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->errorResponse($validator);
        }


        $data = [];


        if(isset($request->image)) {
            if($specification) {
                (file_exists($specification->image)) ? unlink($specification->image) : '';
            }
            $image = $this->uploadFile($request, $this->specifications_path, 'image');
            $data['image'] = $image;
        }

        if($specification) {
            $message = translate('specification updated successfully');

            // Remove Old Translations
            Translation::where([
                'translatable_model' => Specification::class,   // ✅ fix: not "translatable_model"
                'translatable_id'   => $specification->id,
            ])->delete();

            $specification->update($data);
        } else {
            $message = translate('specification added successfully');
            $data['uuid'] = \Str::uuid();
            $specification = specification::create($data);
        }

        // Translations
        if($request->translations) {
            foreach($request->translations as  $translation) {
                foreach (['header', 'body'] as $key) {
                    Translation::create([
                        'translatable_model' => Specification::class,   // ✅ fix: not "translatable_model"
                        'translatable_id'   => $specification->id,
                        'lang_id'           => $translation['lang_id'],
                        'lang_key'               => $key,
                        'lang_value'             => $translation[$key],
                    ]);
                }
            }
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
        $specification = [
            "id" => $specification->id,
            "uuid" => $specification->uuid,
            "header" => $specification->translations('header'),
            "body" => $specification->translations('body'),
            "image" => $specification->image,
            "created_at" => $specification->created_at,
        ];
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
