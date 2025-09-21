<div>
    <x-mary-header title="Services" subtitle="Manage services" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                <x-mary-input
                    icon="o-magnifying-glass"
                    placeholder="Search Services..."
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
                @if(count($appliedStatusFilter) > 0)
                <x-mary-badge :value="count($appliedStatusFilter)" class="badge-primary badge-sm" />
                @endif
                <!-- <span class="text-xs text-base-content/60">({{ $categories->total() ?? 0 }})</span> -->
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

                {{-- Modern card-style dropdown content --}}
                <div class="w-64 bg-base-100 rounded-2xl shadow-xl border border-base-300 p-2">
                    {{-- Header --}}
                    <div class="flex items-center gap-2 px-3 py-2 mb-2">
                        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                            <x-mary-icon name="o-bars-arrow-up" class="w-4 h-4 text-primary" />
                        </div>
                        <div>
                            <div class="font-semibold text-sm">Sort Services</div>
                            <div class="text-xs text-base-content/60">Choose how to order your list</div>
                        </div>
                    </div>

                    {{-- Sort options with modern styling --}}
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

                        {{-- Divider --}}
                        <div class="border-t border-base-300 mx-2 my-2"></div>

                        {{-- Date options --}}
                        <div class="px-2">
                            <div class="text-xs font-semibold text-secondary uppercase tracking-wider mb-1">Date Created</div>
                            <div class="space-y-0.5">
                                <button
                                    wire:click="updateSort('created_at', 'desc')"
                                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-all hover:bg-base-200 {{ $sortBy['column'] === 'created_at' && $sortBy['direction'] === 'desc' ? 'bg-secondary/10 text-secondary shadow-sm' : '' }}">
                                    <x-mary-icon name="o-calendar" class="w-4 h-4" />
                                    <span class="text-sm">Newest First</span>
                                    @if($sortBy['column'] === 'created_at' && $sortBy['direction'] === 'desc')
                                    <x-mary-icon name="o-check" class="w-4 h-4 ml-auto text-secondary" />
                                    @endif
                                </button>

                                <button
                                    wire:click="updateSort('created_at', 'asc')"
                                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-all hover:bg-base-200 {{ $sortBy['column'] === 'created_at' && $sortBy['direction'] === 'asc' ? 'bg-secondary/10 text-secondary shadow-sm' : '' }}">
                                    <x-mary-icon name="o-calendar" class="w-4 h-4" />
                                    <span class="text-sm">Oldest First</span>
                                    @if($sortBy['column'] === 'created_at' && $sortBy['direction'] === 'asc')
                                    <x-mary-icon name="o-check" class="w-4 h-4 ml-auto text-secondary" />
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
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


    <x-mary-modal wire:model="myModal2"
        title="{{ $isEdit ? 'Edit Service' : 'Create Service' }}"
        subtitle="{{ $isEdit ? 'Update the service' : 'Add a new service' }}"
        size="lg">
        <x-mary-form no-separator>
            <x-mary-input label="Name" icon="o-pencil" placeholder="Service name" wire:model="name" />
            <x-mary-input label="Description" icon="o-document-text" placeholder="Description" wire:model="description" />

            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.cancel" />
                <x-mary-button
                    label="{{ $isEdit ? 'Update' : 'Confirm' }}"
                    class="btn-primary"
                    @click="$wire.saveCategory" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-mary-card class="bg-base-200">
        <div class="flex justify-end mb-4 gap-2">
            <x-mary-button
                class="btn-secondary"
                :badge="count($selected)"
                label="Switch Status"
                icon="o-arrow-path"
                wire:click="makeDisable"
                spinner
                :disabled="count($selected) === 0" />
            <x-mary-button icon="o-plus" class="btn-primary" @click="$wire.newCategory" />
        </div>
        <x-mary-hr />
        <x-mary-table :headers="$headers" :rows="$categories" striped :sort-by="$sortBy" per-page="perPage" :row-decoration="$row_decoration" :per-page-values="[5, 10, 20]" with-pagination show-empty-text empty-text="Nothing Here!" wire:model.live="selected" selectable>
            @scope('cell_is_active', $row)
            <x-mary-badge :value="$row->is_active ? 'Active' : 'Inactive'" :class="$row->is_active ? 'badge-primary badge-soft' : 'badge-error badge-soft'" />
            @endscope
            @scope('cell_actions', $row)
            <div class="flex gap-2 justify-center items-center">
                <x-mary-button icon="o-pencil" spinner class="btn-circle btn-ghost btn-xs" tooltip-left="Edit"
                    @click="$wire.editCategory({{ $row->id }})" />
                <x-mary-button
                    icon="o-trash"
                    spinner
                    class="btn-circle btn-ghost btn-xs btn-error"
                    tooltip-left="Delete"
                    @click="$wire.confirmDeleteCategory({{ $row->id }})" />
            </div>
            @endscope
        </x-mary-table>
    </x-mary-card>


    {{-- Category Delete Confirmation Modal --}}
    <x-mary-modal
        wire:model="showDeleteCategoryModal"
        title="Confirm Service Deletion"
        box-class="backdrop-blur max-w-2xl">

        @if($categoryToDelete)
        <div class="space-y-6">
            {{-- Header with Icon --}}
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 bg-error/10 rounded-full flex items-center justify-center flex-shrink-0">
                    <x-mary-icon name="o-exclamation-triangle" class="w-8 h-8 text-error" />
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-base-content mb-2">Delete Service</h3>
                    <p class="text-base-content/70">
                        You are about to permanently delete the Service
                        "<span class="font-semibold text-primary">{{ $categoryToDelete->name }}</span>".
                    </p>
                </div>
            </div>

            {{-- Category Summary --}}
            <div class="bg-base-100 rounded-lg border p-4">
                <h4 class="font-semibold mb-3">Service Details</h4>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-base-content/60">Service Name:</span>
                        <span class="text-sm font-medium">{{ $categoryToDelete->name }}</span>
                    </div>

                    @if($categoryToDelete->description)
                    <div class="flex justify-between items-start">
                        <span class="text-sm text-base-content/60">Description:</span>
                        <span class="text-sm font-medium text-right max-w-xs">{{ $categoryToDelete->description }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between items-center">
                        <span class="text-sm text-base-content/60">Products Count:</span>
                        <span class="text-sm font-medium">
                            <x-mary-badge
                                :value="$categoryToDelete->products->count() . ' Products'"
                                class="badge-info badge-xs" />
                        </span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-sm text-base-content/60">Status:</span>
                        <x-mary-badge
                            :value="$categoryToDelete->is_active ? 'Active' : 'Inactive'"
                            :class="$categoryToDelete->is_active ? 'badge-success badge-xs' : 'badge-error badge-xs'" />
                    </div>
                </div>
            </div>

            {{-- Show products in this category --}}
            @if($categoryToDelete->products->count() > 0)
            <div class="bg-base-100 rounded-lg border p-4">
                <h4 class="font-semibold mb-3 flex items-center gap-2">
                    <x-mary-icon name="o-cube" class="w-4 h-4" />
                    Products in this Service
                </h4>
                <div class="max-h-32 overflow-y-auto space-y-1">
                    @foreach($categoryToDelete->products->take(10) as $product)
                    <div class="flex justify-between items-center text-sm py-1">
                        <span>{{ $product->name }}</span>
                        <span class="text-base-content/60">{{ $product->stock_quantity }} {{ $product->unit }}</span>
                    </div>
                    @endforeach
                    @if($categoryToDelete->products->count() > 10)
                    <div class="text-xs text-base-content/60 pt-2 border-t">
                        ... and {{ $categoryToDelete->products->count() - 10 }} more products
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Constraint Errors --}}
            @if($categoryDeleteError)
            <div class="alert alert-error">
                <x-mary-icon name="o-shield-exclamation" class="w-6 h-6" />
                <div>
                    <h4 class="font-bold">Cannot Delete Service</h4>
                    <div class="text-sm mt-2 whitespace-pre-line">{{ $categoryDeleteError }}</div>
                    <div class="text-xs mt-2 text-error/70">
                        To delete this Service, you must first:
                        <ul class="list-disc list-inside mt-1">
                            <li>Move all products to another category, or</li>
                            <li>Delete all items in this Service</li>
                        </ul>
                    </div>
                </div>
            </div>
            @else
            {{-- Deletion Warning --}}
            <div class="alert alert-warning">
                <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6" />
                <div>
                    <h4 class="font-bold">⚠️ Permanent Action</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• This Service will be permanently deleted</li>
                        <li>• All Service data will be lost</li>
                        <li>• This action cannot be undone</li>
                    </ul>
                </div>
            </div>

            {{-- Confirmation Input --}}
            <div class="border-l-4 border-error pl-4">
                <p class="text-sm text-base-content/70 mb-2">
                    Type <code class="bg-base-200 px-2 py-1 rounded text-xs">DELETE</code> to confirm:
                </p>
                <x-mary-input
                    wire:model.live="categoryDeleteConfirmation"
                    placeholder="Type DELETE to confirm"
                    class="font-mono" />
            </div>
            @endif
        </div>
        @endif

        <x-slot:actions>
            <x-mary-button
                label="Cancel"
                class="btn-ghost"
                @click="$wire.closeDeleteCategoryModal()" />

            @if(!$categoryDeleteError)
            <x-mary-button
                label="Delete Service"
                class="btn-error"
                spinner="deleteCategory"
                :disabled="$categoryDeleteConfirmation !== 'DELETE'"
                @click="$wire.deleteCategory()" />
            @endif
        </x-slot:actions>
    </x-mary-modal>

    <x-mary-drawer
        wire:model="showDrawer"
        title="Filters"
        subtitle="Apply filters to get specific results"
        separator
        with-close-button
        close-on-escape
        class="w-11/12 lg:w-1/3"
        right>
        {{-- Quick Stats Bar --}}
        <div class="flex items-center gap-4 p-3 bg-base-100 rounded border mb-4">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-info rounded-full"></div>
                <span class="text-sm">{{ $categories->total() ?? 0 }} Results</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-primary rounded-full"></div>
                <span class="text-sm">{{ count($appliedStatusFilter) }} Filters</span>
            </div>
        </div>

        {{-- Filters --}}
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Status</label>
                <x-mary-choices
                    wire:model="statusFilter"
                    :options="$statusOptions"
                    clearable
                    class="text-sm" />
            </div>
        </div>

        {{-- Applied Preview --}}
        @if(count($appliedStatusFilter) > 0)
        <div class="mt-4 p-2 bg-base-200 rounded text-center">
            <div class="text-xs text-base-content/60 mb-1">Applied:</div>
            <div class="flex flex-wrap gap-1 justify-center">
                @foreach($appliedStatusFilter as $filter)
                <span class="px-1.5 py-0.5 bg-primary text-primary-content text-xs rounded">
                    {{ ucfirst($filter) }}
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