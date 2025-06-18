<?php

namespace Winavin\Permissions\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HasAnyPermission
{
    public function handle( Request $request, Closure $next, string|array $permissions, $team = null ) : Response
    {
        if( !Auth::user()?->hasAnyPermission( $permissions, $team ) ) {
            abort( 403, 'You do not have the required permission(s) to access this resource.' );
        }
        return $next( $request );
    }
}
