<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Menu;
use Illuminate\Support\Facades\Log;

class CheckMenuAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if (!$user) {
            abort(403, 'Unauthorized. Please login.');
        }

        $currentRoute = $request->route()->getName();
        
        // Allow super admin to access all routes
        if ($user->hasRole('Super Admin ( Developers)')) {
            return $next($request);
        }

        // Find menu for current route
        $menu = Menu::where('url', $currentRoute)->first();

        if (!$menu) {
            Log::warning("Menu not found for route: {$currentRoute}");
            abort(403, 'Unauthorized access. Route not assigned to any menu.');
        }

        // Check if user has access through roles or permissions
        $hasAccess = $menu->roles()
            ->whereIn('roles.id', $user->roles->pluck('id')->toArray())
            ->exists()
            ||
            $menu->permissions()
            ->whereIn('permissions.id', $user->getAllPermissions()->pluck('id')->toArray())
            ->exists();

        if (!$hasAccess) {
            Log::warning("User {$user->id} attempted to access restricted route: {$currentRoute}");
            abort(403, 'Unauthorized access. You do not have permission to access this menu.');
        }

        return $next($request);
    }
}