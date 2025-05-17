<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && !auth()->user()->status) {
            auth()->logout();
            return response()->json(['message' => 'Your account is inactive'], 403);
        }

        return $next($request);
    }
}
