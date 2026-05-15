<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    /**
     * Handle an incoming request.
     *
     * Usage in routes:
     *   ->middleware('role:ADMIN,FINANCE')
     *
     * Accepts one or more UserRole case names as comma-separated values.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        foreach ($roles as $roleValue) {
            $role = UserRole::tryFrom(strtoupper($roleValue));

            if ($role !== null && $user?->role === $role) {
                return $next($request);
            }
        }

        abort(403);
    }
}
