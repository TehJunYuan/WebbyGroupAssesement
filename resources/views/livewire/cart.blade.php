<?php

use Livewire\Volt\Component;
use App\Models\Cart;
use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\UserInformation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new class extends Component {
    public $selectedItems = [];

    public function loadCartItems()
    {
        return Cart::with('book.category')
            ->where('user_id', auth()->id())
            ->where('IsActive', 1)
            ->orderBy('InsertAt', 'desc')
            ->get();
    }

    public function getCartTotal()
    {
        return $this->loadCartItems()->sum(function ($item) {
            return $item->quantity * $item->book->price;
        });
    }

    public function getTotalItems()
    {
        return $this->loadCartItems()->count();
    }

    public function updateQuantity($cartId, $quantity): void
    {
        if ($quantity < 1) {
            return;
        }

        $cartItem = Cart::where('id', $cartId)
            ->where('user_id', auth()->id())
            ->where('IsActive', 1)
            ->first();

        if (!$cartItem) {
            $this->dispatch('cart-item-not-found');
            return;
        }

        $book = $cartItem->book;
        
        if ($quantity > $book->stock_quantity) {
            $this->dispatch('insufficient-stock', quantity: $book->stock_quantity);
            return;
        }

        $cartItem->quantity = $quantity;
        $cartItem->UpdateBy = now();
        $cartItem->UpdateUserId = auth()->id();
        $cartItem->save();

        $this->dispatch('cart-updated');
    }

    public function removeItem($cartId): void
    {
        $cartItem = Cart::where('id', $cartId)
            ->where('user_id', auth()->id())
            ->where('IsActive', 1)
            ->first();

        if ($cartItem) {
            $cartItem->softDelete();
            $this->dispatch('cart-item-removed');
        }
    }

    public function getCoverImageUrl($book)
    {
        return $book->getCoverImageUrl('thumb') ?? 'https://via.placeholder.com/300x450?text=No+Cover';
    }

    public function getSelectedTotal()
    {
        $total = 0;
        $items = $this->loadCartItems();
        
        foreach ($items as $item) {
            if (in_array($item->id, $this->selectedItems)) {
                $total += $item->quantity * $item->book->price;
            }
        }
        
        return $total;
    }

    public function getSelectedItemsCount()
    {
        $count = 0;
        $items = $this->loadCartItems();
        
        foreach ($items as $item) {
            if (in_array($item->id, $this->selectedItems)) {
                $count++;
            }
        }
        
        return $count;
    }

    public function toggleSelectAll()
    {
        $items = $this->loadCartItems();
        
        if (count($this->selectedItems) === $items->count()) {
            $this->selectedItems = [];
        } else {
            $this->selectedItems = $items->pluck('id')->toArray();
        }
    }

    public function proceedToCheckout(): void
    {
        if (empty($this->selectedItems)) {
            $this->dispatch('no-items-selected');
            return;
        }

        $user = auth()->user();
        $userInfo = UserInformation::where('user_id', $user->id)->first();

        if (!$userInfo || empty($userInfo->address)) {
            $this->dispatch('address-required');
            return;
        }

        $cartItems = Cart::whereIn('id', $this->selectedItems)
            ->where('user_id', $user->id)
            ->where('IsActive', 1)
            ->with('book')
            ->get();

        if ($cartItems->isEmpty()) {
            $this->dispatch('cart-items-not-found');
            return;
        }

        try {
            DB::transaction(function () use ($cartItems, $user, $userInfo) {
                $totalAmount = 0;

                foreach ($cartItems as $cartItem) {
                    $book = $cartItem->book;

                    if ($book->stock_quantity < $cartItem->quantity) {
                        throw new \Exception(__('Insufficient stock for :title', ['title' => $book->title]));
                    }

                    $subtotal = $cartItem->quantity * $book->price;
                    $totalAmount += $subtotal;
                }

                $order = Order::create([
                    'user_id' => $user->id,
                    'total_amount' => $totalAmount,
                    'payment_status' => 'pending',
                    'shipping_address' => $userInfo->address,
                    'InsertAt' => now(),
                    'InsertUserId' => $user->id,
                    'IsActive' => 1,
                ]);

                foreach ($cartItems as $cartItem) {
                    $book = $cartItem->book;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'book_id' => $book->id,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $book->price,
                        'subtotal' => $cartItem->quantity * $book->price,
                        'InsertAt' => now(),
                        'InsertUserId' => $user->id,
                        'IsActive' => 1,
                    ]);

                    $book->stock_quantity -= $cartItem->quantity;
                    $book->save();

                    $cartItem->softDelete();
                }

                $this->selectedItems = [];
                $this->dispatch('order-created', orderId: $order->id);
            });
        } catch (\Exception $e) {
            $this->dispatch('order-failed', message: $e->getMessage());
        }
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div>
            <flux:heading>{{ __('Shopping Cart') }}</flux:heading>
            <flux:subheading>{{ __('Review your cart items before checkout') }}</flux:subheading>
        </div>

        <x-action-message on="cart-updated" class="text-green-600 dark:text-green-400">
            {{ __('Cart updated successfully.') }}
        </x-action-message>
        <x-action-message on="cart-item-removed" class="text-green-600 dark:text-green-400">
            {{ __('Item removed from cart.') }}
        </x-action-message>
        <x-action-message on="insufficient-stock" class="text-red-600 dark:text-red-400">
            {{ __('Insufficient stock available.') }}
        </x-action-message>
        <x-action-message on="no-items-selected" class="text-yellow-600 dark:text-yellow-400">
            {{ __('Please select at least one item to proceed to checkout.') }}
        </x-action-message>
        <x-action-message on="address-required" class="text-red-600 dark:text-red-400">
            {{ __('Please complete your personal information with address before checkout.') }}
        </x-action-message>
        <x-action-message on="order-created" class="text-green-600 dark:text-green-400">
            {{ __('Order created successfully!') }}
        </x-action-message>
        <x-action-message on="order-failed" class="text-red-600 dark:text-red-400">
            {{ __('Failed to create order: :message', ['message' => $message ?? 'Unknown error']) }}
        </x-action-message>

        @if ($this->loadCartItems()->count() > 0)
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                        <thead class="bg-neutral-50 dark:bg-neutral-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    @php
                                        $allItems = $this->loadCartItems();
                                        $allSelected = count($selectedItems) === $allItems->count() && count($allItems) > 0;
                                    @endphp
                                    <label class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            wire:click="toggleSelectAll"
                                            @if($allSelected) checked @endif
                                            class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500 dark:border-neutral-700 dark:bg-neutral-800"
                                        />
                                    </label>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Book') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Price') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Quantity') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Subtotal') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-neutral-900 divide-y divide-neutral-200 dark:divide-neutral-700">
                            @foreach ($this->loadCartItems() as $item)
                                <tr wire:key="cart-item-{{ $item->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <flux:checkbox 
                                            wire:model.live="selectedItems"
                                            value="{{ $item->id }}"
                                        />
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-20 w-16">
                                                <img 
                                                    class="h-20 w-16 object-cover rounded"
                                                    src="{{ $this->getCoverImageUrl($item->book) }}" 
                                                    alt="{{ $item->book->title }}"
                                                >
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                    {{ $item->book->title }}
                                                </div>
                                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ $item->book->category->name ?? __('Uncategorized') }}
                                                </div>
                                                @if($item->book->stock_quantity < $item->quantity)
                                                    <div class="text-xs text-red-600 dark:text-red-400">
                                                        {{ __('Only :stock available', ['stock' => $item->book->stock_quantity]) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-neutral-900 dark:text-neutral-100">
                                            ${{ number_format($item->book->price, 2) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <flux:input 
                                                type="number" 
                                                min="1" 
                                                max="{{ $item->book->stock_quantity }}"
                                                :value="$item->quantity"
                                                wire:change="updateQuantity({{ $item->id }}, $event.target.value)"
                                                class="w-20"
                                            />
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                            ${{ number_format($item->quantity * $item->book->price, 2) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <flux:button 
                                            variant="danger" 
                                            size="sm"
                                            wire:click="removeItem({{ $item->id }})"
                                            wire:confirm="Are you sure you want to remove this item from your cart?"
                                        >
                                            {{ __('Remove') }}
                                        </flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-neutral-200 dark:border-neutral-700 px-6 py-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="text-sm text-neutral-600 dark:text-neutral-400">
                                {{ __('Selected Items: :count', ['count' => $this->getSelectedItemsCount()]) }}
                            </div>
                            <div class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                                {{ __('Total Line Items in Cart: :count', ['count' => $this->getTotalItems()]) }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-neutral-600 dark:text-neutral-400 mb-1">
                                {{ __('Selected Subtotal') }}
                            </div>
                            <div class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">
                                ${{ number_format($this->getSelectedTotal(), 2) }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <flux:button 
                            variant="primary" 
                            class="px-6 py-3 text-base"
                            wire:click="proceedToCheckout"
                            :disabled="empty($selectedItems)"
                        >
                            {{ __('Proceed to Checkout') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ __('Your cart is empty') }}</h3>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ __('Start shopping to add items to your cart') }}</p>
                <div class="mt-6">
                    <flux:button variant="primary" :href="route('shop.index')" wire:navigate>
                        {{ __('Continue Shopping') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </div>
</div>

