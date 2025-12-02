<?php

namespace Sti\StiAuth\Middleware;

use Closure;
use Sti\StiAuth\Services\AuthClient;

class VerifyApiToken
{
    public function __construct(
        protected AuthClient $auth
    ) {}

    public function handle($request, Closure $next)
    {
        $token = session('api_token');

        if (!$token) {
            return redirect()->route('login');
        }

        $user = $this->auth->validateToken($token);

        if (!$user) {
            return response()->json(['error' => 'Invalid token'], 403);
        }

        // optional: User ins Request-Objekt setzen
        $request->merge(['auth_user' => $user]);

        return $next($request);
    }
}
