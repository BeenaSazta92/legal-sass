<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnforceTenantIsolation
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            $request->attributes->set('tenant_firm_id', $request->user()->firm_id);
            $request->attributes->set('user_role', $request->user()->role);
        }

        return $next($request);
    }
}
