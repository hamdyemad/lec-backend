<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    use Res, FileUploads;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $feature_type = FeatureType::where('name', 'section')->first();

        $sections = $feature_type->features()->select(['id', 'feature','title']);

        if($request->title) {
            $sections = $sections->where('title', 'like', "%$request->title%");
        }
        $sections = $sections->get();
        return $this->sendRes('all sections', true, $sections);
    }


    public function store(Request $request)
    {
        $rules = [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $data = [
            'ar' => $request->name_ar,
            'en' => $request->name_en,
        ];

        $data = serialize($data);

        $feature_type = FeatureType::where('name', 'section')->first();

        $section = $feature_type->features()->create([
            'title' => $data
        ]);

        return $this->sendRes(__('sections.add success'), true, $section);
    }

    public function edit(Request $request, $section_id)
    {
        $feature_type = FeatureType::where('name', 'section')->first();
        $section = $feature_type->features()->select(['id', 'feature','title'])->find($section_id);
        if($section) {
            $rules = [
                'name_en' => ['required', 'string', 'max:255'],
                'name_ar' => ['required', 'string', 'max:255'],
            ];

            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()) {
                $message = implode('<br>', $validator->errors()->all());
                return $this->sendRes($message, false, [], $validator->errors(), 400);
            }
            $data = [
                'ar' => $request->name_ar,
                'en' => $request->name_en,
            ];
            $data = serialize($data);
            $section->update([
                'title' => $data
            ]);
            return $this->sendRes(__('sections.update success'), true, $section);
        } else {
            return $this->sendRes(__('sections.not found'), false, [], [], 400);
        }
    }


    public function delete(Request $request, $section_id)
    {

        $feature_type = FeatureType::where('name', 'section')->first();
        $section = $feature_type->features()->find($section_id);
        if($section) {
            $section->delete();
            return $this->sendRes(__('sections.delete success'), true);
        } else {
            return $this->sendRes(__('sections.not found'), false, [], [], 400);
        }
    }



}
