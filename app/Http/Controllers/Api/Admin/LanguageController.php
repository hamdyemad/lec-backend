<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\Translate;
use App\Models\Language;
use App\Models\Translation;
use App\Traits\Res;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LanguageController extends Controller
{
    use Res;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = request('per_page') ?? 12;
        $languages = Language::orderBy('name')->paginate($per_page);
        return $this->sendRes(translate('all languages'), true, $languages);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update_translation(Request $request)
    {
        $rules = [
            'key' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:255'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->errorResponse($validator);
        }
        $lang = $request->lang;
        $language = Language::where('code', $lang)->first();
        if($language) {
            $translation = Translation::where([
                'lang_id' => $language->id,
                'lang_key' => $request->key
            ])->first();
            if($translation) {
                $translation->lang_value = $request->value;
                $translation->save();
            } else {
                $translation = Translation::create([
                    'lang_id' => $language->id,
                    'lang_key' => $request->key,
                    'lang_value' => $request->value
                ]);
            }
            return $this->sendRes(translate('translation success'), true, $translation, [], 200);
        } else {
            return $this->sendRes(translate('language not found'), false, [], [], 400);
        }

    }

    public function show(Request $request, $id) {
        $language = Language::find($id);

        $keyword = request('keyword');
        $translations = $language->translations();

        if($keyword) {
            $translations = $translations
            ->where(function($query) use ($keyword) {
                $query->where('lang_key', 'like', "%$keyword%")
                ->orWhere('lang_value', 'like', "%$keyword%");
            });
        }
        $translations = $translations->paginate(12);
        $language->translations = $translations;
        if($language) {
            return $this->sendRes(translate('language returned success'), true, $language, [], 200);
        } else {
            return $this->sendRes(translate('language not found'), false, [], [], 400);
        }
    }
}
