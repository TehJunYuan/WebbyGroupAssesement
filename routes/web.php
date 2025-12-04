<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
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
