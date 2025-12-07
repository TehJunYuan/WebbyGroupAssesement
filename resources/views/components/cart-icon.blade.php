@if(auth()->check() && !auth()->user()->roles()->exists())
    @php
        $cartCount = auth()->user()->cartItems()->where('IsActive', 1)->sum('quantity');
    @endphp
    <a href="{{ route('cart.index') }}" wire:navigate class="fixed top-4 right-4 z-50 inline-flex items-center justify-center p-3 text-sm font-medium text-neutral-700 dark:text-neutral-200 bg-white dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-700 rounded-lg shadow-lg hover:bg-neutral-50 dark:hover:bg-neutral-700 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        @if($cartCount > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full min-w-[20px]">{{ $cartCount }}</span>
        @endif
    </a>
@endif

