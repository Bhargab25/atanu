<div>
    <x-mary-header title="Products" subtitle="Manage product inventory" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                <x-mary-input
                    icon="o-magnifying-glass"
                    placeholder="Search products..."
                    wire:model.live.debounce.300ms="search"
                    class="w-64" />
                @if($search)
                <x-mary-button
                    icon="o-x-mark"
                    class="btn-ghost btn-sm btn-circle"
                    wire:click="clearSearch"
                    tooltip="Clear search" />
                @endif
            </div>
        </x-slot:middle>
        <x-slot:actions>
            <button
                @click="$wire.showDrawer = true"
                class="flex items-center gap-2 px-3 py-2 bg-base-200 hover:bg-base-300 rounded-lg transition-colors">
                <x-mary-icon name="o-funnel" class="w-4 h-4" />
                <span class="text-sm">Filters</span>
                @if(count($appliedStatusFilter) > 0 || count($appliedCategoryFilter) > 0)
                <x-mary-badge :value="count($appliedStatusFilter) + count($appliedCategoryFilter)" class="badge-primary badge-sm" />
                @endif
            </button>

            <x-mary-dropdown class="dropdown-end">
                <x-slot:trigger>
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-base-200 hover:bg-base-300 transition-colors cursor-pointer">
                        <x-mary-icon name="o-bars-arrow-up" class="w-4 h-4" />
                        <span class="text-sm font-medium">Sort</span>
                        <x-mary-badge
                            :value="ucfirst($sortBy['column'])"
                            class="badge-primary badge-sm" />
                        <x-mary-icon
                            :name="$sortBy['direction'] === 'asc' ? 'o-arrow-up' : 'o-arrow-down'"
                            class="w-3 h-3" />
                    </div>
                </x-slot:trigger>

                <div class="w-64 bg-base-100 rounded-2xl shadow-xl border border-base-300 p-2">
                    <div class="flex items-center gap-2 px-3 py-2 mb-2">
                        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                            <x-mary-icon name="o-bars-arrow-up" class="w-4 h-4 text-primary" />
                        </div>
                        <div>
                            <div class="font-semibold text-sm">Sort Products</div>
                            <div class="text-xs text-base-content/60">Choose how to order your list</div>
                        </div>
                    </div>

                    <div class="space-y-1">
                        {{-- Name options --}}
                        <div class="px-2">
                            <div class="text-xs font-semibold text-primary uppercase tracking-wider mb-1">Name</div>
                            <div class="space-y-0.5">
                                <button
                                    wire:click="updateSort('name', 'asc')"
                                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-all hover:bg-base-200 {{ $sortBy['column'] === 'name' && $sortBy['direction'] === 'asc' ? 'bg-primary/10 text-primary shadow-sm' : '' }}">
                                    <x-mary-icon name="o-arrow-up" class="w-4 h-4" />
                                    <span class="text-sm">A → Z</span>
                                    @if($sortBy['column'] === 'name' && $sortBy['direction'] === 'asc')
                                    <x-mary-icon name="o-check" class="w-4 h-4 ml-auto text-primary" />
                                    @endif
                                </button>

                                <button
                                    wire:click="updateSort('name', 'desc')"
                                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-all hover:bg-base-200 {{ $sortBy['column'] === 'name' && $sortBy['direction'] === 'desc' ? 'bg-primary/10 text-primary shadow-sm' : '' }}">
                                    <x-mary-icon name="o-arrow-down" class="w-4 h-4" />
                                    <span class="text-sm">Z → A</span>
                                    @if($sortBy['column'] === 'name' && $sortBy['direction'] === 'desc')
                                    <x-mary-icon name="o-check" class="w-4 h-4 ml-auto text-primary" />
                                    @endif
                                </button>
                            </div>
                        </div>

                        <div class="border-t border-base-300 mx-2 my-2"></div>

                        {{-- Date options --}}
                        <div class="px-2">
                            <div class="text-xs font-semibold text-secondary uppercase tracking-wider mb-1">Created Date</div>
                            <div class="space-y-0.5">
                                <button
                                    wire:click="updateSort('created_at', 'desc')"
                                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-all hover:bg-base-200 {{ $sortBy['column'] === 'created_at' && $sortBy['direction'] === 'desc' ? 'bg-secondary/10 text-secondary shadow-sm' : '' }}">
                                    <x-mary-icon name="o-clock" class="w-4 h-4" />
                                    <span class="text-sm">Newest First</span>
                                    @if($sortBy['column'] === 'created_at' && $sortBy['direction'] === 'desc')
                                    <x-mary-icon name="o-check" class="w-4 h-4 ml-auto text-secondary" />
                                    @endif
                                </button>

                                <button
                                    wire:click="updateSort('created_at', 'asc')"
                                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-all hover:bg-base-200 {{ $sortBy['column'] === 'created_at' && $sortBy['direction'] === 'asc' ? 'bg-secondary/10 text-secondary shadow-sm' : '' }}">
                                    <x-mary-icon name="o-clock" class="w-4 h-4" />
                                    <span class="text-sm">Oldest First</span>
                                    @if($sortBy['column'] === 'created_at' && $sortBy['direction'] === 'asc')
                                    <x-mary-icon name="o-check" class="w-4 h-4 ml-auto text-secondary" />
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-base-300 mt-3 pt-2">
                        <button
                            wire:click="resetSort"
                            class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-base-content/70 hover:text-base-content hover:bg-base-200 transition-all">
                            <x-mary-icon name="o-arrow-path" class="w-4 h-4" />
                            Reset to Default
                        </button>
                    </div>
                </div>
            </x-mary-dropdown>
        </x-slot:actions>
    </x-mary-header>

    {{-- Product Modal --}}
    <x-mary-modal wire:model="myModal"
        title="{{ $isEdit ? 'Edit Product' : 'Create Product' }}"
        subtitle="{{ $isEdit ? 'Update the product details' : 'Add a new product to inventory' }}"
        size="lg">
        <x-mary-form no-separator>
            <x-mary-input label="Product Name *" icon="o-cube" placeholder="Product name" wire:model="name" />

            <x-mary-select
                label="Category *"
                icon="o-tag"
                wire:model="service_id"
                :options="$categories"
                option-value="id"
                option-label="name"
                placeholder="Select a category" />

            <x-mary-textarea label="Description" icon="o-document-text" wire:model="description" placeholder="Product description..." hint="Max 1000 chars" rows="3" />

            <x-mary-toggle label="Active Status" wire:model="is_active" />

            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.cancel" />
                <x-mary-button
                    label="{{ $isEdit ? 'Update' : 'Create' }}"
                    class="btn-primary"
                    @click="$wire.saveProduct"
                    spinner="saveProduct" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    {{-- Main Content --}}
    <x-mary-card class="bg-base-200">
        <div class="flex justify-between items-center mb-4">
            <div class="flex gap-2">
                <x-mary-button
                    class="btn-secondary"
                    :badge="count($selected)"
                    label="Toggle Status"
                    icon="o-arrow-path"
                    wire:click="toggleStatus"
                    spinner="toggleStatus"
                    :disabled="count($selected) === 0" />
            </div>
            <x-mary-button icon="o-plus" label="Add Product" class="btn-primary" @click="$wire.newProduct" />
        </div>
        <x-mary-hr />

        <x-mary-table
            :headers="$headers"
            :rows="$products"
            striped
            :sort-by="$sortBy"
            per-page="perPage"
            :row-decoration="$row_decoration"
            :per-page-values="[5, 10, 20, 50]"
            with-pagination
            show-empty-text
            empty-text="No products found!"
            wire:model.live="selected"
            selectable>

            @scope('cell_category.name', $row)
            <x-mary-badge :value="$row->category->name ?? 'No Category'" class="badge-outline badge-sm" />
            @endscope

            @scope('cell_description', $row)
            @if($row->description)
            <div class="max-w-xs truncate" title="{{ $row->description }}">
                {{ $row->description }}
            </div>
            @else
            <span class="text-gray-400 italic">No description</span>
            @endif
            @endscope

            @scope('cell_is_active', $row)
            <x-mary-badge
                :value="$row->is_active ? 'Active' : 'Inactive'"
                :class="$row->is_active ? 'badge-success' : 'badge-error'" />
            @endscope

            @scope('cell_actions', $row)
            <div class="flex gap-2 justify-center items-center">
                <x-mary-button
                    icon="o-pencil"
                    spinner
                    class="btn-circle btn-ghost btn-xs"
                    tooltip-left="Edit"
                    @click="$wire.editProduct({{ $row->id }})" />
                <x-mary-button
                    icon="o-trash"
                    spinner
                    class="btn-circle btn-ghost btn-xs btn-error"
                    tooltip-left="Delete"
                    @click="$wire.confirmDelete({{ $row->id }})" />
            </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Delete Confirmation Modal --}}
    <x-mary-modal
        wire:model="showDeleteModal"
        title="Confirm Deletion"
        box-class="backdrop-blur max-w-lg">

        @if($productToDelete)
        <div class="space-y-4">
            {{-- Product Info --}}
            <div class="p-4 bg-base-100 rounded-lg border">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-error/10 rounded-full flex items-center justify-center">
                        <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6 text-error" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-base-content">Delete Product</h3>
                        <p class="text-sm text-base-content/70">
                            Are you sure you want to delete "<strong>{{ $productToDelete->name }}</strong>"?
                        </p>
                    </div>
                </div>
            </div>

            {{-- Product Details --}}
            <div class="grid grid-cols-1 gap-4 text-sm">
                <div>
                    <span class="text-base-content/60">Category:</span>
                    <span class="font-medium">{{ $productToDelete->category->name ?? 'N/A' }}</span>
                </div>
                @if($productToDelete->description)
                <div>
                    <span class="text-base-content/60">Description:</span>
                    <span class="font-medium">{{ Str::limit($productToDelete->description, 50) }}</span>
                </div>
                @endif
            </div>

            {{-- Error Message if Can't Delete --}}
            @if($deleteError)
            <div class="alert alert-error">
                <x-mary-icon name="o-x-circle" class="w-5 h-5" />
                <div>
                    <h4 class="font-semibold">Cannot Delete Product</h4>
                    <div class="text-sm mt-1 whitespace-pre-line">{{ $deleteError }}</div>
                </div>
            </div>
            @else
            {{-- Warning Message --}}
            <div class="alert alert-warning">
                <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
                <div>
                    <h4 class="font-semibold">Warning</h4>
                    <p class="text-sm">This action cannot be undone. The product will be permanently deleted.</p>
                </div>
            </div>
            @endif
        </div>
        @endif

        <x-slot:actions>
            <x-mary-button
                label="Cancel"
                @click="$wire.closeDeleteModal()" />

            @if(!$deleteError)
            <x-mary-button
                label="Delete Product"
                class="btn-error"
                spinner="deleteProduct"
                @click="$wire.deleteProduct()" />
            @endif
        </x-slot:actions>
    </x-mary-modal>

    {{-- Filter Drawer --}}
    <x-mary-drawer
        wire:model="showDrawer"
        title="Filters"
        subtitle="Apply filters to get specific results"
        separator
        with-close-button
        close-on-escape
        class="w-11/12 lg:w-1/3"
        right>

        {{-- Stats Bar --}}
        <div class="flex items-center gap-4 p-3 bg-base-100 rounded border mb-4">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-info rounded-full"></div>
                <span class="text-sm">{{ $products->total() ?? 0 }} Results</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-primary rounded-full"></div>
                <span class="text-sm">{{ count($appliedStatusFilter) + count($appliedCategoryFilter) }} Filters</span>
            </div>
        </div>

        {{-- Filter Sections --}}
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Status</label>
                <x-mary-choices
                    wire:model="statusFilter"
                    :options="$statusOptions"
                    clearable
                    class="text-sm" />
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Category</label>
                <x-mary-choices
                    wire:model="categoryFilter"
                    :options="$categoryOptions"
                    clearable
                    class="text-sm" />
            </div>
        </div>

        {{-- Applied Filters Preview --}}
        @if(count($appliedStatusFilter) > 0 || count($appliedCategoryFilter) > 0)
        <div class="mt-4 p-2 bg-base-200 rounded text-center">
            <div class="text-xs text-base-content/60 mb-1">Applied:</div>
            <div class="flex flex-wrap gap-1 justify-center">
                @foreach($appliedStatusFilter as $filter)
                <span class="px-1.5 py-0.5 bg-primary text-primary-content text-xs rounded">
                    Status: {{ ucfirst($filter) }}
                </span>
                @endforeach
                @foreach($appliedCategoryFilter as $categoryId)
                @php
                $categoryName = collect($categoryOptions)->firstWhere('id', $categoryId)['name'] ?? 'Unknown';
                @endphp
                <span class="px-1.5 py-0.5 bg-secondary text-secondary-content text-xs rounded">
                    {{ $categoryName }}
                </span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Actions --}}
        <x-slot:actions>
            <x-mary-button
                label="Reset"
                @click="$wire.resetFilters"
                class="btn-ghost btn-sm" />
            <x-mary-button
                label="Apply"
                class="btn-primary btn-sm"
                @click="$wire.applyFilters" />
        </x-slot:actions>
    </x-mary-drawer>
</div>