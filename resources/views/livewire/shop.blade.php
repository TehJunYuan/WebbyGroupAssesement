<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\Cart;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $selectedCategory = '';
    public $sortBy = 'latest';
    public $categories;
    public $quantities = [];

    public function mount(): void
    {
        $this->categories = BookCategory::all();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedCategory(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function loadBooks()
    {
        $query = Book::with(['category', 'seller']);

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->selectedCategory)) {
            $query->where('category_id', $this->selectedCategory);
        }

        switch ($this->sortBy) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'latest':
            default:
                $query->orderBy('InsertAt', 'desc');
                break;
        }

        return $query->paginate(12);
    }

    public function addToCart($bookId): void
    {
        if (!auth()->check()) {
            $this->dispatch('require-login');
            return;
        }

        $book = Book::findOrFail($bookId);
        $quantity = isset($this->quantities[$bookId]) && $this->quantities[$bookId] > 0 
            ? (int)$this->quantities[$bookId] 
            : 1;

        if ($quantity < 1) {
            $this->dispatch('invalid-quantity');
            return;
        }

        if ($book->stock_quantity < 1) {
            $this->dispatch('book-out-of-stock');
            return;
        }

        $cartItem = Cart::where('user_id', auth()->id())
            ->where('book_id', $bookId)
            ->where('IsActive', 1)
            ->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $quantity;
            
            if ($newQuantity > $book->stock_quantity) {
                $this->dispatch('insufficient-stock', quantity: $book->stock_quantity);
                return;
            }
            
            $cartItem->quantity = $newQuantity;
            $cartItem->UpdateBy = now();
            $cartItem->UpdateUserId = auth()->id();
            $cartItem->save();
        } else {
            if ($quantity > $book->stock_quantity) {
                $this->dispatch('insufficient-stock', quantity: $book->stock_quantity);
                return;
            }

            Cart::create([
                'user_id' => auth()->id(),
                'book_id' => $bookId,
                'quantity' => $quantity,
                'InsertAt' => now(),
                'InsertUserId' => auth()->id(),
                'IsActive' => 1,
            ]);
        }

        $this->quantities[$bookId] = 1;
        $this->dispatch('added-to-cart');
    }

    public function getCoverImageUrl($book)
    {
        return $book->getCoverImageUrl('preview') ?? 'https://via.placeholder.com/600x900?text=No+Cover';
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div>
        <flux:heading>{{ __('Book Shop') }}</flux:heading>
        <flux:subheading>{{ __('Browse and purchase books from our sellers') }}</flux:subheading>
    </div>

    <x-action-message on="added-to-cart" class="fixed top-4 right-4 z-50">
        <div class="rounded-lg bg-green-500 text-white px-4 py-3 shadow-lg">
            {{ __('Book added to cart successfully!') }}
        </div>
    </x-action-message>

    <x-action-message on="book-out-of-stock" class="fixed top-4 right-4 z-50">
        <div class="rounded-lg bg-red-500 text-white px-4 py-3 shadow-lg">
            {{ __('This book is out of stock.') }}
        </div>
    </x-action-message>

    <x-action-message on="insufficient-stock" class="fixed top-4 right-4 z-50">
        <div class="rounded-lg bg-yellow-500 text-white px-4 py-3 shadow-lg">
            {{ __('Insufficient stock available.') }}
        </div>
    </x-action-message>

    <x-action-message on="require-login" class="fixed top-4 right-4 z-50">
        <div class="rounded-lg bg-blue-500 text-white px-4 py-3 shadow-lg">
            {{ __('Please login to add items to cart.') }}
        </div>
    </x-action-message>

    <x-action-message on="invalid-quantity" class="fixed top-4 right-4 z-50">
        <div class="rounded-lg bg-red-500 text-white px-4 py-3 shadow-lg">
            {{ __('Please enter a valid quantity (minimum 1).') }}
        </div>
    </x-action-message>

    <div class="flex flex-col lg:flex-row gap-4">
        <div class="lg:w-64 flex-shrink-0">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-4">
                <flux:heading size="md" class="mb-4">{{ __('Filters') }}</flux:heading>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                            {{ __('Category') }}
                        </label>
                        <select 
                            wire:model.live="selectedCategory"
                            class="w-full rounded-lg border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 px-3 py-2 focus:border-primary-500 focus:ring-primary-500 focus:outline-none focus:ring-2"
                        >
                            <option value="">{{ __('All Categories') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                            {{ __('Sort By') }}
                        </label>
                        <select 
                            wire:model.live="sortBy"
                            class="w-full rounded-lg border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 px-3 py-2 focus:border-primary-500 focus:ring-primary-500 focus:outline-none focus:ring-2"
                        >
                            <option value="latest">{{ __('Latest') }}</option>
                            <option value="price_low">{{ __('Price: Low to High') }}</option>
                            <option value="price_high">{{ __('Price: High to Low') }}</option>
                            <option value="title">{{ __('Title: A-Z') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-4 mb-4">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    :label="__('Search Books')" 
                    type="text"
                    placeholder="Search by title or description..."
                    icon="magnifying-glass"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @forelse ($this->loadBooks() as $book)
                    <div wire:key="book-{{ $book->id }}" class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="relative">
                            <img 
                                src="{{ $this->getCoverImageUrl($book) }}" 
                                alt="{{ $book->title }}" 
                                class="w-full h-64 object-cover"
                            >
                            @if($book->stock_quantity <= 0)
                                <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-semibold">
                                    {{ __('Out of Stock') }}
                                </div>
                            @elseif($book->stock_quantity < 10)
                                <div class="absolute top-2 right-2 bg-yellow-500 text-white px-2 py-1 rounded text-xs font-semibold">
                                    {{ __('Low Stock') }}
                                </div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-lg text-neutral-900 dark:text-neutral-100 mb-1 line-clamp-2">
                                {{ $book->title }}
                            </h3>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">
                                {{ $book->category->name ?? __('Uncategorized') }}
                            </p>
                            @if($book->description)
                                <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-3 line-clamp-2">
                                    {{ Str::limit($book->description, 80) }}
                                </p>
                            @endif
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <span class="text-lg font-bold text-primary-600 dark:text-primary-400">
                                        ${{ number_format($book->price, 2) }}
                                    </span>
                                    @if($book->stock_quantity > 0)
                                        <span class="text-xs text-neutral-500 dark:text-neutral-400 block">
                                            {{ __('In Stock: :quantity', ['quantity' => $book->stock_quantity]) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400 mb-3">
                                <span>{{ __('Seller: :name', ['name' => $book->seller->name ?? 'Unknown']) }}</span>
                            </div>
                            @if($book->stock_quantity > 0)
                                <div class="flex items-center gap-2 mb-3">
                                    <label class="text-sm font-medium text-neutral-700 dark:text-neutral-300 whitespace-nowrap">
                                        {{ __('Quantity:') }}
                                    </label>
                                    <flux:input 
                                        type="number" 
                                        min="1" 
                                        max="{{ $book->stock_quantity }}"
                                        wire:model="quantities.{{ $book->id }}"
                                        class="w-20 text-center"
                                        :value="isset($quantities[$book->id]) ? $quantities[$book->id] : 1"
                                    />
                                </div>
                            @endif
                            <flux:button 
                                variant="primary" 
                                class="w-full"
                                wire:click="addToCart({{ $book->id }})"
                                :disabled="$book->stock_quantity <= 0"
                            >
                                @if($book->stock_quantity <= 0)
                                    {{ __('Out of Stock') }}
                                @else
                                    {{ __('Add to Cart') }}
                                @endif
                            </flux:button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <flux:text class="text-neutral-500 dark:text-neutral-400">
                            {{ __('No books found.') }}
                        </flux:text>
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $this->loadBooks()->links() }}
            </div>
        </div>
    </div>
</div>
