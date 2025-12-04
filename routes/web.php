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
});
