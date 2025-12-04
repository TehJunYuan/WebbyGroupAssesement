<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSellerApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($request->routeIs('seller.*')) {
            if (!$user->hasRole('Seller')) {
                abort(403, 'Only sellers can access this page.');
            }

            if (!$user->isApprovedSeller()) {
                if ($user->isPendingSeller()) {
                    return redirect()->route('dashboard')->with('error', 'Your seller account is pending approval. Please wait for admin approval.');
                } elseif ($user->isRejectedSeller()) {
                    return redirect()->route('dashboard')->with('error', 'Your seller account has been rejected. Please contact admin for more information.');
                } else {
                    return redirect()->route('dashboard')->with('error', 'Your seller account requires approval. Please contact admin.');
                }
            }
        } else {
            if ($user->hasRole('Seller') && !$user->isApprovedSeller()) {
                if ($user->isPendingSeller()) {
                    return redirect()->route('dashboard')->with('error', 'Your seller account is pending approval. Please wait for admin approval.');
                } elseif ($user->isRejectedSeller()) {
                    return redirect()->route('dashboard')->with('error', 'Your seller account has been rejected. Please contact admin for more information.');
                } else {
                    return redirect()->route('dashboard')->with('error', 'Your seller account requires approval. Please contact admin.');
                }
            }
        }

        return $next($request);
    }
}
