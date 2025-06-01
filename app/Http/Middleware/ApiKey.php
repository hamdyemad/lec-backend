<?php

namespace App\Http\Middleware;

use App\Models\ApiKey as ModelsApiKey;
use App\Traits\Res;
use Closure;
use Illuminate\Http\Request;

class ApiKey
{
    use Res;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        if($request->hasHeader('x-api-key')) {
            $apiKeyModel = ModelsApiKey::where('value', $request->header('x-api-key'))->first();
            if($apiKeyModel) {
                return $next($request);
            } else {
                return $this->sendRes(__('x-api-key value not valid'), false, [], [], 400);
            }
        } else {
            return $this->sendRes(__('api key unvalid please add in header: (x-api-key) and put the api key'), false, [], [], 400);
        }

    }
}
