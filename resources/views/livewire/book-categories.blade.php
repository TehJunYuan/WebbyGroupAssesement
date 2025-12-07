<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\BookCategory;
use App\Models\Scopes\ActiveScope;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $selectedCategory = null;
    public $name = '';
    public $description = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function loadCategories()
    {
        $query = BookCategory::withoutGlobalScope(ActiveScope::class);

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

    public function openEditForm($categoryId): void
    {
        $category = BookCategory::withoutGlobalScope(ActiveScope::class)->find($categoryId);
        if ($category) {
            $this->selectedCategory = $category;
            $this->name = $category->name;
            $this->description = $category->Description ?? '';
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
        $this->selectedCategory = null;
    }

    public function createCategory(): void
    {
        if (!auth()->user()->can('create categories')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:book_categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        BookCategory::create([
            'name' => $this->name,
            'Description' => $this->description,
            'InsertAt' => now(),
            'InsertUserId' => auth()->id(),
            'IsActive' => 1,
        ]);

        $this->resetPage();
        $this->dispatch('category-created');
        $this->closeForms();
    }

    public function updateCategory(): void
    {
        if (!auth()->user()->can('edit categories')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$this->selectedCategory) {
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:book_categories,name,' . $this->selectedCategory->id],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category = BookCategory::withoutGlobalScope(ActiveScope::class)->find($this->selectedCategory->id);
        if ($category) {
            $category->update([
                'name' => $this->name,
                'Description' => $this->description,
                'UpdateBy' => now(),
                'UpdateUserId' => auth()->id(),
            ]);

            $this->resetPage();
            $this->dispatch('category-updated');
            $this->closeForms();
        }
    }

    public function deleteCategory($categoryId): void
    {
        if (!auth()->user()->can('delete categories')) {
            abort(403, 'Unauthorized action.');
        }

        $category = BookCategory::withoutGlobalScope(ActiveScope::class)->find($categoryId);
        if ($category) {
            $category->softDelete();
            $this->resetPage();
            $this->dispatch('category-deleted');
        }
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>{{ __('Book Categories Management') }}</flux:heading>
            <flux:subheading>{{ __('Manage all book categories in the system') }}</flux:subheading>
        </div>
        @can('create categories')
            <div class="flex gap-2">
                <flux:button variant="primary" wire:click="openCreateForm">
                    {{ __('Create Category') }}
                </flux:button>
            </div>
        @endcan
    </div>

    <x-action-message on="category-created" class="text-green-600 dark:text-green-400">
        {{ __('Category created successfully.') }}
    </x-action-message>
    <x-action-message on="category-updated" class="text-green-600 dark:text-green-400">
        {{ __('Category updated successfully.') }}
    </x-action-message>
    <x-action-message on="category-deleted" class="text-green-600 dark:text-green-400">
        {{ __('Category deleted successfully.') }}
    </x-action-message>

    <flux:modal name="create-category-modal" wire:model="showCreateModal" class="max-w-xl" focusable>
        <form wire:submit="createCategory" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Create New Category') }}</flux:heading>
                <flux:subheading>{{ __('Add a new book category to the system') }}</flux:subheading>
            </div>
            <flux:input 
                wire:model="name" 
                :label="__('Category Name')" 
                type="text" 
                required 
                autofocus
                placeholder="e.g., Fiction, Science, Technology"
            />
            <flux:textarea 
                wire:model="description" 
                :label="__('Description')" 
                rows="3"
                placeholder="Enter category description..."
            />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="closeForms">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ __('Create Category') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-category-modal" wire:model="showEditModal" class="max-w-xl" focusable>
        @if ($selectedCategory)
            <form wire:submit="updateCategory" class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ __('Edit Category') }}</flux:heading>
                    <flux:subheading>{{ __('Update the category details') }}</flux:subheading>
                </div>
                <flux:input 
                    wire:model="name" 
                    :label="__('Category Name')" 
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
                        {{ __('Update Category') }}
                    </flux:button>
                </div>
            </form>
        @endif
    </flux:modal>

    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
        <div class="border-b border-neutral-200 dark:border-neutral-700 p-4">
            <flux:heading size="lg">{{ __('All Categories') }}</flux:heading>
            <div class="mt-3">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    :label="__('Search Categories')" 
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
                            {{ __('Category Name') }}
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
                    @forelse ($this->loadCategories() as $category)
                        <tr wire:key="category-{{ $category->id }}" class="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                    {{ $category->name }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-neutral-500 dark:text-neutral-400 max-w-md truncate">
                                    {{ $category->Description ? Str::limit($category->Description, 100) : '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $category->InsertAt ? $category->InsertAt->format('M d, Y') : '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($category->IsActive == 1)
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
                                    @can('edit categories')
                                        <flux:button 
                                            variant="ghost" 
                                            size="sm"
                                            wire:click="openEditForm({{ $category->id }})"
                                            wire:key="edit-category-{{ $category->id }}"
                                        >
                                            {{ __('Edit') }}
                                        </flux:button>
                                    @endcan
                                    @can('delete categories')
                                        <flux:button 
                                            variant="danger" 
                                            size="sm"
                                            wire:click="deleteCategory({{ $category->id }})"
                                            wire:key="delete-category-{{ $category->id }}"
                                            wire:confirm="Are you sure you want to delete this category?"
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
                                {{ __('No categories found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-neutral-200 dark:border-neutral-700 px-4 py-3">
            {{ $this->loadCategories()->links() }}
        </div>
    </div>
</div>

