<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('dashboard', function () {
    $user = auth()->user();
    
    if ($user->hasRole('Admin')) {
        return redirect()->route('users-list.index');
    }
    
    if ($user->hasRole('Seller')) {
        return redirect()->route('seller.books.index');
    }
    
    return redirect()->route('shop.index');
})->middleware(['auth', 'verified', 'user.information.complete'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    Volt::route('permissions', 'permissions')
        ->middleware(['any.permission:assign permissions|assign roles|view permissions'])
        ->name('permissions.index');
    
    Volt::route('role-permissions', 'role-permissions')
        ->middleware(['any.permission:assign roles|view roles|create roles'])
        ->name('role-permissions.index');
    
    Volt::route('permissions-list', 'permissions-list')
        ->middleware(['any.permission:view permissions|create permissions'])
        ->name('permissions-list.index');
    
    Volt::route('seller-approvals', 'seller-approvals')
        ->middleware(['any.permission:view sellers|approve sellers|reject sellers|manage seller accounts'])
        ->name('seller-approvals.index');
    
    Volt::route('book-categories', 'book-categories')
        ->middleware(['any.permission:view categories|create categories|edit categories|delete categories|manage categories'])
        ->name('book-categories.index');
    
    Volt::route('genders', 'genders')
        ->middleware(['any.permission:view genders|create genders|edit genders|delete genders|manage genders'])
        ->name('genders.index');
    
    Volt::route('sellers-list', 'sellers-list')
        ->middleware(['any.permission:view sellers|manage seller accounts'])
        ->name('sellers-list.index');
    
    Volt::route('users-list', 'users-list')
        ->middleware(['any.permission:view users|manage users'])
        ->name('users-list.index');

    Volt::route('user/information', 'user.information')
        ->name('user.information');
    
    Volt::route('shop', 'shop')
        ->middleware(['user.information.complete', 'prevent.seller.from.shop'])
        ->name('shop.index');
    
    Volt::route('cart', 'cart')
        ->middleware(['user.information.complete', 'prevent.seller.from.shop'])
        ->name('cart.index');
});

Route::middleware(['auth', 'seller.role'])->group(function () {
    Volt::route('seller/information', 'seller.information')
        ->name('seller.information');
});

Route::middleware(['auth', 'seller.approved'])->group(function () {
    Volt::route('seller/panel', 'seller.panel')
        ->middleware(['any.permission:access seller panel'])
        ->name('seller.panel');
    
    Volt::route('seller/books', 'seller.books')
        ->middleware(['any.permission:view own books|create books|edit own books|delete own books'])
        ->name('seller.books.index');
    
    Volt::route('seller/orders', 'seller.orders')
        ->middleware(['any.permission:view own orders|update order status'])
        ->name('seller.orders.index');
    
    Volt::route('seller/analytics', 'seller.analytics')
        ->middleware(['any.permission:view sales analytics|view sales reports|view order statistics'])
        ->name('seller.analytics.index');
});
