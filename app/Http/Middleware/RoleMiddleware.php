<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        // Check if user has the required role
        if ($role === 'admin' && !$user->isAdmin()) {
            abort(403, 'Access denied. Admin role required.');
        }

        if ($role === 'accountant' && !$user->isAccountant() && !$user->isAdmin()) {
            abort(403, 'Access denied. Accountant or Admin role required.');
        }

        if ($role === 'client' && !$user->isClient() && !$user->isAccountant() && !$user->isAdmin()) {
            abort(403, 'Access denied. Client, Accountant, or Admin role required.');
        }

        return $next($request);
    }
}
