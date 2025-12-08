<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Order;
use App\Models\OrderItem;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $paymentStatusFilter = '';
    public $selectedOrder = null;
    public $showOrderDetails = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPaymentStatusFilter(): void
    {
        $this->resetPage();
    }

    public function loadOrders()
    {
        $query = Order::query()
            ->where('user_id', auth()->id())
            ->with(['items.book.category', 'items.book.seller'])
            ->orderBy('InsertAt', 'desc');

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('id', 'like', '%' . $this->search . '%')
                  ->orWhereHas('items.book', function($bookQuery) {
                      $bookQuery->where('title', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if (!empty($this->paymentStatusFilter)) {
            $query->where('payment_status', $this->paymentStatusFilter);
        }

        return $query->paginate(15);
    }

    public function viewOrderDetails($orderId): void
    {
        $this->selectedOrder = Order::with(['items.book.category', 'items.book.seller'])
            ->where('id', $orderId)
            ->where('user_id', auth()->id())
            ->first();

        if ($this->selectedOrder) {
            $this->showOrderDetails = true;
        }
    }

    public function closeOrderDetails(): void
    {
        $this->showOrderDetails = false;
        $this->selectedOrder = null;
    }

    public function getPaymentStatusBadge($status)
    {
        return match($status) {
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
            'paid' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            'refunded' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            default => 'bg-neutral-100 text-neutral-800 dark:bg-neutral-900/30 dark:text-neutral-300',
        };
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div>
            <flux:heading>{{ __('My Orders') }}</flux:heading>
            <flux:subheading>{{ __('View your order history and details') }}</flux:subheading>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    :label="__('Search Orders')" 
                    type="text"
                    placeholder="Search by order ID or book title..."
                    icon="magnifying-glass"
                />
                <flux:select wire:model.live="paymentStatusFilter" :label="__('Payment Status')">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="paid">{{ __('Paid') }}</option>
                    <option value="failed">{{ __('Failed') }}</option>
                    <option value="refunded">{{ __('Refunded') }}</option>
                </flux:select>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-neutral-50 dark:bg-neutral-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                {{ __('Order ID') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                {{ __('Order Date') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                {{ __('Items') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                {{ __('Total Amount') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                {{ __('Payment Status') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-neutral-900 divide-y divide-neutral-200 dark:divide-neutral-700">
                        @forelse ($this->loadOrders() as $order)
                            <tr wire:key="order-{{ $order->id }}" class="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                        #{{ $order->id }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900 dark:text-neutral-100">
                                        {{ $order->InsertAt ? $order->InsertAt->format('M d, Y H:i') : '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-neutral-900 dark:text-neutral-100">
                                        {{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                        ${{ number_format($order->total_amount, 2) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->getPaymentStatusBadge($order->payment_status) }}">
                                        {{ ucfirst($order->payment_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <flux:button 
                                        variant="ghost" 
                                        size="sm"
                                        wire:click="viewOrderDetails({{ $order->id }})"
                                    >
                                        {{ __('View Details') }}
                                    </flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">
                                    {{ __('No orders found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-neutral-200 dark:border-neutral-700 px-4 py-3">
                {{ $this->loadOrders()->links() }}
            </div>
        </div>

        @if ($showOrderDetails && $selectedOrder)
            <flux:modal name="order-details" wire:model="showOrderDetails" class="max-w-4xl">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Order Details') }} #{{ $selectedOrder->id }}</flux:heading>
                    <flux:subheading>{{ __('Order placed on :date', ['date' => $selectedOrder->InsertAt->format('M d, Y H:i')]) }}</flux:subheading>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:heading size="sm" class="mb-2">{{ __('Payment Status') }}</flux:heading>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $this->getPaymentStatusBadge($selectedOrder->payment_status) }}">
                            {{ ucfirst($selectedOrder->payment_status) }}
                        </span>
                    </div>
                    <div>
                        <flux:heading size="sm" class="mb-2">{{ __('Shipping Address') }}</flux:heading>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $selectedOrder->shipping_address }}</p>
                    </div>
                </div>

                <div>
                    <flux:heading size="sm" class="mb-3">{{ __('Order Items') }}</flux:heading>
                    <div class="space-y-3">
                        @foreach ($selectedOrder->items as $item)
                            <div class="flex items-center gap-4 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                                <img 
                                    src="{{ $item->book->getCoverImageUrl('thumb') ?? 'https://via.placeholder.com/100x150?text=No+Cover' }}" 
                                    alt="{{ $item->book->title }}"
                                    class="w-16 h-24 object-cover rounded"
                                />
                                <div class="flex-1">
                                    <div class="font-medium text-neutral-900 dark:text-neutral-100">
                                        {{ $item->book->title }}
                                    </div>
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $item->book->category->name ?? __('No Category') }}
                                    </div>
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ __('Seller: :seller', ['seller' => $item->book->seller->name ?? __('Unknown')]) }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ __('Quantity: :qty', ['qty' => $item->quantity]) }}
                                    </div>
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ __('Unit Price: $:price', ['price' => number_format($item->unit_price, 2)]) }}
                                    </div>
                                    <div class="font-medium text-neutral-900 dark:text-neutral-100">
                                        {{ __('Subtotal: $:subtotal', ['subtotal' => number_format($item->subtotal, 2)]) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-neutral-200 dark:border-neutral-700 pt-4">
                    <div class="flex justify-between items-center">
                        <flux:heading size="lg">{{ __('Total Amount') }}</flux:heading>
                        <div class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">
                            ${{ number_format($selectedOrder->total_amount, 2) }}
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" wire:click="closeOrderDetails">
                            {{ __('Close') }}
                        </flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
        @endif
    </div>
</div>

