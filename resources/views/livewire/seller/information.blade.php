<?php

use Livewire\Volt\Component;
use App\Models\SellerInformation;
use App\Models\Scopes\ActiveScope;

new class extends Component {
    public $seller_name = '';
    public $seller_address = '';
    public $phone_number = '';
    public $tax_id = '';
    public $description = '';
    public $sellerInfo = null;

    public function mount(): void
    {
        $this->loadSellerInformation();
    }

    public function loadSellerInformation(): void
    {
        $this->sellerInfo = SellerInformation::where('user_id', auth()->id())
            ->withoutGlobalScope(ActiveScope::class)
            ->first();

        if ($this->sellerInfo) {
            $this->seller_name = $this->sellerInfo->seller_name ?? '';
            $this->seller_address = $this->sellerInfo->seller_address ?? '';
            $this->phone_number = $this->sellerInfo->phone_number ?? '';
            $this->tax_id = $this->sellerInfo->tax_id ?? '';
            $this->description = $this->sellerInfo->description ?? '';
        }
    }

    public function saveSellerInformation(): void
    {
        if (!auth()->user()->hasRole('Seller')) {
            abort(403, 'Only sellers can save seller information.');
        }

        $this->validate([
            'seller_name' => ['required', 'string', 'max:255'],
            'seller_address' => ['required', 'string', 'max:1000'],
            'phone_number' => ['required', 'string', 'max:20'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $data = [
            'user_id' => auth()->id(),
            'seller_name' => $this->seller_name,
            'seller_address' => $this->seller_address,
            'phone_number' => $this->phone_number,
            'tax_id' => $this->tax_id,
            'description' => $this->description,
            'IsActive' => 1,
        ];

        $wasFirstTime = false;
        
        if ($this->sellerInfo) {
            $this->sellerInfo->update($data);
            $this->dispatch('seller-info-updated');
        } else {
            $wasFirstTime = true;
            $data['InsertAt'] = now();
            $data['InsertUserId'] = auth()->id();
            SellerInformation::create($data);
            $this->dispatch('seller-info-created');
        }

        $this->loadSellerInformation();
        
        if ($wasFirstTime) {
            if (auth()->user()->isApprovedSeller()) {
                $this->redirect(route('seller.books.index'), navigate: true);
            }
        }
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div>
        <flux:heading>{{ __('Seller Information') }}</flux:heading>
        @if($sellerInfo)
            <flux:subheading>{{ __('Update your seller information') }}</flux:subheading>
        @else
            <flux:subheading>{{ __('Please provide your seller information to continue') }}</flux:subheading>
        @endif
    </div>

    @php
        $user = auth()->user();
        $isApproved = $user->isApprovedSeller();
        $isPending = $user->isPendingSeller();
        $isRejected = $user->isRejectedSeller();
    @endphp

    @if($isPending)
        <div class="rounded-xl border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 p-4">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200">
                        {{ __('Pending Approval') }}
                    </h3>
                    <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                        {{ __('Your seller account is pending approval. Please wait for admin approval before accessing seller features.') }}
                    </p>
                    @if($user->seller_applied_at)
                        <p class="mt-2 text-xs text-yellow-600 dark:text-yellow-400">
                            {{ __('Applied: :time', ['time' => $user->seller_applied_at->diffForHumans()]) }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($isRejected)
        <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-red-800 dark:text-red-200">
                        {{ __('Account Rejected') }}
                    </h3>
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                        {{ __('Your seller account has been rejected. Please contact admin for more information.') }}
                    </p>
                    @if($user->seller_rejection_reason)
                        <div class="mt-2 rounded-md bg-red-100 dark:bg-red-900/30 p-3">
                            <p class="text-xs font-medium text-red-800 dark:text-red-200">
                                {{ __('Rejection Reason:') }}
                            </p>
                            <p class="mt-1 text-xs text-red-700 dark:text-red-300">
                                {{ $user->seller_rejection_reason }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($isApproved)
        <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-4">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-green-800 dark:text-green-200">
                        {{ __('Account Approved') }}
                    </h3>
                    <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                        {{ __('Your seller account has been approved. You can now access all seller features.') }}
                    </p>
                    @if($user->seller_approved_at)
                        <p class="mt-2 text-xs text-green-600 dark:text-green-400">
                            {{ __('Approved: :time', ['time' => $user->seller_approved_at->diffForHumans()]) }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <x-action-message on="seller-info-created" class="text-green-600 dark:text-green-400">
        {{ __('Seller information saved successfully.') }}
    </x-action-message>
    <x-action-message on="seller-info-updated" class="text-green-600 dark:text-green-400">
        {{ __('Seller information updated successfully.') }}
    </x-action-message>

    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
        <form wire:submit="saveSellerInformation" class="space-y-6">
            <flux:input 
                wire:model="seller_name" 
                :label="__('Seller Name')" 
                type="text" 
                required 
                autofocus
                placeholder="e.g., John's Bookstore"
            />

            <flux:textarea 
                wire:model="seller_address" 
                :label="__('Seller Address')" 
                rows="3"
                required
                placeholder="Enter your address..."
            />

            <flux:input 
                wire:model="phone_number" 
                :label="__('Phone Number')" 
                type="text" 
                required
                placeholder="e.g., +1234567890"
            />

            <flux:input 
                wire:model="tax_id" 
                :label="__('Tax ID / Business Registration Number')" 
                type="text"
                placeholder="Enter tax ID or business registration number (optional)"
            />

            <flux:textarea 
                wire:model="description" 
                :label="__('Business Description')" 
                rows="4"
                placeholder="Tell us about your business (optional)..."
            />

            <div class="flex justify-end gap-2">
                <flux:button variant="primary" type="submit">
                    {{ $sellerInfo ? __('Update Information') : __('Save Information') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>

