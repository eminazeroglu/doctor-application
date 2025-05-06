<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class OptionalAuthSanctum
{
    public function handle(Request $request, Closure $next)
    {
        if (EnsureFrontendRequestsAreStateful::fromFrontend($request)) {
            $request->setUserResolver(function () {
                return app('auth')->guard('sanctum')->user();
            });
        }

        if ($request->bearerToken()) {
            try {
                $user = app('auth')->guard('sanctum')->user();
                if ($user) {
                    auth()->setUser($user);
                }
            } catch (AuthenticationException $e) {
                // Xəta olsa belə davam edirik
            }
        }

        return $next($request);
    }
}
