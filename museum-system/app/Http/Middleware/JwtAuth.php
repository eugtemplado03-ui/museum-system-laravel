<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $header = $request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $token = substr($header, 7);
        $secret = config('app.jwt_secret', env('JWT_SECRET', 'change-this-secret-in-production'));

        try {
            $payload = (array) JWT::decode($token, new Key($secret, 'HS256'));
            $request->attributes->set('jwt_user', $payload);
            $request->merge(['user' => $payload]);

            if ($role && (!isset($payload['role']) || $payload['role'] !== $role)) {
                return response()->json(['error' => 'Admin access required'], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        return $next($request);
    }
}
