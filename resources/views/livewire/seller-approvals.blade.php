<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use App\Models\Role;

new class extends Component {
    public $pendingSellers;
    public $approvedSellers;
    public $rejectedSellers;
    public $selectedSeller = null;
    public $search = '';
    public $filter = 'pending';
    public $rejectionReason = '';

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $sellerRole = Role::where('name', 'Seller')->first();
        
        if (!$sellerRole) {
            $this->pendingSellers = collect();
            $this->approvedSellers = collect();
            $this->rejectedSellers = collect();
            return;
        }

        $sellerIds = DB::table('model_has_roles')
            ->where('role_id', $sellerRole->id)
            ->where('model_type', User::class)
            ->pluck('model_id')
            ->toArray();

        if (empty($sellerIds)) {
            $this->pendingSellers = collect();
            $this->approvedSellers = collect();
            $this->rejectedSellers = collect();
            return;
        }

        $allSellers = User::whereIn('id', $sellerIds)->get();

        $this->approvedSellers = $allSellers->where('seller_approval_status', 1);

        $notApproved = $allSellers->where('seller_approval_status', 0);
        
        $this->pendingSellers = $notApproved->filter(function($seller) {
            return empty($seller->seller_rejection_reason);
        });

        $this->rejectedSellers = $notApproved->filter(function($seller) {
            return !empty($seller->seller_rejection_reason);
        });
    }

    public function updatedSearch(): void
    {
        $this->loadData();
        
        if (!empty($this->search)) {
            if ($this->filter === 'pending') {
                $this->pendingSellers = $this->pendingSellers->filter(function ($user) {
                    return stripos($user->name, $this->search) !== false || 
                           stripos($user->email, $this->search) !== false;
                });
            } elseif ($this->filter === 'approved') {
                $this->approvedSellers = $this->approvedSellers->filter(function ($user) {
                    return stripos($user->name, $this->search) !== false || 
                           stripos($user->email, $this->search) !== false;
                });
            } elseif ($this->filter === 'rejected') {
                $this->rejectedSellers = $this->rejectedSellers->filter(function ($user) {
                    return stripos($user->name, $this->search) !== false || 
                           stripos($user->email, $this->search) !== false;
                });
            }
        }
    }

    public function selectSeller($userId): void
    {
        $this->selectedSeller = User::find($userId);
        $this->rejectionReason = $this->selectedSeller->seller_rejection_reason ?? '';
    }

    public function approveSeller($userId): void
    {
        $seller = User::find($userId);
        if ($seller && $seller->hasRole('Seller')) {
            $seller->update([
                'seller_approval_status' => 1,
                'seller_approved_at' => now(),
                'seller_approved_by' => auth()->id(),
                'seller_rejection_reason' => null,
            ]);
            
            $this->loadData();
            $this->selectedSeller = null;
            $this->dispatch('seller-approved');
        }
    }

    public function rejectSeller($userId): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'max:1000'],
        ]);

        $seller = User::find($userId);
        if ($seller && $seller->hasRole('Seller')) {
            $seller->update([
                'seller_approval_status' => 0,
                'seller_approved_at' => null,
                'seller_approved_by' => auth()->id(),
                'seller_rejection_reason' => $this->rejectionReason,
            ]);
            
            $this->loadData();
            $this->selectedSeller = null;
            $this->rejectionReason = '';
            $this->dispatch('seller-rejected');
        }
    }

    public function getCurrentSellersProperty()
    {
        return match($this->filter) {
            'approved' => $this->approvedSellers,
            'rejected' => $this->rejectedSellers,
            default => $this->pendingSellers,
        };
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>{{ __('Seller Approval Management') }}</flux:heading>
            <flux:subheading>{{ __('Approve or reject seller registration requests') }}</flux:subheading>
        </div>
    </div>

    <x-action-message on="seller-approved" class="text-green-600 dark:text-green-400">
        {{ __('Seller approved successfully.') }}
    </x-action-message>
    <x-action-message on="seller-rejected" class="text-green-600 dark:text-green-400">
        {{ __('Seller rejected successfully.') }}
    </x-action-message>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
            <div class="border-b border-neutral-200 dark:border-neutral-700 p-4">
                <flux:heading size="lg">{{ __('Sellers') }}</flux:heading>
                <div class="mt-3 space-y-3">
                    <div class="flex gap-2">
                        <flux:button 
                            variant="{{ $filter === 'pending' ? 'primary' : 'ghost' }}" 
                            size="sm"
                            wire:click="$set('filter', 'pending')"
                        >
                            {{ __('Pending') }} ({{ count($pendingSellers) }})
                        </flux:button>
                        <flux:button 
                            variant="{{ $filter === 'approved' ? 'primary' : 'ghost' }}" 
                            size="sm"
                            wire:click="$set('filter', 'approved')"
                        >
                            {{ __('Approved') }} ({{ count($approvedSellers) }})
                        </flux:button>
                        <flux:button 
                            variant="{{ $filter === 'rejected' ? 'primary' : 'ghost' }}" 
                            size="sm"
                            wire:click="$set('filter', 'rejected')"
                        >
                            {{ __('Rejected') }} ({{ count($rejectedSellers) }})
                        </flux:button>
                    </div>
                    <flux:input 
                        wire:model.live.debounce.300ms="search" 
                        :label="__('Search Sellers')" 
                        type="text"
                        placeholder="Search by name or email..."
                    />
                </div>
            </div>
            <div class="max-h-[600px] overflow-y-auto p-4">
                <div class="space-y-2">
                    @forelse ($this->currentSellers as $seller)
                        <div 
                            wire:click="selectSeller({{ $seller->id }})"
                            class="cursor-pointer rounded-lg border p-3 transition-colors {{ $selectedSeller && $selectedSeller->id === $seller->id ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-700/50' }}"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-semibold">{{ $seller->name }}</div>
                                    <div class="text-sm text-neutral-600 dark:text-neutral-400">{{ $seller->email }}</div>
                                </div>
                                <div class="flex flex-col items-end">
                                    @if($seller->seller_approval_status == 1)
                                        <span class="rounded bg-green-100 px-2 py-0.5 text-xs text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                            {{ __('Approved') }}
                                        </span>
                                    @elseif(!empty($seller->seller_rejection_reason))
                                        <span class="rounded bg-red-100 px-2 py-0.5 text-xs text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                            {{ __('Rejected') }}
                                        </span>
                                    @else
                                        <span class="rounded bg-yellow-100 px-2 py-0.5 text-xs text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                            {{ __('Pending') }}
                                        </span>
                                    @endif
                                    @if($seller->seller_applied_at)
                                        <span class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                                            {{ $seller->seller_applied_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-neutral-500 dark:text-neutral-400">
                            {{ __('No sellers found.') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
            @if ($selectedSeller)
                <div class="border-b border-neutral-200 dark:border-neutral-700 p-4">
                    <flux:heading size="lg">{{ $selectedSeller->name }}</flux:heading>
                    <flux:subheading>{{ $selectedSeller->email }}</flux:subheading>
                </div>
                <div class="max-h-[600px] overflow-y-auto p-4 space-y-6">
                    <div>
                        <flux:heading size="md" class="mb-3">{{ __('Seller Information') }}</flux:heading>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-neutral-600 dark:text-neutral-400">{{ __('Name:') }}</span>
                                <span class="font-medium">{{ $selectedSeller->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-neutral-600 dark:text-neutral-400">{{ __('Email:') }}</span>
                                <span class="font-medium">{{ $selectedSeller->email }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-neutral-600 dark:text-neutral-400">{{ __('Status:') }}</span>
                                @if($selectedSeller->seller_approval_status == 1)
                                    <span class="rounded bg-green-100 px-2 py-0.5 text-xs text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                        {{ __('Approved') }}
                                    </span>
                                @elseif(!empty($selectedSeller->seller_rejection_reason))
                                    <span class="rounded bg-red-100 px-2 py-0.5 text-xs text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                        {{ __('Rejected') }}
                                    </span>
                                @else
                                    <span class="rounded bg-yellow-100 px-2 py-0.5 text-xs text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                        {{ __('Pending Approval') }}
                                    </span>
                                @endif
                            </div>
                            @if($selectedSeller->seller_approved_at)
                                <div class="flex justify-between">
                                    <span class="text-neutral-600 dark:text-neutral-400">{{ __('Approved At:') }}</span>
                                    <span class="font-medium">{{ $selectedSeller->seller_approved_at->format('M d, Y H:i') }}</span>
                                </div>
                                @if($selectedSeller->approvedBy)
                                    <div class="flex justify-between">
                                        <span class="text-neutral-600 dark:text-neutral-400">{{ __('Approved By:') }}</span>
                                        <span class="font-medium">{{ $selectedSeller->approvedBy->name }}</span>
                                    </div>
                                @endif
                            @endif
                            @if($selectedSeller->seller_rejection_reason)
                                <div class="mt-3 pt-3 border-t border-neutral-200 dark:border-neutral-700">
                                    <div class="text-neutral-600 dark:text-neutral-400 mb-1">{{ __('Rejection Reason:') }}</div>
                                    <div class="text-sm bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
                                        {{ $selectedSeller->seller_rejection_reason }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($selectedSeller->seller_approval_status != 1)
                        <div class="pt-4 border-t border-neutral-200 dark:border-neutral-700">
                            <flux:heading size="md" class="mb-3">{{ __('Actions') }}</flux:heading>
                            
                            @if(empty($selectedSeller->seller_rejection_reason))
                                <div class="space-y-4">
                                    <flux:button 
                                        variant="primary" 
                                        class="w-full"
                                        wire:click="approveSeller({{ $selectedSeller->id }})"
                                    >
                                        {{ __('Approve Seller') }}
                                    </flux:button>
                                    
                                    <div>
                                        <flux:input 
                                            wire:model="rejectionReason" 
                                            :label="__('Rejection Reason')" 
                                            type="textarea"
                                            placeholder="Enter reason for rejection..."
                                            rows="3"
                                        />
                                        <flux:button 
                                            variant="danger" 
                                            class="w-full mt-2"
                                            wire:click="rejectSeller({{ $selectedSeller->id }})"
                                        >
                                            {{ __('Reject Seller') }}
                                        </flux:button>
                                    </div>
                                </div>
                            @else
                                <flux:button 
                                    variant="primary" 
                                    class="w-full"
                                    wire:click="approveSeller({{ $selectedSeller->id }})"
                                >
                                    {{ __('Approve Seller') }}
                                </flux:button>
                            @endif
                        </div>
                    @endif
                </div>
            @else
                <div class="flex h-full items-center justify-center p-8">
                    <div class="text-center text-neutral-500 dark:text-neutral-400">
                        <flux:heading size="md" class="mb-2">{{ __('Select a Seller') }}</flux:heading>
                        <flux:text>{{ __('Choose a seller from the list to view details and manage approval.') }}</flux:text>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
