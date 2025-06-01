<?php

namespace App\Http\Middleware;

use App\Traits\Res;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;

class CustomAuthenticate extends BaseAuthenticate
{
    use Res;
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return $this->sendRes(translate('Unauthenticated'), false, [], [], 401);
        }
    }

    public function handle($request, Closure $next, ...$guards)
    {
        try {
            $this->authenticate($request, $guards);
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return $this->sendRes(translate('Unauthenticated'), false, [], [], 401);
        }
        return $next($request);
    }
}
