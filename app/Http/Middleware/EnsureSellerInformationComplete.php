<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSellerInformationComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasRole('Seller') && $user->isApprovedSeller()) {
            if (!$user->hasSellerInformation() && !$request->routeIs('seller.information')) {
                return redirect()->route('seller.information')->with('error', 'Please complete your seller information before accessing seller features.');
            }
        }

        return $next($request);
    }
}
