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
     * @param  string  $roles  Pipe-separated list of roles (e.g. 'seller|admin')
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'status' => 'fail',
                'status_code' => 401,
                'message' => 'Unauthenticated. Please login to continue.'
            ], 401);
        }

        // Split the roles string by | to handle multiple roles
        $roleArray = explode('|', $roles);

        // Check if the user has any of the required roles
        $hasRole = false;

        foreach ($roleArray as $role) {
            if ($request->user()->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            Log::warning('Role authorization failed', [
                'user_id' => $request->user()->id,
                'required_roles' => $roleArray,
                'user_roles' => $request->user()->roles()->pluck('slug')->toArray(),
                'endpoint' => $request->path()
            ]);

            return response()->json([
                'status' => 'fail',
                'status_code' => 403,
                'message' => 'Forbidden: You do not have the required role to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}
