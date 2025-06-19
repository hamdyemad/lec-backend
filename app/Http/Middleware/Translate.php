<?php

namespace App\Http\Middleware;

use App\Models\ApiKey as ModelsApiKey;
use App\Models\Language;
use App\Traits\Res;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class Translate
{
    use Res;

    public function handle(Request $request, Closure $next)
    {
        $current_lang = Language::where('code', $request->header('lang'))->first();
        if ($current_lang) {
            app()->setLocale($current_lang->code);
        } else {
            return $this->sendRes(translate('language is not found please check the header (lang) is passed or not'), false, [], [], 400);
        }
        $request['lang'] = app()->getLocale();
        $request['lang_id'] = $current_lang->id;
        return $next($request);
    }
}
