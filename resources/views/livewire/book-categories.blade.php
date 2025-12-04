<?php

use Livewire\Volt\Component;
use App\Models\BookCategory;
use App\Models\Scopes\ActiveScope;
use Illuminate\Support\Str;

new class extends Component {
    public $categories;
    public $search = '';
    public $showCreateForm = false;
    public $showEditForm = false;
    public $selectedCategory = null;
    public $name = '';
    public $description = '';

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->categories = BookCategory::all();
    }

    public function updatedSearch(): void
    {
        if (empty($this->search)) {
            $this->categories = BookCategory::all();
        } else {
            $this->categories = BookCategory::where('name', 'like', '%' . $this->search . '%')
                ->orWhere('Description', 'like', '%' . $this->search . '%')
                ->get();
        }
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showCreateForm = true;
        $this->showEditForm = false;
    }

    public function openEditForm($categoryId): void
    {
        $category = BookCategory::withoutGlobalScope(ActiveScope::class)->find($categoryId);
        if ($category) {
            $this->selectedCategory = $category;
            $this->name = $category->name;
            $this->description = $category->Description ?? '';
            $this->showEditForm = true;
            $this->showCreateForm = false;
        }
    }

    public function closeForms(): void
    {
        $this->resetForm();
        $this->showCreateForm = false;
        $this->showEditForm = false;
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

        $this->closeForms();
        $this->loadData();
        $this->dispatch('category-created');
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

            $this->closeForms();
            $this->loadData();
            $this->dispatch('category-updated');
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
            $this->loadData();
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

    @if ($showCreateForm)
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Create New Category') }}</flux:heading>
            <form wire:submit="createCategory" class="space-y-4">
                <flux:input 
                    wire:model="name" 
                    :label="__('Category Name')" 
                    type="text" 
                    required 
                    autofocus
                    placeholder="e.g., Fiction, Science, Technology"
                />
                <flux:input 
                    wire:model="description" 
                    :label="__('Description')" 
                    type="textarea"
                    rows="3"
                    placeholder="Enter category description..."
                />
                <div class="flex gap-2">
                    <flux:button variant="primary" type="submit">
                        {{ __('Create Category') }}
                    </flux:button>
                    <flux:button variant="ghost" type="button" wire:click="closeForms">
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    @if ($showEditForm && $selectedCategory)
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Edit Category') }}</flux:heading>
            <form wire:submit="updateCategory" class="space-y-4">
                <flux:input 
                    wire:model="name" 
                    :label="__('Category Name')" 
                    type="text" 
                    required 
                    autofocus
                />
                <flux:input 
                    wire:model="description" 
                    :label="__('Description')" 
                    type="textarea"
                    rows="3"
                />
                <div class="flex gap-2">
                    <flux:button variant="primary" type="submit">
                        {{ __('Update Category') }}
                    </flux:button>
                    <flux:button variant="ghost" type="button" wire:click="closeForms">
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

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
        <div class="p-4">
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($categories as $category)
                    <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-4 hover:bg-neutral-50 dark:hover:bg-neutral-700/50">
                        <div class="mb-3">
                            <div class="font-semibold text-lg">{{ $category->name }}</div>
                            @if($category->Description)
                                <div class="text-sm text-neutral-600 dark:text-neutral-400 mt-2">
                                    {{ Str::limit($category->Description, 100) }}
                                </div>
                            @endif
                            @if($category->InsertAt)
                                <div class="text-xs text-neutral-500 dark:text-neutral-400 mt-2">
                                    Created: {{ $category->InsertAt->format('M d, Y') }}
                                </div>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            @can('edit categories')
                                <flux:button 
                                    variant="ghost" 
                                    size="sm"
                                    wire:click="openEditForm({{ $category->id }})"
                                >
                                    {{ __('Edit') }}
                                </flux:button>
                            @endcan
                            @can('delete categories')
                                <flux:button 
                                    variant="danger" 
                                    size="sm"
                                    wire:click="deleteCategory({{ $category->id }})"
                                    wire:confirm="Are you sure you want to delete this category?"
                                >
                                    {{ __('Delete') }}
                                </flux:button>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-8 text-center text-neutral-500 dark:text-neutral-400">
                        {{ __('No categories found.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

