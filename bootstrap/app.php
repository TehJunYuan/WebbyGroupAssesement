<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'seller.approved' => \App\Http\Middleware\EnsureSellerApproved::class,
            'seller.role' => \App\Http\Middleware\EnsureSellerRole::class,
            'seller.information.complete' => \App\Http\Middleware\EnsureSellerInformationComplete::class,
            'user.information.complete' => \App\Http\Middleware\EnsureUserInformationComplete::class,
            'prevent.seller.from.shop' => \App\Http\Middleware\PreventSellerFromShop::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'any.permission' => \App\Http\Middleware\CheckAnyPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
