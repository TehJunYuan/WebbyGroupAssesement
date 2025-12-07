<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Gender;
use App\Models\Scopes\ActiveScope;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $selectedGender = null;
    public $name = '';
    public $description = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function loadGenders()
    {
        $query = Gender::withoutGlobalScope(ActiveScope::class);

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('Description', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('name')->paginate(10);
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditForm($genderId): void
    {
        $gender = Gender::withoutGlobalScope(ActiveScope::class)->find($genderId);
        if ($gender) {
            $this->selectedGender = $gender;
            $this->name = $gender->name;
            $this->description = $gender->Description ?? '';
            $this->showEditModal = true;
        }
    }

    public function closeForms(): void
    {
        $this->resetForm();
        $this->showCreateModal = false;
        $this->showEditModal = false;
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->description = '';
        $this->selectedGender = null;
    }

    public function createGender(): void
    {
        if (!auth()->user()->can('create genders')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:genders,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Gender::create([
            'name' => $this->name,
            'Description' => $this->description,
            'InsertAt' => now(),
            'InsertUserId' => auth()->id(),
            'IsActive' => 1,
        ]);

        $this->resetPage();
        $this->dispatch('gender-created');
        $this->closeForms();
    }

    public function updateGender(): void
    {
        if (!auth()->user()->can('edit genders')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$this->selectedGender) {
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:genders,name,' . $this->selectedGender->id],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $gender = Gender::withoutGlobalScope(ActiveScope::class)->find($this->selectedGender->id);
        if ($gender) {
            $gender->update([
                'name' => $this->name,
                'Description' => $this->description,
                'UpdateBy' => now(),
                'UpdateUserId' => auth()->id(),
            ]);

            $this->resetPage();
            $this->dispatch('gender-updated');
            $this->closeForms();
        }
    }

    public function deleteGender($genderId): void
    {
        if (!auth()->user()->can('delete genders')) {
            abort(403, 'Unauthorized action.');
        }

        $gender = Gender::withoutGlobalScope(ActiveScope::class)->find($genderId);
        if ($gender) {
            $gender->softDelete();
            $this->resetPage();
            $this->dispatch('gender-deleted');
        }
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>{{ __('Genders Management') }}</flux:heading>
            <flux:subheading>{{ __('Manage all gender options in the system') }}</flux:subheading>
        </div>
        @can('create genders')
            <div class="flex gap-2">
                <flux:button variant="primary" wire:click="openCreateForm">
                    {{ __('Create Gender') }}
                </flux:button>
            </div>
        @endcan
    </div>

    <x-action-message on="gender-created" class="text-green-600 dark:text-green-400">
        {{ __('Gender created successfully.') }}
    </x-action-message>
    <x-action-message on="gender-updated" class="text-green-600 dark:text-green-400">
        {{ __('Gender updated successfully.') }}
    </x-action-message>
    <x-action-message on="gender-deleted" class="text-green-600 dark:text-green-400">
        {{ __('Gender deleted successfully.') }}
    </x-action-message>

    <flux:modal name="create-gender-modal" wire:model="showCreateModal" class="max-w-xl" focusable>
        <form wire:submit="createGender" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Create New Gender') }}</flux:heading>
                <flux:subheading>{{ __('Add a new gender option to the system') }}</flux:subheading>
            </div>
            <flux:input 
                wire:model="name" 
                :label="__('Gender Name')" 
                type="text" 
                required 
                autofocus
                placeholder="e.g., Male, Female, Other"
            />
            <flux:textarea 
                wire:model="description" 
                :label="__('Description')" 
                rows="3"
                placeholder="Enter gender description..."
            />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="closeForms">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ __('Create Gender') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-gender-modal" wire:model="showEditModal" class="max-w-xl" focusable>
        @if ($selectedGender)
            <form wire:submit="updateGender" class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ __('Edit Gender') }}</flux:heading>
                    <flux:subheading>{{ __('Update the gender details') }}</flux:subheading>
                </div>
                <flux:input 
                    wire:model="name" 
                    :label="__('Gender Name')" 
                    type="text" 
                    required 
                    autofocus
                />
                <flux:textarea 
                    wire:model="description" 
                    :label="__('Description')" 
                    rows="3"
                />
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button" wire:click="closeForms">
                            {{ __('Cancel') }}
                        </flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">
                        {{ __('Update Gender') }}
                    </flux:button>
                </div>
            </form>
        @endif
    </flux:modal>

    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
        <div class="border-b border-neutral-200 dark:border-neutral-700 p-4">
            <flux:heading size="lg">{{ __('All Genders') }}</flux:heading>
            <div class="mt-3">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    :label="__('Search Genders')" 
                    type="text"
                    placeholder="Search by name or description..."
                />
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-neutral-50 dark:bg-neutral-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Gender Name') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Description') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Created At') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Status') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-neutral-900 divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($this->loadGenders() as $gender)
                        <tr wire:key="gender-{{ $gender->id }}" class="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                    {{ $gender->name }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-neutral-500 dark:text-neutral-400 max-w-md truncate">
                                    {{ $gender->Description ? Str::limit($gender->Description, 100) : '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $gender->InsertAt ? $gender->InsertAt->format('M d, Y') : '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($gender->IsActive == 1)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                        {{ __('Active') }}
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                        {{ __('Inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    @can('edit genders')
                                        <flux:button 
                                            variant="ghost" 
                                            size="sm"
                                            wire:click="openEditForm({{ $gender->id }})"
                                            wire:key="edit-gender-{{ $gender->id }}"
                                        >
                                            {{ __('Edit') }}
                                        </flux:button>
                                    @endcan
                                    @can('delete genders')
                                        <flux:button 
                                            variant="danger" 
                                            size="sm"
                                            wire:click="deleteGender({{ $gender->id }})"
                                            wire:key="delete-gender-{{ $gender->id }}"
                                            wire:confirm="Are you sure you want to delete this gender?"
                                        >
                                            {{ __('Delete') }}
                                        </flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">
                                {{ __('No genders found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-neutral-200 dark:border-neutral-700 px-4 py-3">
            {{ $this->loadGenders()->links() }}
        </div>
    </div>
</div>

