<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSellerApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Allow access if user is not a seller
        if (!$user || !$user->hasRole('Seller')) {
            return $next($request);
        }

        // Check if seller is approved
        if (!$user->isApprovedSeller()) {
            if ($user->isPendingSeller()) {
                return redirect()->route('dashboard')->with('error', 'Your seller account is pending approval. Please wait for admin approval.');
            } elseif ($user->isRejectedSeller()) {
                return redirect()->route('dashboard')->with('error', 'Your seller account has been rejected. Please contact admin for more information.');
            } else {
                return redirect()->route('dashboard')->with('error', 'Your seller account requires approval. Please contact admin.');
            }
        }

        return $next($request);
    }
}
