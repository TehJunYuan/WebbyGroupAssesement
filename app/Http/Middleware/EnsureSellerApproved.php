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

            if ($request->routeIs('seller.information')) {
                return $next($request);
            }

            if (!$user->isApprovedSeller()) {
                if (!$user->hasSellerInformation()) {
                    return redirect()->route('seller.information')->with('error', 'Please complete your seller information first.');
                }
                
                if ($user->isPendingSeller()) {
                    return redirect()->route('seller.information')->with('error', 'Your seller account is pending approval. Please wait for admin approval.');
                } elseif ($user->isRejectedSeller()) {
                    return redirect()->route('seller.information')->with('error', 'Your seller account has been rejected. Please contact admin for more information.');
                } else {
                    return redirect()->route('seller.information')->with('error', 'Your seller account requires approval. Please contact admin.');
                }
            }

            if (!$user->hasSellerInformation()) {
                return redirect()->route('seller.information')->with('error', 'Please complete your seller information before accessing seller features.');
            }
        } else {
            if ($user->hasRole('Seller') && !$user->isApprovedSeller()) {
                if (!$user->hasSellerInformation()) {
                    return redirect()->route('seller.information')->with('error', 'Please complete your seller information first.');
                }
                
                if ($user->isPendingSeller()) {
                    return redirect()->route('seller.information')->with('error', 'Your seller account is pending approval. Please wait for admin approval.');
                } elseif ($user->isRejectedSeller()) {
                    return redirect()->route('seller.information')->with('error', 'Your seller account has been rejected. Please contact admin for more information.');
                } else {
                    return redirect()->route('seller.information')->with('error', 'Your seller account requires approval. Please contact admin.');
                }
            }
        }

        return $next($request);
    }
}
