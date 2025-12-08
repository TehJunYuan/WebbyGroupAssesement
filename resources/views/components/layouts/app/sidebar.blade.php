<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            @php
                $homeRoute = auth()->user()->hasRole('Admin') ? route('users-list.index') : (auth()->user()->hasRole('Seller') ? route('seller.books.index') : route('shop.index'));
            @endphp
            <a href="{{ $homeRoute }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group class="grid">
                    @canany(['assign permissions', 'assign roles', 'view permissions'])
                        <flux:navlist.item icon="lock-closed" :href="route('permissions.index')" :current="request()->routeIs('permissions.*')" wire:navigate>{{ __('User Permissions') }}</flux:navlist.item>
                    @endcanany
                    
                    @canany(['assign roles', 'view roles', 'create roles'])
                        <flux:navlist.item icon="user-group" :href="route('role-permissions.index')" :current="request()->routeIs('role-permissions.*')" wire:navigate>{{ __('Role Permissions') }}</flux:navlist.item>
                    @endcanany
                    
                    @canany(['view permissions', 'create permissions'])
                        <flux:navlist.item icon="key" :href="route('permissions-list.index')" :current="request()->routeIs('permissions-list.*')" wire:navigate>{{ __('Permissions List') }}</flux:navlist.item>
                    @endcanany
                    
                    @canany(['view sellers', 'approve sellers', 'reject sellers', 'manage seller accounts'])
                        <flux:navlist.item icon="shield-check" :href="route('seller-approvals.index')" :current="request()->routeIs('seller-approvals.*')" wire:navigate>{{ __('Seller Approvals') }}</flux:navlist.item>
                    @endcanany
                    
                    @canany(['view sellers', 'manage seller accounts'])
                        <flux:navlist.item icon="users" :href="route('sellers-list.index')" :current="request()->routeIs('sellers-list.*')" wire:navigate>{{ __('Sellers List') }}</flux:navlist.item>
                    @endcanany
                    
                    @canany(['view users', 'manage users'])
                        <flux:navlist.item icon="users" :href="route('users-list.index')" :current="request()->routeIs('users-list.*')" wire:navigate>{{ __('Users List') }}</flux:navlist.item>
                    @endcanany
                    
                    @canany(['view categories', 'create categories', 'edit categories', 'delete categories', 'manage categories'])
                        <flux:navlist.item icon="squares-2x2" :href="route('book-categories.index')" :current="request()->routeIs('book-categories.*')" wire:navigate>{{ __('Book Categories') }}</flux:navlist.item>
                    @endcanany

                    @canany(['view genders', 'create genders', 'edit genders', 'delete genders', 'manage genders'])
                        <flux:navlist.item icon="user-group" :href="route('genders.index')" :current="request()->routeIs('genders.*')" wire:navigate>{{ __('Genders') }}</flux:navlist.item>
                    @endcanany

                    @if(auth()->check() && !auth()->user()->hasRole('Seller'))
                        <flux:navlist.item icon="shopping-bag" :href="route('shop.index')" :current="request()->routeIs('shop.*')" wire:navigate>{{ __('Book Shop') }}</flux:navlist.item>
                        <flux:navlist.item icon="shopping-bag" :href="route('user.orders.index')" :current="request()->routeIs('user.orders.*')" wire:navigate>{{ __('My Orders') }}</flux:navlist.item>
                    @endif
                </flux:navlist.group>

                @if(auth()->check() && auth()->user()->isApprovedSeller())
                    <flux:navlist.group :heading="__('Seller Panel')" class="grid">
                        @canany(['access seller panel'])
                            <flux:navlist.item icon="layout-grid" :href="route('seller.panel')" :current="request()->routeIs('seller.panel')" wire:navigate>{{ __('Seller Dashboard') }}</flux:navlist.item>
                        @endcanany
                        @canany(['view own books', 'create books', 'edit own books', 'delete own books'])
                            <flux:navlist.item icon="book-open-text" :href="route('seller.books.index')" :current="request()->routeIs('seller.books.*')" wire:navigate>{{ __('My Books') }}</flux:navlist.item>
                        @endcanany
                        @canany(['view own orders', 'update order status'])
                            <flux:navlist.item icon="shopping-cart" :href="route('seller.orders.index')" :current="request()->routeIs('seller.orders.*')" wire:navigate>{{ __('Orders') }}</flux:navlist.item>
                        @endcanany
                    </flux:navlist.group>
                @endif
            </flux:navlist>

            <flux:spacer />

            @if(auth()->check() && auth()->user()->isApprovedSeller())
            <flux:navlist variant="outline">
                    <flux:navlist.item icon="user" :href="route('seller.information')" :current="request()->routeIs('seller.information')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            </flux:navlist>
            @elseif(auth()->check() && !auth()->user()->roles()->exists())
            <flux:navlist variant="outline">
                    <flux:navlist.item icon="user" :href="route('user.information')" :current="request()->routeIs('user.information')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            </flux:navlist>
            @endif

            @if(auth()->check() && !auth()->user()->roles()->exists())
                <div class="mb-2 hidden lg:block">
                    <a href="{{ route('cart.index') }}" wire:navigate class="relative inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-neutral-700 dark:text-neutral-200 bg-white dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-700 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span>{{ __('Cart') }}</span>
                        <div 
                            x-data="{ 
                                count: {{ auth()->user()->cartItems()->where('IsActive', 1)->count() }},
                                updateCount() {
                                    fetch('{{ route('cart.count') }}')
                                        .then(response => response.json())
                                        .then(data => { this.count = data.count; });
                                }
                            }"
                            x-on:added-to-cart.window="updateCount()"
                            x-on:cart-updated.window="updateCount()"
                            x-on:cart-item-removed.window="updateCount()"
                            x-on:order-created.window="updateCount()"
                        >
                            <span x-show="count > 0" 
                                  x-text="count"
                                  class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                            </span>
                        </div>
                    </a>
                </div>
            @endif

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                    data-test="sidebar-menu-button"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                        <flux:menu.item :href="route('user.information')" icon="user" wire:navigate>{{ __('Personal Information') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                        <flux:menu.item :href="route('user.information')" icon="user" wire:navigate>{{ __('Personal Information') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        <x-cart-icon />

        @fluxScripts
    </body>
</html>
