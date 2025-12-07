<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventSellerFromShop
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasRole('Seller')) {
            return redirect()->route('seller.books.index')->with('error', 'Sellers cannot access the book shop.');
        }

        return $next($request);
    }
}
