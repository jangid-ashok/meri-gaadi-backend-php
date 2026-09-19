<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('admin.login');
        }

        if (! $user->hasAnyPermission($permissions)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Forbidden.'], 403)
                : redirect()->route('admin.forbidden');
        }

        return $next($request);
    }
}