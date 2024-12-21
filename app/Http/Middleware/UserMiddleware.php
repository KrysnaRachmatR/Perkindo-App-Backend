<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserMiddleware
{
  public function handle(Request $request, Closure $next)
  {
    if (!$request->user() || !$request->user()->tokenCan('user:access')) {
      return response()->json(['message' => 'Unauthorized'], 403);
    }

    return $next($request);
  }
}
