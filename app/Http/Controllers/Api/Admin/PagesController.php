<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Http\Resources\PageResource;
use App\Models\City;
use App\Models\Country;
use App\Models\Page;
use App\Models\ShippingMethod;
use App\Models\Translation;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PagesController extends Controller
{
    use Res;



    public function privacy_policy(Request $request)
    {
        $rules = [
            'lang_id' => ['required', 'array'],
            'lang_id.*' => ['required', 'exists:languages,id'],
            'title' => ['required', 'array'],
            'title.*' => ['required', 'string', 'max:255'],
            'content' => ['required', 'array'],
            'content.*' => ['required', 'string', 'max:255'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }
        $page = Page::where('key_type', 'privacy_policy')->first();
        if ($page) {
            Translation::where([
                'translatable_model' => Page::class,
                'translatable_id'   => $page->id,
            ])->delete();
            $message = translate('page updated success');
        } else {
            $data['key_type'] = 'privacy_policy';
            $data['uuid'] = \Str::uuid();
            $page = Page::create($data);
            $message = translate('page created success');
        }

        // Translations
        if ($request->lang_id) {
            foreach ($request->lang_id as  $i => $val) {
                foreach (['title', 'content'] as $key) {
                    Translation::create([
                        'translatable_model' => Page::class,
                        'translatable_id'   => $page->id,
                        'lang_id'           => $request->lang_id[$i],
                        'lang_key'               => $key,
                        'lang_value'             => $request[$key][$i],
                    ]);
                }
            }
        }

        return $this->sendRes($message, true, [], [], 200);
    }


    public function privacy_policy_show(Request $request)
    {
        $page = Page::where('key_type', 'privacy_policy')->first();
        if (!$page) {
            return $this->sendRes(translate('page not found'), false, [], [], 400);
        }
        $page->title = $page->translations('title');
        $page->content = $page->translations('content');
        return $this->sendRes(translate('page found'), true, $page);
    }
}
