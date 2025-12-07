<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Book;
use Illuminate\Support\Facades\DB;

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
        $sellerId = auth()->id();

        $query = Order::query()
            ->whereHas('items.book', function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->with(['user', 'items.book'])
            ->orderBy('InsertAt', 'desc');

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('id', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function($userQuery) {
                      $userQuery->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if (!empty($this->paymentStatusFilter)) {
            $query->where('payment_status', $this->paymentStatusFilter);
        }

        return $query->paginate(15);
    }

    public function getSellerOrderTotal($order)
    {
        $sellerId = auth()->id();
        return $order->items()
            ->whereHas('book', function($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->sum('subtotal');
    }

    public function getSellerOrderItems($order)
    {
        $sellerId = auth()->id();
        return $order->items()
            ->whereHas('book', function($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->with('book')
            ->get();
    }

    public function viewOrderDetails($orderId): void
    {
        $this->selectedOrder = Order::with(['user', 'items.book'])
            ->where('id', $orderId)
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

    public function updatePaymentStatus($orderId, $status): void
    {
        $order = Order::findOrFail($orderId);

        $sellerId = auth()->id();
        $hasSellerItems = $order->items()
            ->whereHas('book', function($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->exists();

        if (!$hasSellerItems) {
            $this->dispatch('order-not-found');
            return;
        }

        $validStatuses = ['pending', 'paid', 'failed', 'refunded'];
        if (!in_array($status, $validStatuses)) {
            $this->dispatch('invalid-status');
            return;
        }

        $order->payment_status = $status;
        $order->save();

        $this->dispatch('status-updated');
        
        if ($this->selectedOrder && $this->selectedOrder->id == $orderId) {
            $this->selectedOrder->refresh();
        }
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div>
            <flux:heading>{{ __('Orders') }}</flux:heading>
            <flux:subheading>{{ __('Manage and update order status') }}</flux:subheading>
        </div>

        <x-action-message on="status-updated" class="text-green-600 dark:text-green-400">
            {{ __('Order status updated successfully.') }}
        </x-action-message>
        <x-action-message on="order-not-found" class="text-red-600 dark:text-red-400">
            {{ __('Order not found or you do not have permission to access this order.') }}
        </x-action-message>
        <x-action-message on="invalid-status" class="text-red-600 dark:text-red-400">
            {{ __('Invalid order status.') }}
        </x-action-message>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    :label="__('Search Orders')" 
                    type="text"
                    placeholder="Search by order ID, customer name or email..."
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
                                {{ __('Customer') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                {{ __('Order Date') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                {{ __('Amount') }}
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
                            <tr wire:key="order-{{ $order->id }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                        #{{ $order->id }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900 dark:text-neutral-100">
                                        {{ $order->user->name ?? __('Unknown') }}
                                    </div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ $order->user->email ?? '' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900 dark:text-neutral-100">
                                        {{ $order->InsertAt ? $order->InsertAt->format('Y-m-d H:i') : '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                        ${{ number_format($this->getSellerOrderTotal($order), 2) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            'refunded' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                        ];
                                        $statusColor = $statusColors[$order->payment_status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                        {{ ucfirst($order->payment_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button 
                                            variant="ghost" 
                                            size="sm"
                                            wire:click="viewOrderDetails({{ $order->id }})"
                                        >
                                            {{ __('View') }}
                                        </flux:button>
                                        @if($order->payment_status !== 'paid')
                                            <flux:button 
                                                variant="primary" 
                                                size="sm"
                                                wire:click="updatePaymentStatus({{ $order->id }}, 'paid')"
                                                wire:confirm="Mark this order as paid?"
                                            >
                                                {{ __('Mark as Paid') }}
                                            </flux:button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ __('No orders found.') }}
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-700">
                {{ $this->loadOrders()->links() }}
            </div>
        </div>
    </div>

    @if($showOrderDetails && $selectedOrder)
        <flux:modal name="order-details" wire:model="showOrderDetails">
            <form wire:submit.prevent="closeOrderDetails" class="space-y-6">
                <div class="space-y-4">
                    <div>
                        <flux:heading size="lg">{{ __('Order Details') }} #{{ $selectedOrder->id }}</flux:heading>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:heading size="sm" class="mb-2">{{ __('Customer Information') }}</flux:heading>
                            <div class="space-y-1 text-sm">
                                <div>
                                    <span class="font-medium">{{ __('Name:') }}</span>
                                    <span class="text-neutral-600 dark:text-neutral-400">{{ $selectedOrder->user->name ?? __('Unknown') }}</span>
                                </div>
                                <div>
                                    <span class="font-medium">{{ __('Email:') }}</span>
                                    <span class="text-neutral-600 dark:text-neutral-400">{{ $selectedOrder->user->email ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="font-medium">{{ __('Shipping Address:') }}</span>
                                    <div class="text-neutral-600 dark:text-neutral-400 mt-1">
                                        {{ $selectedOrder->shipping_address ?? __('Not provided') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <flux:heading size="sm" class="mb-2">{{ __('Order Information') }}</flux:heading>
                            <div class="space-y-1 text-sm">
                                <div>
                                    <span class="font-medium">{{ __('Order Date:') }}</span>
                                    <span class="text-neutral-600 dark:text-neutral-400">{{ $selectedOrder->InsertAt ? $selectedOrder->InsertAt->format('Y-m-d H:i:s') : '-' }}</span>
                                </div>
                                <div>
                                    <span class="font-medium">{{ __('Payment Status:') }}</span>
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            'refunded' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                        ];
                                        $statusColor = $statusColors[$selectedOrder->payment_status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }} ml-2">
                                        {{ ucfirst($selectedOrder->payment_status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <flux:heading size="sm" class="mb-2">{{ __('Order Items') }}</flux:heading>
                        <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
                            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                <thead class="bg-neutral-50 dark:bg-neutral-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">{{ __('Book') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">{{ __('Quantity') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">{{ __('Unit Price') }}</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">{{ __('Subtotal') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-neutral-900 divide-y divide-neutral-200 dark:divide-neutral-700">
                                    @foreach($this->getSellerOrderItems($selectedOrder) as $item)
                                        <tr>
                                            <td class="px-4 py-3">
                                                <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                    {{ $item->book->title ?? __('Unknown Book') }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-neutral-900 dark:text-neutral-100">
                                                {{ $item->quantity }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-neutral-900 dark:text-neutral-100">
                                                ${{ number_format($item->unit_price, 2) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium text-neutral-900 dark:text-neutral-100 text-right">
                                                ${{ number_format($item->subtotal, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-neutral-50 dark:bg-neutral-800">
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                            {{ __('Total:') }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm font-bold text-neutral-900 dark:text-neutral-100">
                                            ${{ number_format($this->getSellerOrderTotal($selectedOrder), 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    @if($selectedOrder->payment_status !== 'paid')
                        <div class="flex justify-end gap-2 pt-4 border-t border-neutral-200 dark:border-neutral-700">
                            <flux:button 
                                variant="primary" 
                                wire:click="updatePaymentStatus({{ $selectedOrder->id }}, 'paid')"
                                wire:confirm="Mark this order as paid?"
                            >
                                {{ __('Mark as Paid') }}
                            </flux:button>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="closeOrderDetails">
                        {{ __('Close') }}
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>

