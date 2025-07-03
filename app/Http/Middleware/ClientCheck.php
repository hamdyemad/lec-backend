<?php

namespace App\Http\Middleware;

use App\Traits\Res;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientCheck
{
    use Res;
    protected $user_type_id = 3;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $auth = auth()->user();
        if($auth) {
            if($auth->user_type_id == 3) {
                return $next($request);
            } else {
                $message = translate('you are not client please check your permession');
                return $this->sendRes($message, false, [], [], 401);
            }
        } else {
            $message = translate('you are not authorizd');
            return $this->sendRes($message, false, [], [], 401);
        }
    }
}
