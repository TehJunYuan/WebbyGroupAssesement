<?php

use Livewire\Volt\Component;
use App\Models\Permission;

new class extends Component {
    public $permissions;
    public $search = '';
    public $showCreatePermission = false;
    public $newPermissionName = '';

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->permissions = Permission::all();
    }

    public function updatedSearch(): void
    {
        if (empty($this->search)) {
            $this->permissions = Permission::all();
        } else {
            $this->permissions = Permission::where('name', 'like', '%' . $this->search . '%')->get();
        }
    }

    public function createPermission(): void
    {
        $this->validate([
            'newPermissionName' => ['required', 'string', 'max:255', 'unique:permissions,name'],
        ]);

        Permission::create(['name' => $this->newPermissionName, 'guard_name' => 'web', 'IsActive' => 1]);
        $this->newPermissionName = '';
        $this->showCreatePermission = false;
        $this->loadData();
        $this->dispatch('permission-created');
    }

    public function deletePermission($permissionId): void
    {
        $permission = Permission::withoutGlobalScope(\App\Models\Scopes\ActiveScope::class)->find($permissionId);
        if ($permission) {
            $permission->softDelete();
            $this->loadData();
            $this->dispatch('permission-deleted');
        }
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>{{ __('Permissions Management') }}</flux:heading>
            <flux:subheading>{{ __('Manage all permissions in the system') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button variant="ghost" wire:click="$toggle('showCreatePermission')">
                {{ __('Create Permission') }}
            </flux:button>
        </div>
    </div>

    <x-action-message on="permission-created" class="text-green-600 dark:text-green-400">
        {{ __('Permission created successfully.') }}
    </x-action-message>
    <x-action-message on="permission-deleted" class="text-green-600 dark:text-green-400">
        {{ __('Permission deleted successfully.') }}
    </x-action-message>

    @if ($showCreatePermission)
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Create New Permission') }}</flux:heading>
            <form wire:submit="createPermission" class="space-y-4">
                <flux:input 
                    wire:model="newPermissionName" 
                    :label="__('Permission Name')" 
                    type="text" 
                    required 
                    autofocus
                    placeholder="e.g., edit posts, delete users"
                />
                <div class="flex gap-2">
                    <flux:button variant="primary" type="submit">
                        {{ __('Create Permission') }}
                    </flux:button>
                    <flux:button variant="ghost" type="button" wire:click="$set('showCreatePermission', false)">
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
        <div class="border-b border-neutral-200 dark:border-neutral-700 p-4">
            <flux:heading size="lg">{{ __('All Permissions') }}</flux:heading>
            <div class="mt-3">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    :label="__('Search Permissions')" 
                    type="text"
                    placeholder="Search permissions..."
                />
            </div>
        </div>
        <div class="p-4">
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($permissions as $permission)
                    <div class="flex items-center justify-between rounded-lg border border-neutral-200 dark:border-neutral-700 p-4 hover:bg-neutral-50 dark:hover:bg-neutral-700/50">
                        <div class="flex-1">
                            <div class="font-medium">{{ $permission->name }}</div>
                            <div class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                                Guard: {{ $permission->guard_name }}
                            </div>
                        </div>
                        <flux:button 
                            variant="danger" 
                            size="sm"
                            wire:click="deletePermission({{ $permission->id }})"
                            wire:confirm="Are you sure you want to delete this permission?"
                        >
                            {{ __('Delete') }}
                        </flux:button>
                    </div>
                @empty
                    <div class="col-span-full py-8 text-center text-neutral-500 dark:text-neutral-400">
                        {{ __('No permissions found.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

