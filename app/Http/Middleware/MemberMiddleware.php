<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MemberMiddleware
{
  public function handle(Request $request, Closure $next)
  {
    if (!$request->user()->tokenCan('user:access')) {
      return response()->json(['message' => 'Unauthorized - Member Only'], 403);
    }

    return $next($request);
  }
}
