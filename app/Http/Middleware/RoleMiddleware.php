<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Please login to continue.',
            ], 401);
        }

        if (!$request->user()->hasRole($role)) {
            Log::warning('Role authorization failed', [
                'user_id' => $request->user()->id,
                'required_role' => $role,
                'user_roles' => $request->user()->roles()->pluck('slug')->toArray(),
                'endpoint' => $request->path()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Forbidden: You do not have permission to access this resource.',
            ], 403);
        }

        return $next($request);
    }
}
