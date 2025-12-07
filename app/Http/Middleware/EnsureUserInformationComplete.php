<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserInformationComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $hasAnyRole = $user->roles()->exists();

        if (!$hasAnyRole) {
            $excludedRoutes = [
                'user.information',
                'logout',
                'profile.edit',
                'user-password.edit',
                'appearance.edit',
                'two-factor.show',
            ];
            
            $isExcludedRoute = false;
            foreach ($excludedRoutes as $route) {
                if ($request->routeIs($route)) {
                    $isExcludedRoute = true;
                    break;
                }
            }
            
            if (!$isExcludedRoute && str_starts_with($request->path(), 'settings')) {
                $isExcludedRoute = true;
            }
            
            if (!$user->hasUserInformation() && !$isExcludedRoute) {
                return redirect()->route('user.information')->with('error', 'Please complete your personal information to continue.');
            }
        }

        return $next($request);
    }
}
