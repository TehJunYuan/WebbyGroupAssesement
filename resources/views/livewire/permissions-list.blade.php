<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Permission;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $showCreatePermissionModal = false;
    public $newPermissionName = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function loadPermissions()
    {
        $query = Permission::query();

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return $query->paginate(10);
    }

    public function createPermission(): void
    {
        $this->validate([
            'newPermissionName' => ['required', 'string', 'max:255', 'unique:permissions,name'],
        ]);

        Permission::create(['name' => $this->newPermissionName, 'guard_name' => 'web', 'IsActive' => 1]);
        $this->newPermissionName = '';
        $this->resetPage();
        $this->dispatch('permission-created');
        $this->showCreatePermissionModal = false;
    }

    public function deletePermission($permissionId): void
    {
        $permission = Permission::withoutGlobalScope(\App\Models\Scopes\ActiveScope::class)->find($permissionId);
        if ($permission) {
            $permission->softDelete();
            $this->resetPage();
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
            <flux:button 
                variant="ghost" 
                wire:click="$set('showCreatePermissionModal', true)"
            >
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

    <flux:modal name="create-permission-modal" wire:model="showCreatePermissionModal" class="max-w-xl" focusable>
        <form wire:submit="createPermission" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Create New Permission') }}</flux:heading>
                <flux:subheading>{{ __('Add a new permission to the system') }}</flux:subheading>
            </div>
            <flux:input 
                wire:model="newPermissionName" 
                :label="__('Permission Name')" 
                type="text" 
                required 
                autofocus
                placeholder="e.g., edit posts, delete users"
            />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="$set('showCreatePermissionModal', false)">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ __('Create Permission') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

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
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-neutral-50 dark:bg-neutral-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Permission Name') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Guard Name') }}
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
                    @forelse ($this->loadPermissions() as $permission)
                        <tr wire:key="permission-{{ $permission->id }}" class="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                    {{ $permission->name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $permission->guard_name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($permission->IsActive == 1)
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
                                <flux:button 
                                    variant="danger" 
                                    size="sm"
                                    wire:click="deletePermission({{ $permission->id }})"
                                    wire:key="delete-permission-{{ $permission->id }}"
                                    wire:confirm="Are you sure you want to delete this permission?"
                                >
                                    {{ __('Delete') }}
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">
                                {{ __('No permissions found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-neutral-200 dark:border-neutral-700 px-4 py-3">
            {{ $this->loadPermissions()->links() }}
        </div>
    </div>
</div>

