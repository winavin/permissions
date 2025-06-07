<?php

namespace Winavin\Permissions\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HasAnyRole
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle( Request $request, Closure $next, string|array $roles, $team = null ) : Response
    {
        if( !Auth::user()?->hasAnyRole( $roles, $team ) ) {
            abort( 403, 'You do not have the required role(s) to access this resource.' );
        }
        return $next( $request );
    }
}
