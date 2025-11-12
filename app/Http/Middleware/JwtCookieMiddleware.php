<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\Bidan;
use App\Models\Pasien;

class JwtCookieMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('token') ?: $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            $role = $payload->get('role');
            $userId = $payload->get('sub');

            if ($role === 'bidan') {
                $user = Bidan::find($userId);
            } elseif ($role === 'pasien') {
                $user = Pasien::find($userId);
            } else {
                return response()->json(['message' => 'Invalid token role'], 401);
            }

            if (!$user) {
                return response()->json(['message' => 'Invalid token user'], 401);
            }

            $request->merge([
                'auth_user' => $user,
                'auth_role' => $role,
            ]);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token error: ' . $e->getMessage()], 401);
        }

        return $next($request);
    }
}
