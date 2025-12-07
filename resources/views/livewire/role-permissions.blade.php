<?php

use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Role;
use App\Models\Permission;

new class extends Component {
    use WithPagination;

    public $permissions;
    public $selectedRole = null;
    public $search = '';
    public $permissionSearch = '';
    public $showCreateRoleModal = false;
    public $newRoleName = '';

    public function mount(): void
    {
        $this->permissions = Permission::all();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function loadRoles()
    {
        $query = Role::with('permissions');

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return $query->orderBy('name')->paginate(10);
    }

    public function selectRole($roleId): void
    {
        $this->selectedRole = Role::find($roleId);
        if ($this->selectedRole) {
            // Load permissions relationship
            $this->selectedRole->load('permissions');
        }
    }

    public function assignPermission($permissionId): void
    {
        if (!$this->selectedRole) {
            return;
        }

        $permission = Permission::find($permissionId);
        if ($permission && !$this->selectedRole->hasPermissionTo($permission)) {
            $this->selectedRole->givePermissionTo($permission);
            $this->resetPage();
            $this->selectedRole = Role::with('permissions')->find($this->selectedRole->id);
            $this->dispatch('permission-assigned');
        }
    }

    public function removePermission($permissionId): void
    {
        if (!$this->selectedRole) {
            return;
        }

        $permission = Permission::find($permissionId);
        if ($permission && $this->selectedRole->hasPermissionTo($permission)) {
            $roleId = $this->selectedRole->id;
            $this->selectedRole->revokePermissionTo($permission);
            $this->resetPage();
            $this->selectedRole = Role::with('permissions')->find($roleId);
            $this->dispatch('permission-removed');
        }
    }

    public function getFilteredPermissionsProperty()
    {
        if (empty($this->permissionSearch)) {
            return $this->permissions;
        }

        return $this->permissions->filter(function ($permission) {
            return stripos($permission->name, $this->permissionSearch) !== false;
        });
    }

    public function createRole(): void
    {
        $this->validate([
            'newRoleName' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        Role::create(['name' => $this->newRoleName, 'guard_name' => 'web', 'IsActive' => 1]);
        $this->newRoleName = '';
        $this->loadData();
        $this->dispatch('role-created');
        $this->showCreateRoleModal = false;
    }

    public function deleteRole($roleId): void
    {
        $role = Role::withoutGlobalScope(\App\Models\Scopes\ActiveScope::class)->find($roleId);
        if ($role) {
            // If the selected role is being deleted, clear the selection
            if ($this->selectedRole && $this->selectedRole->id === $roleId) {
                $this->selectedRole = null;
            }
            $role->softDelete();
            $this->resetPage();
            $this->dispatch('role-deleted');
        }
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>{{ __('Role Permissions Management') }}</flux:heading>
            <flux:subheading>{{ __('Assign permissions to roles') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button 
                variant="ghost" 
                wire:click="$set('showCreateRoleModal', true)"
            >
                {{ __('Create Role') }}
            </flux:button>
        </div>
    </div>

    <x-action-message on="permission-assigned" class="text-green-600 dark:text-green-400">
        {{ __('Permission assigned to role successfully.') }}
    </x-action-message>
    <x-action-message on="permission-removed" class="text-green-600 dark:text-green-400">
        {{ __('Permission removed from role successfully.') }}
    </x-action-message>
    <x-action-message on="role-created" class="text-green-600 dark:text-green-400">
        {{ __('Role created successfully.') }}
    </x-action-message>
    <x-action-message on="role-deleted" class="text-green-600 dark:text-green-400">
        {{ __('Role deleted successfully.') }}
    </x-action-message>

    <flux:modal name="create-role-modal" wire:model="showCreateRoleModal" class="max-w-xl" focusable>
        <form wire:submit="createRole" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Create New Role') }}</flux:heading>
                <flux:subheading>{{ __('Add a new role to the system') }}</flux:subheading>
            </div>
            <flux:input 
                wire:model="newRoleName" 
                :label="__('Role Name')" 
                type="text" 
                required 
                autofocus
                placeholder="e.g., editor, moderator"
            />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="$set('showCreateRoleModal', false)">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ __('Create Role') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Roles List -->
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
            <div class="border-b border-neutral-200 dark:border-neutral-700 p-4">
                <flux:heading size="lg">{{ __('Roles') }}</flux:heading>
                <div class="mt-3">
                    <flux:input 
                        wire:model.live.debounce.300ms="search" 
                        :label="__('Search Roles')" 
                        type="text"
                        placeholder="Search roles..."
                    />
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-neutral-50 dark:bg-neutral-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                {{ __('Role Name') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                {{ __('Permissions Count') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-neutral-900 divide-y divide-neutral-200 dark:divide-neutral-700">
                        @forelse ($this->loadRoles() as $role)
                            <tr 
                                wire:key="role-{{ $role->id }}"
                                wire:click="selectRole({{ $role->id }})"
                                class="cursor-pointer transition-colors {{ $selectedRole && $selectedRole->id === $role->id ? 'bg-primary-50 dark:bg-primary-900/20' : 'hover:bg-neutral-50 dark:hover:bg-neutral-800' }}"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                        {{ $role->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $role->permissions->count() }} {{ Str::plural('permission', $role->permissions->count()) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <flux:button 
                                        variant="danger" 
                                        size="sm"
                                        wire:click.stop="deleteRole({{ $role->id }})"
                                        wire:key="delete-role-{{ $role->id }}"
                                        wire:confirm="Are you sure you want to delete this role? This will remove the role from all users."
                                    >
                                        {{ __('Delete') }}
                                    </flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">
                                    {{ __('No roles found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-neutral-200 dark:border-neutral-700 px-4 py-3">
                {{ $this->loadRoles()->links() }}
            </div>
        </div>

        <!-- Role Details & Permissions -->
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
            @if ($selectedRole)
                <div class="border-b border-neutral-200 dark:border-neutral-700 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="lg">{{ $selectedRole->name }}</flux:heading>
                            <flux:subheading>
                                {{ __(':count permissions assigned', ['count' => $selectedRole->permissions->count()]) }}
                            </flux:subheading>
                        </div>
                        <flux:button 
                            variant="danger" 
                            size="sm"
                            wire:click="deleteRole({{ $selectedRole->id }})"
                            wire:confirm="Are you sure you want to delete this role? This will remove the role from all users."
                        >
                            {{ __('Delete Role') }}
                        </flux:button>
                    </div>
                </div>
                <div class="max-h-[600px] overflow-y-auto p-4 space-y-6">
                    <!-- Assigned Permissions Section -->
                    <div>
                        <flux:heading size="md" class="mb-3">{{ __('Assigned Permissions') }}</flux:heading>
                        <div class="space-y-2 mb-4">
                            @if ($selectedRole->permissions->count() > 0)
                                @foreach ($selectedRole->permissions as $permission)
                                    <div wire:key="permission-{{ $permission->id }}" class="flex items-center justify-between rounded-lg border border-neutral-200 dark:border-neutral-700 p-3">
                                        <span class="font-medium">{{ $permission->name }}</span>
                                        <flux:button 
                                            variant="danger" 
                                            size="sm"
                                            wire:click="removePermission({{ $permission->id }})"
                                            wire:key="remove-permission-{{ $permission->id }}"
                                        >
                                            {{ __('Remove') }}
                                        </flux:button>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ __('No permissions assigned to this role.') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <flux:separator />

                    <!-- Available Permissions Section -->
                    <div>
                        <flux:heading size="md" class="mb-3">{{ __('Available Permissions') }}</flux:heading>
                        
                        <div class="mb-3">
                            <flux:input 
                                wire:model.live.debounce.300ms="permissionSearch" 
                                :label="__('Search Permissions')" 
                                type="text"
                                placeholder="Filter permissions..."
                            />
                        </div>

                        @if ($this->filteredPermissions->count() > 0)
                            <div class="space-y-2">
                                <div class="grid grid-cols-1 gap-2 max-h-[400px] overflow-y-auto">
                                    @foreach ($this->filteredPermissions as $permission)
                                        @php
                                            $hasPermission = $selectedRole->hasPermissionTo($permission);
                                        @endphp
                                        @if (!$hasPermission)
                                            <flux:button 
                                                variant="ghost" 
                                                size="sm"
                                                wire:click="assignPermission({{ $permission->id }})"
                                                class="justify-start text-left"
                                            >
                                                {{ $permission->name }}
                                            </flux:button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                {{ __('No available permissions found.') }}
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="flex h-full items-center justify-center p-8">
                    <div class="text-center text-neutral-500 dark:text-neutral-400">
                        <flux:heading size="md" class="mb-2">{{ __('Select a Role') }}</flux:heading>
                        <flux:text>{{ __('Choose a role from the list to manage its permissions.') }}</flux:text>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

