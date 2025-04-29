<?php

namespace Winavin\Permissions\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HasAllPermisions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string|array $permissions, $team = null): Response
    {
        if(! Auth::user()?->hasAllPermissions($permissions, $team)) {
            abort(403, 'You do not have the required permission(s) to access this resource.');
        }
        return $next($request);
    }
}
