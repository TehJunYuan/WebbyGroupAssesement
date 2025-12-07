<?php

use Livewire\Volt\Component;
use App\Models\Book;
use App\Models\Scopes\ActiveScope;

new class extends Component {
    public $stats = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    public function loadStats(): void
    {
        $books = Book::bySeller(auth()->id())
            ->withoutGlobalScope(ActiveScope::class)
            ->get();

        $activeBooks = $books->where('IsActive', 1);
        $totalStock = $activeBooks->sum('stock_quantity');
        $totalBooks = $activeBooks->count();

        $this->stats = [
            'total_books' => $totalBooks,
            'active_books' => $activeBooks->count(),
            'inactive_books' => $books->where('IsActive', -1)->count(),
            'total_stock' => $totalStock,
            'low_stock' => $activeBooks->where('stock_quantity', '<', 10)->count(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div>
        <flux:heading>{{ __('Seller Dashboard') }}</flux:heading>
        <flux:subheading>{{ __('Welcome back! Here\'s an overview of your seller account.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="sm" class="text-neutral-600 dark:text-neutral-400">{{ __('Total Books') }}</flux:heading>
                    <div class="mt-2 text-3xl font-bold">{{ $stats['total_books'] ?? 0 }}</div>
                </div>
                <div class="rounded-full bg-primary-100 dark:bg-primary-900/30 p-3">
                    <flux:icon.book-open-text class="size-6 text-primary-600 dark:text-primary-400" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="sm" class="text-neutral-600 dark:text-neutral-400">{{ __('Active Books') }}</flux:heading>
                    <div class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['active_books'] ?? 0 }}</div>
                </div>
                <div class="rounded-full bg-green-100 dark:bg-green-900/30 p-3">
                    <flux:icon.check-circle class="size-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="sm" class="text-neutral-600 dark:text-neutral-400">{{ __('Total Stock') }}</flux:heading>
                    <div class="mt-2 text-3xl font-bold">{{ $stats['total_stock'] ?? 0 }}</div>
                </div>
                <div class="rounded-full bg-blue-100 dark:bg-blue-900/30 p-3">
                    <flux:icon.squares-2x2 class="size-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="sm" class="text-neutral-600 dark:text-neutral-400">{{ __('Low Stock') }}</flux:heading>
                    <div class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $stats['low_stock'] ?? 0 }}</div>
                </div>
                <div class="rounded-full bg-orange-100 dark:bg-orange-900/30 p-3">
                    <flux:icon.exclamation-triangle class="size-6 text-orange-600 dark:text-orange-400" />
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Quick Actions') }}</flux:heading>
            <div class="grid gap-3">
                @can('create books')
                    <flux:button variant="primary" :href="route('seller.books.index')" wire:navigate>
                        {{ __('Upload New Book') }}
                    </flux:button>
                @endcan
                @can('view own books')
                    <flux:button variant="ghost" :href="route('seller.books.index')" wire:navigate>
                        {{ __('Manage My Books') }}
                    </flux:button>
                @endcan
                @canany(['view own orders', 'update order status'])
                    <flux:button variant="ghost" :href="route('seller.orders.index')" wire:navigate>
                        {{ __('Manage Orders') }}
                    </flux:button>
                @endcanany
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Recent Books') }}</flux:heading>
            @php
                $recentBooks = Book::bySeller(auth()->id())
                    ->withoutGlobalScope(ActiveScope::class)
                    ->with(['category', 'media'])
                    ->orderBy('InsertAt', 'desc')
                    ->limit(5)
                    ->get();
            @endphp
            @if($recentBooks->count() > 0)
                <div class="space-y-3">
                    @foreach($recentBooks as $book)
                        <div class="flex items-center gap-3 rounded-lg border border-neutral-200 dark:border-neutral-700 p-3">
                            @php
                                $coverUrl = $book->getCoverImageUrl('thumb') ?? 'https://via.placeholder.com/60x80?text=No+Cover';
                            @endphp
                            <img src="{{ $coverUrl }}" alt="{{ $book->title }}" class="h-16 w-12 object-cover rounded">
                            <div class="flex-1">
                                <div class="font-medium">{{ $book->title }}</div>
                                <div class="text-sm text-neutral-600 dark:text-neutral-400">
                                    {{ $book->category->name ?? 'Uncategorized' }} · Stock: {{ $book->stock_quantity }}
                                </div>
                            </div>
                            <div class="text-sm font-semibold">${{ number_format($book->price, 2) }}</div>
                        </div>
                    @endforeach
                </div>
                @can('view own books')
                    <div class="mt-4">
                        <flux:button variant="ghost" :href="route('seller.books.index')" wire:navigate>
                            {{ __('View All Books') }}
                        </flux:button>
                    </div>
                @endcan
            @else
                <div class="py-8 text-center text-neutral-500 dark:text-neutral-400">
                    {{ __('No books yet. Start by uploading your first book!') }}
                </div>
                @can('create books')
                    <flux:button variant="primary" :href="route('seller.books.index')" wire:navigate>
                        {{ __('Upload Your First Book') }}
                    </flux:button>
                @endcan
            @endif
        </div>
    </div>
</div>

