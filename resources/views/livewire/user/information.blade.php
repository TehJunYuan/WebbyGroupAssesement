<?php

use Livewire\Volt\Component;
use App\Models\UserInformation;
use App\Models\Gender;
use App\Models\Scopes\ActiveScope;

new class extends Component {
    public $full_name = '';
    public $address = '';
    public $phone_number = '';
    public $date_of_birth = '';
    public $gender_id = '';
    public $genders = [];
    public $userInfo = null;

    public function mount(): void
    {
        $this->genders = Gender::all();
        $this->loadUserInformation();
    }

    public function loadUserInformation(): void
    {
        $this->userInfo = UserInformation::where('user_id', auth()->id())
            ->withoutGlobalScope(ActiveScope::class)
            ->first();

        if ($this->userInfo) {
            $this->full_name = $this->userInfo->full_name ?? '';
            $this->address = $this->userInfo->address ?? '';
            $this->phone_number = $this->userInfo->phone_number ?? '';
            $this->date_of_birth = $this->userInfo->date_of_birth ? $this->userInfo->date_of_birth->format('Y-m-d') : '';
            $this->gender_id = $this->userInfo->gender_id ?? '';
        }
    }

    public function saveUserInformation(): void
    {
        $this->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'phone_number' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender_id' => ['nullable', 'exists:genders,id'],
        ]);

        $data = [
            'user_id' => auth()->id(),
            'full_name' => $this->full_name,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'date_of_birth' => $this->date_of_birth ? $this->date_of_birth : null,
            'gender_id' => $this->gender_id ? $this->gender_id : null,
            'IsActive' => 1,
        ];

        $wasFirstTime = false;
        
        if ($this->userInfo) {
            $this->userInfo->update($data);
            $this->dispatch('user-info-updated');
        } else {
            $wasFirstTime = true;
            $data['InsertAt'] = now();
            $data['InsertUserId'] = auth()->id();
            UserInformation::create($data);
            $this->dispatch('user-info-created');
        }

        $this->loadUserInformation();
        
        if ($wasFirstTime) {
            $user = auth()->user();
            if ($user->hasRole('Seller')) {
                $this->redirect(route('seller.panel'), navigate: true);
            } else {
                $this->redirect(route('shop.index'), navigate: true);
            }
        }
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div>
        <flux:heading>{{ __('Personal Information') }}</flux:heading>
        @if($userInfo)
            <flux:subheading>{{ __('Update your personal information') }}</flux:subheading>
        @else
            <flux:subheading>{{ __('Please provide your personal information to continue') }}</flux:subheading>
        @endif
    </div>

    <x-action-message on="user-info-created" class="text-green-600 dark:text-green-400">
        {{ __('Personal information saved successfully.') }}
    </x-action-message>
    <x-action-message on="user-info-updated" class="text-green-600 dark:text-green-400">
        {{ __('Personal information updated successfully.') }}
    </x-action-message>

    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
        <form wire:submit="saveUserInformation" class="space-y-6">
            <flux:input 
                wire:model="full_name" 
                :label="__('Full Name')" 
                type="text" 
                required 
                autofocus
                placeholder="e.g., John Doe"
            />

            <flux:textarea 
                wire:model="address" 
                :label="__('Address')" 
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
                wire:model="date_of_birth" 
                :label="__('Date of Birth')" 
                type="date"
                placeholder="Select your date of birth"
            />

            <flux:select wire:model="gender_id" :label="__('Gender')">
                <option value="">{{ __('Select Gender') }}</option>
                @foreach($genders as $gender)
                    <option value="{{ $gender->id }}">{{ $gender->name }}</option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:button variant="primary" type="submit">
                    {{ $userInfo ? __('Update Information') : __('Save Information') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>

