<?php

namespace App\Http\Middleware;

use App\Models\ApiKey as ModelsApiKey;
use App\Models\UserType;
use App\Traits\Res;
use Closure;
use Illuminate\Http\Request;

class AuthenticationType
{
    use Res;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $type)
    {
        $auth = auth()->user();

        if($auth) {
            if($auth->user_type_id == 1) {
                return $next($request); // Super Admin can access all routes
            }
            $user_type = UserType::where('title', $type)->first();
            if($user_type) {
                if($auth && $auth->user_type_id == $user_type->id) {
                    return $next($request);
                } else {
                    return $this->sendRes(translate('you have no permession to use this request'), false, [], [], 401);
                }
            } else {
                return $this->sendRes(translate('invalid user type please check your permession'), false, [], [], 400);
            }
        } else {
            return $this->sendRes(translate('you are not authenticated'), false, [], [], 401);
        }

    }
}
