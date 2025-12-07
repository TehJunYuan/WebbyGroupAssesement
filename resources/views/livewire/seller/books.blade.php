<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\Scopes\ActiveScope;
use App\Rules\BookCoverImageSize;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads, WithPagination;

    public $categories;
    public $search = '';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $selectedBook = null;
    public $title = '';
    public $description = '';
    public $price = '';
    public $stock_quantity = 0;
    public $category_id = '';
    public $cover_image;
    public $cover_image_preview = null;

    public function mount(): void
    {
        $this->categories = BookCategory::all();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function loadBooks()
    {
        $query = Book::bySeller(auth()->id())
            ->withoutGlobalScope(ActiveScope::class)
            ->with('category');

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('InsertAt', 'desc')->paginate(10);
    }

    public function openCreateForm(): void
    {
        if (!auth()->user()->can('create books')) {
            abort(403, 'Unauthorized action.');
        }

        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditForm($bookId): void
    {
        if (!auth()->user()->can('edit own books')) {
            abort(403, 'Unauthorized action.');
        }

        $book = Book::withoutGlobalScope(ActiveScope::class)
            ->bySeller(auth()->id())
            ->findOrFail($bookId);

        $this->selectedBook = $book;
        $this->title = $book->title;
        $this->description = $book->description ?? '';
        $this->price = $book->price;
        $this->stock_quantity = $book->stock_quantity;
        $this->category_id = $book->category_id;
        
        $this->cover_image_preview = $book->getCoverImageUrl('preview');
        $this->showEditModal = true;
    }

    public function closeForms(): void
    {
        $this->resetForm();
        $this->showCreateModal = false;
        $this->showEditModal = false;
    }

    public function resetForm(): void
    {
        $this->title = '';
        $this->description = '';
        $this->price = '';
        $this->stock_quantity = 0;
        $this->category_id = '';
        $this->cover_image = null;
        $this->cover_image_preview = null;
        $this->selectedBook = null;
    }

    public function updatedCoverImage(): void
    {
        $this->validate([
            'cover_image' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($this->cover_image) {
            $this->cover_image_preview = $this->cover_image->temporaryUrl();
        } else {
            if ($this->selectedBook) {
                $this->cover_image_preview = $this->selectedBook->getCoverImageUrl('preview');
            }
        }
    }

    public function createBook(): void
    {
        if (!auth()->user()->can('create books')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:book_categories,id'],
            'cover_image' => ['required', 'image', 'max:5120', 'mimes:jpeg,png,jpg,webp', new BookCoverImageSize()],
        ]);

        $coverImagePath = null;
        if ($this->cover_image) {
            $fileName = time() . '_' . uniqid() . '.' . $this->cover_image->getClientOriginalExtension();
            $coverImagePath = $this->cover_image->storeAs('books/cover_image', $fileName, 'public');
        }

        $book = Book::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'stock_quantity' => $validated['stock_quantity'],
            'category_id' => $validated['category_id'],
            'cover_image' => $coverImagePath,
            'seller_id' => auth()->id(),
            'InsertAt' => now(),
            'InsertUserId' => auth()->id(),
            'IsActive' => 1,
        ]);

        $this->resetPage();
        $this->dispatch('book-created');
        $this->closeForms();
    }

    public function updateBook(): void
    {
        if (!auth()->user()->can('edit own books')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$this->selectedBook) {
            return;
        }

        $book = Book::withoutGlobalScope(ActiveScope::class)
            ->bySeller(auth()->id())
            ->findOrFail($this->selectedBook->id);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:book_categories,id'],
            'cover_image' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,png,jpg,webp', new BookCoverImageSize()],
        ]);

        $book->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'stock_quantity' => $validated['stock_quantity'],
            'category_id' => $validated['category_id'],
            'UpdateBy' => now(),
            'UpdateUserId' => auth()->id(),
        ]);

        if ($this->cover_image) {
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            
            $fileName = time() . '_' . $book->id . '.' . $this->cover_image->getClientOriginalExtension();
            $path = $this->cover_image->storeAs('books/cover_image', $fileName, 'public');
            
            $book->cover_image = $path;
            $book->save();
        }

        $this->resetPage();
        $this->dispatch('book-updated');
        $this->closeForms();
    }

    public function deleteBook($bookId): void
    {
        if (!auth()->user()->can('delete own books')) {
            abort(403, 'Unauthorized action.');
        }

        $book = Book::withoutGlobalScope(ActiveScope::class)
            ->bySeller(auth()->id())
            ->findOrFail($bookId);
        
        $book->softDelete();
        $this->resetPage();
        $this->dispatch('book-deleted');
    }

    public function restoreBook($bookId): void
    {
        if (!auth()->user()->can('create books')) {
            abort(403, 'Unauthorized action.');
        }

        $book = Book::withoutGlobalScope(ActiveScope::class)
            ->bySeller(auth()->id())
            ->findOrFail($bookId);
        
        $book->restore();
        $this->resetPage();
        $this->dispatch('book-restored');
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>{{ __('My Books') }}</flux:heading>
            <flux:subheading>{{ __('Manage your book inventory') }}</flux:subheading>
        </div>
        @can('create books')
            <div class="flex gap-2">
                <flux:button variant="primary" wire:click="openCreateForm">
                    {{ __('Upload New Book') }}
                </flux:button>
            </div>
        @endcan
    </div>

    <x-action-message on="book-created" class="text-green-600 dark:text-green-400">
        {{ __('Book uploaded successfully.') }}
    </x-action-message>
    <x-action-message on="book-updated" class="text-green-600 dark:text-green-400">
        {{ __('Book updated successfully.') }}
    </x-action-message>
    <x-action-message on="book-deleted" class="text-green-600 dark:text-green-400">
        {{ __('Book deleted successfully.') }}
    </x-action-message>
    <x-action-message on="book-restored" class="text-green-600 dark:text-green-400">
        {{ __('Book restored successfully.') }}
    </x-action-message>

    <flux:modal name="create-book-modal" wire:model="showCreateModal" class="max-w-2xl" focusable>
        <form wire:submit="createBook" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Upload New Book') }}</flux:heading>
                <flux:subheading>{{ __('Fill in the details to upload a new book') }}</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input 
                    wire:model="title" 
                    :label="__('Book Title')" 
                    type="text" 
                    required 
                    autofocus
                    placeholder="e.g., The Great Gatsby"
                />
                <div>
                    <label for="category_id_create" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                        {{ __('Category') }} <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="category_id_create"
                        wire:model="category_id" 
                        required
                        class="w-full rounded-lg border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 px-3 py-2 focus:border-primary-500 focus:ring-primary-500 focus:outline-none focus:ring-2"
                    >
                        <option value="">{{ __('Select a category') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <flux:textarea 
                wire:model="description" 
                :label="__('Description')" 
                rows="4"
                placeholder="Enter book description..."
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input 
                    wire:model="price" 
                    :label="__('Price')" 
                    type="number" 
                    step="0.01"
                    min="0"
                    required
                    placeholder="0.00"
                />
                <flux:input 
                    wire:model="stock_quantity" 
                    :label="__('Stock Quantity')" 
                    type="number" 
                    min="0"
                    required
                    placeholder="0"
                />
            </div>

            <div>
                <flux:input 
                    wire:model="cover_image" 
                    :label="__('Cover Image')" 
                    type="file" 
                    accept="image/jpeg,image/png,image/jpg,image/webp"
                    required
                />
                @if ($cover_image_preview)
                    <div class="mt-3">
                        <div class="relative inline-block">
                            <img 
                                src="{{ $cover_image_preview }}" 
                                alt="Cover Preview" 
                                class="max-w-xs h-auto rounded-lg border-2 border-neutral-300 dark:border-neutral-600 shadow-lg object-cover"
                                style="max-height: 300px;"
                            />
                        </div>
                    </div>
                @endif
                <flux:description class="mt-1">
                    {{ __('Upload a cover image (JPEG, PNG, WEBP, max 5MB). Recommended size: 600x900 pixels (2:3 aspect ratio). Minimum: 300x450 pixels.') }}
                </flux:description>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="closeForms">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ __('Upload Book') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-book-modal" wire:model="showEditModal" class="max-w-2xl" focusable>
        <form wire:submit="updateBook" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Edit Book') }}</flux:heading>
                <flux:subheading>{{ __('Update the book details') }}</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input 
                    wire:model="title" 
                    :label="__('Book Title')" 
                    type="text" 
                    required 
                    autofocus
                    placeholder="e.g., The Great Gatsby"
                />
                <div>
                    <label for="category_id_edit" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                        {{ __('Category') }} <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="category_id_edit"
                        wire:model="category_id" 
                        required
                        class="w-full rounded-lg border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 px-3 py-2 focus:border-primary-500 focus:ring-primary-500 focus:outline-none focus:ring-2"
                    >
                        <option value="">{{ __('Select a category') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <flux:textarea 
                wire:model="description" 
                :label="__('Description')" 
                rows="4"
                placeholder="Enter book description..."
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input 
                    wire:model="price" 
                    :label="__('Price')" 
                    type="number" 
                    step="0.01"
                    min="0"
                    required
                    placeholder="0.00"
                />
                <flux:input 
                    wire:model="stock_quantity" 
                    :label="__('Stock Quantity')" 
                    type="number" 
                    min="0"
                    required
                    placeholder="0"
                />
            </div>

            <div>
                <flux:input 
                    wire:model="cover_image" 
                    :label="__('Cover Image')" 
                    type="file" 
                    accept="image/jpeg,image/png,image/jpg,image/webp"
                />
                @if ($cover_image_preview)
                    <div class="mt-3">
                        <div class="relative inline-block">
                            <img 
                                src="{{ $cover_image_preview }}" 
                                alt="Cover Preview" 
                                class="max-w-xs h-auto rounded-lg border-2 border-neutral-300 dark:border-neutral-600 shadow-lg object-cover"
                                style="max-height: 300px;"
                            />
                        </div>
                    </div>
                @endif
                <flux:description class="mt-1">
                    {{ __('Upload a new cover image (optional, JPEG, PNG, WEBP, max 5MB). Recommended size: 600x900 pixels (2:3 aspect ratio). Minimum: 300x450 pixels.') }}
                </flux:description>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="closeForms">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ __('Update Book') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
        <div class="border-b border-neutral-200 dark:border-neutral-700 p-4">
            <flux:heading size="lg">{{ __('All Books') }}</flux:heading>
            <div class="mt-3">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    :label="__('Search Books')" 
                    type="text"
                    placeholder="Search books by title or description..."
                />
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-neutral-50 dark:bg-neutral-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Cover') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Title') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Category') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Price') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Stock') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Status') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Created At') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-neutral-900 divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($this->loadBooks() as $book)
                        <tr wire:key="book-{{ $book->id }}" class="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $coverUrl = $book->getCoverImageUrl('thumb') ?? 'https://via.placeholder.com/60x80?text=No+Cover';
                                @endphp
                                <img src="{{ $coverUrl }}" alt="{{ $book->title }}" class="h-20 w-14 object-cover rounded">
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                    {{ $book->title }}
                                </div>
                                @if($book->description)
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 max-w-md truncate">
                                        {{ Str::limit($book->description, 50) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $book->category->name ?? 'Uncategorized' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-primary-600 dark:text-primary-400">
                                    ${{ number_format($book->price, 2) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $book->stock_quantity }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($book->IsActive == 1)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                        {{ __('Active') }}
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                        {{ __('Inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $book->InsertAt ? $book->InsertAt->format('M d, Y') : '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    @can('edit own books')
                                        <flux:button 
                                            variant="ghost" 
                                            size="sm"
                                            wire:click="openEditForm({{ $book->id }})"
                                            wire:key="edit-book-{{ $book->id }}"
                                        >
                                            {{ __('Edit') }}
                                        </flux:button>
                                    @endcan
                                    @can('delete own books')
                                        @if ($book->IsActive == 1)
                                            <flux:button 
                                                variant="danger" 
                                                size="sm"
                                                wire:click="deleteBook({{ $book->id }})"
                                                wire:key="delete-book-{{ $book->id }}"
                                                wire:confirm="Are you sure you want to delete this book?"
                                            >
                                                {{ __('Delete') }}
                                            </flux:button>
                                        @else
                                            <flux:button 
                                                variant="success" 
                                                size="sm"
                                                wire:click="restoreBook({{ $book->id }})"
                                                wire:key="restore-book-{{ $book->id }}"
                                                wire:confirm="Are you sure you want to restore this book?"
                                            >
                                                {{ __('Restore') }}
                                            </flux:button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">
                                {{ __('No books found. Start by uploading your first book!') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-neutral-200 dark:border-neutral-700 px-4 py-3">
            {{ $this->loadBooks()->links() }}
        </div>
    </div>
</div>

