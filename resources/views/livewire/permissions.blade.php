<?php

use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use App\Models\Role;
use App\Models\Permission;

new class extends Component {
    public $users;
    public $roles;
    public $permissions;
    public $selectedUser = null;
    public $search = '';

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->users = User::all();
        $this->roles = Role::all();
        $this->permissions = Permission::all();
    }

    public function updatedSearch(): void
    {
        if (empty($this->search)) {
            $this->users = User::all();
        } else {
            $this->users = User::where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%')
                ->get();
        }
    }

    public function selectUser($userId): void
    {
        $this->selectedUser = User::find($userId);
        if ($this->selectedUser) {
            // Load relationships
            $this->selectedUser->load('roles', 'permissions');
        }
    }

    public function assignRole($roleId): void
    {
        if (!$this->selectedUser) {
            return;
        }

        $role = Role::find($roleId);
        if ($role && !$this->selectedUser->hasRole($role)) {
            $this->selectedUser->assignRole($role);
            $this->loadData();
            $this->selectedUser->load('roles', 'permissions');
            $this->dispatch('role-assigned');
        }
    }

    public function removeRole($roleId): void
    {
        if (!$this->selectedUser) {
            return;
        }

        $role = Role::find($roleId);
        if ($role && $this->selectedUser->hasRole($role)) {
            $this->selectedUser->removeRole($role);
            $this->loadData();
            $this->selectedUser->load('roles', 'permissions');
            $this->dispatch('role-removed');
        }
    }

    public function assignPermission($permissionId): void
    {
        if (!$this->selectedUser) {
            return;
        }

        $permission = Permission::find($permissionId);
        if ($permission && !$this->selectedUser->hasDirectPermission($permission)) {
            $this->selectedUser->givePermissionTo($permission);
            $this->loadData();
            $this->selectedUser->load('roles', 'permissions');
            $this->dispatch('permission-assigned');
        }
    }

    public function removePermission($permissionId): void
    {
        if (!$this->selectedUser) {
            return;
        }

        $permission = Permission::find($permissionId);
        if ($permission && $this->selectedUser->hasDirectPermission($permission)) {
            $this->selectedUser->revokePermissionTo($permission);
            $this->loadData();
            $this->selectedUser->load('roles', 'permissions');
            $this->dispatch('permission-removed');
        }
    }

}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>{{ __('User Permissions Management') }}</flux:heading>
            <flux:subheading>{{ __('Manage roles and permissions for all users') }}</flux:subheading>
        </div>
    </div>

    <x-action-message on="role-assigned" class="text-green-600 dark:text-green-400">
        {{ __('Role assigned successfully.') }}
    </x-action-message>
    <x-action-message on="role-removed" class="text-green-600 dark:text-green-400">
        {{ __('Role removed successfully.') }}
    </x-action-message>
    <x-action-message on="permission-assigned" class="text-green-600 dark:text-green-400">
        {{ __('Permission assigned successfully.') }}
    </x-action-message>
    <x-action-message on="permission-removed" class="text-green-600 dark:text-green-400">
        {{ __('Permission removed successfully.') }}
    </x-action-message>
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Users List -->
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
            <div class="border-b border-neutral-200 dark:border-neutral-700 p-4">
                <flux:heading size="lg">{{ __('Users') }}</flux:heading>
                <div class="mt-3">
                    <flux:input 
                        wire:model.live.debounce.300ms="search" 
                        :label="__('Search Users')" 
                        type="text"
                        placeholder="Search by name or email..."
                    />
                </div>
            </div>
            <div class="max-h-[600px] overflow-y-auto p-4">
                <div class="space-y-2">
                    @forelse ($users as $user)
                        <div 
                            wire:click="selectUser({{ $user->id }})"
                            class="cursor-pointer rounded-lg border p-3 transition-colors {{ $selectedUser && $selectedUser->id === $user->id ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-700/50' }}"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-semibold">{{ $user->name }}</div>
                                    <div class="text-sm text-neutral-600 dark:text-neutral-400">{{ $user->email }}</div>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ count($user->roles) }} {{ Str::plural('role', count($user->roles)) }}
                                    </span>
                                    <span class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ count($user->permissions) }} {{ Str::plural('permission', count($user->permissions)) }}
                                    </span>
                                </div>
                            </div>
                            @if ($user->roles->count() > 0)
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($user->roles as $role)
                                        <span class="rounded bg-primary-100 px-2 py-0.5 text-xs text-primary-800 dark:bg-primary-900/30 dark:text-primary-300">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="py-8 text-center text-neutral-500 dark:text-neutral-400">
                            {{ __('No users found.') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- User Details -->
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
            @if ($selectedUser)
                <div class="border-b border-neutral-200 dark:border-neutral-700 p-4">
                    <flux:heading size="lg">{{ $selectedUser->name }}</flux:heading>
                    <flux:subheading>{{ $selectedUser->email }}</flux:subheading>
                </div>
                <div class="max-h-[600px] overflow-y-auto p-4 space-y-6">
                    <!-- Roles Section -->
                    <div>
                        <flux:heading size="md" class="mb-3">{{ __('Roles') }}</flux:heading>
                        <div class="space-y-2 mb-4">
                            @if ($selectedUser->roles->count() > 0)
                                @foreach ($selectedUser->roles as $role)
                                    <div class="flex items-center justify-between rounded-lg border border-neutral-200 dark:border-neutral-700 p-3">
                                        <span class="font-medium">{{ $role->name }}</span>
                                        <flux:button 
                                            variant="danger" 
                                            size="sm"
                                            wire:click="removeRole({{ $role->id }})"
                                        >
                                            {{ __('Remove') }}
                                        </flux:button>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ __('No roles assigned.') }}
                                </div>
                            @endif
                        </div>
                        @if ($roles->count() > 0)
                            <div class="space-y-2">
                                <flux:heading size="sm" class="mb-2">{{ __('Assign Role') }}</flux:heading>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ($roles as $role)
                                        @if (!$selectedUser->hasRole($role))
                                            <flux:button 
                                                variant="ghost" 
                                                size="sm"
                                                wire:click="assignRole({{ $role->id }})"
                                            >
                                                {{ $role->name }}
                                            </flux:button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <flux:separator />

                    <!-- Permissions Section -->
                    <div>
                        <flux:heading size="md" class="mb-3">{{ __('Permissions') }}</flux:heading>
                        <div class="space-y-2 mb-4">
                            @if ($selectedUser->permissions->count() > 0)
                                @foreach ($selectedUser->permissions as $permission)
                                    <div class="flex items-center justify-between rounded-lg border border-neutral-200 dark:border-neutral-700 p-3">
                                        <span class="font-medium">{{ $permission->name }}</span>
                                        <flux:button 
                                            variant="danger" 
                                            size="sm"
                                            wire:click="removePermission({{ $permission->id }})"
                                        >
                                            {{ __('Remove') }}
                                        </flux:button>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ __('No permissions assigned.') }}
                                </div>
                            @endif
                        </div>
                        @if ($permissions->count() > 0)
                            <div class="space-y-2">
                                <flux:heading size="sm" class="mb-2">{{ __('Assign Permission') }}</flux:heading>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ($permissions as $permission)
                                        @if (!$selectedUser->hasDirectPermission($permission))
                                            <flux:button 
                                                variant="ghost" 
                                                size="sm"
                                                wire:click="assignPermission({{ $permission->id }})"
                                            >
                                                {{ $permission->name }}
                                            </flux:button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="flex h-full items-center justify-center p-8">
                    <div class="text-center text-neutral-500 dark:text-neutral-400">
                        <flux:heading size="md" class="mb-2">{{ __('Select a User') }}</flux:heading>
                        <flux:text>{{ __('Choose a user from the list to manage their roles and permissions.') }}</flux:text>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

