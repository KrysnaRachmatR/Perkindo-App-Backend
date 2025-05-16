<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotulenMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || get_class($user) !== \App\Models\Notulen::class) {
            return response()->json(['message' => 'Unauthorized. Notulen access only.'], 403);
        }

        return $next($request);
    }
}
