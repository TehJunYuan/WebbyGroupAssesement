<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('home_route')) {
    function home_route(): string
    {
        $user = Auth::user();

        if (!$user) {
            return route('login');
        }

        if ($user->hasRole('Admin')) {
            return route('users-list.index');
        }

        if ($user->hasRole('Seller')) {
            return route('seller.books.index');
        }

        return route('shop.index');
    }
}

