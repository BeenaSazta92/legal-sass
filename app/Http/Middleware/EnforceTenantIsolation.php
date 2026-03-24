<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnforceTenantIsolation
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Authentication required'], 401);
        }
        if (!$user->firm_id) {
            return response()->json(['message' => 'No firm assigned, you are not authorized to perform this action'], 403);
        }
        // Block suspended firms
        if ($user->firm && $user->firm->status === 'suspended') {
            return response()->json(['message' => 'Firm is suspended'], 403);
        }
        //optional 
        $request->attributes->set('tenant_id', $user->firm_id);
        $request->attributes->set('user_role', $request->user()->role);
        return $next($request);
    }
}
