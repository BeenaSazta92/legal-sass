<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantIsolation extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Store user's firm context in request
        if ($request->user()) {
           // $request->attributes->set('tenant_user', $user); 
            $request->attributes->set('tenant_firm_id', $request->user()->firm_id);
            $request->attributes->set('user_role', $request->user()->role);
        }
        return $next($request);
    }
}
