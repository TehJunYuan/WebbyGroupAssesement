<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = auth()->user();
        
        if ($user->hasRole('Admin')) {
            return redirect()->route('users-list.index');
        }
        
        if ($user->hasRole('Seller')) {
            return redirect()->route('seller.books.index');
        }
        
        return redirect()->route('shop.index');
    }
}

