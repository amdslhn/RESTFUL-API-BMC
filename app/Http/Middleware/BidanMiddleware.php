<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Bidan;

class BidanMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->auth_user ?? null;

        if (!$user || !$user instanceof Bidan) {
            return response()->json(['message' => 'Only bidan can access this route'], 403);
        }

        return $next($request);
    }
}
