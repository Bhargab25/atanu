{{-- resources/views/livewire/client-management.blade.php --}}
<div>
    <x-mary-header title="Client Management" subtitle="Manage clients and their service configurations" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                <x-mary-input
                    icon="o-magnifying-glass"
                    placeholder="Search clients..."
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
            </button>

            <x-mary-dropdown class="dropdown-end">
                <x-slot:trigger>
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-base-200 hover:bg-base-300 transition-colors cursor-pointer">
                        <x-mary-icon name="o-bars-arrow-up" class="w-4 h-4" />
                        <span class="text-sm font-medium">Sort</span>
                    </div>
                </x-slot:trigger>

                <div class="w-64 bg-base-100 rounded-2xl shadow-xl border border-base-300 p-2">
                    <div class="space-y-1">
                        <button wire:click="updateSort('name', 'asc')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-all hover:bg-base-200">
                            <x-mary-icon name="o-arrow-up" class="w-4 h-4" />
                            <span class="text-sm">Name A → Z</span>
                        </button>
                        <button wire:click="updateSort('name', 'desc')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-all hover:bg-base-200">
                            <x-mary-icon name="o-arrow-down" class="w-4 h-4" />
                            <span class="text-sm">Name Z → A</span>
                        </button>
                        <button wire:click="updateSort('created_at', 'desc')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-all hover:bg-base-200">
                            <x-mary-icon name="o-calendar" class="w-4 h-4" />
                            <span class="text-sm">Newest First</span>
                        </button>
                    </div>
                </div>
            </x-mary-dropdown>
        </x-slot:actions>
    </x-mary-header>

    {{-- Client Modal --}}
    <x-mary-modal
        box-class="w-full max-w-screen-xl mx-auto h-[80vh] overflow-y-auto"
        wire:model="myModal"
        title="{{ $isEdit ? 'Edit Client' : 'Create Client' }}"
        subtitle="{{ $isEdit ? 'Update client details and services' : 'Add a new client with service configuration' }}">

        <x-mary-form no-separator>
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="text-lg font-semibold text-blue-800 mb-3">Company Assignment</h3>
                <x-mary-select
                    label="Owner Company *"
                    wire:model="company_profile_id"
                    :options="$companyOptions"
                    option-value="id"
                    option-label="name"
                    placeholder="Select which company owns this client..."
                    icon="o-building-office-2"
                    hint="Select the company that owns/manages this client" />
            </div>
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                {{-- Client Information (Left Column) --}}
                <div class="xl:col-span-1 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Client Information</h3>

                    <x-mary-input label="Client Name *" icon="o-user" wire:model="name" placeholder="Enter client name" />

                    <x-mary-input label="Company Name" icon="o-building-office" wire:model="company_name" placeholder="Company name (optional)" />

                    <div class="grid grid-cols-1 gap-4">
                        <x-mary-input label="Email" icon="o-envelope" type="email" wire:model="email" placeholder="client@company.com" />
                        <x-mary-input label="Phone *" icon="o-phone" wire:model="phone" placeholder="+91 9876543210" />
                        <x-mary-input label="Alternate Phone" icon="o-device-phone-mobile" wire:model="alternate_phone" placeholder="+91 9876543211" />
                    </div>

                    <x-mary-textarea label="Address" icon="o-map-pin" wire:model="address" placeholder="Complete address" rows="2" />

                    <div class="grid grid-cols-2 gap-4">
                        <x-mary-input label="City" wire:model="city" placeholder="City" />
                        <x-mary-input label="State" wire:model="state" placeholder="State" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-mary-input label="Postal Code" wire:model="postal_code" placeholder="123456" />
                        <x-mary-input label="GSTIN" wire:model="gstin" placeholder="GST Number (optional)" />
                    </div>

                    <x-mary-toggle label="Active Status" wire:model="is_active" />

                    <x-mary-textarea label="Notes" icon="o-pencil-square" wire:model="notes" placeholder="Additional notes about the client..." rows="2" />
                </div>

                {{-- Services & Items Configuration (Right 2 Columns) --}}
                <div class="xl:col-span-2 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Services & Products Selection</h3>

                    {{-- Service Selection --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Select Services *</label>
                        @error('selectedServices')
                        <p class="text-xs text-error mb-2">{{ $message }}</p>
                        @enderror
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($availableServices as $service)
                            <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors
                        {{ in_array($service['id'], $selectedServices) ? 'border-primary bg-primary/5' : 'border-gray-200' }}">
                                <input type="checkbox" wire:model.live="selectedServices" value="{{ $service['id'] }}" class="mt-1 text-primary">
                                <div class="flex-1">
                                    <div class="font-medium">{{ $service['name'] }}</div>
                                    @if($service['description'])
                                    <div class="text-sm text-gray-600">{{ $service['description'] }}</div>
                                    @endif
                                    <div class="text-xs text-gray-500 mt-1">{{ count($service['items']) }} products available</div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Products Selection for Selected Services --}}
                    @if(!empty($selectedServices))
                    <div class="space-y-4">
                        <h4 class="text-md font-semibold text-gray-700">Select Products for Each Service</h4>

                        @foreach($selectedServices as $serviceId)
                        @php
                        $service = collect($availableServices)->firstWhere('id', $serviceId);
                        $selectedProducts = $this->serviceItems[$serviceId]['items'] ?? [];
                        @endphp

                        @if($service)
                        <div class="border border-gray-200 rounded-lg p-4">

                            <h5 class="font-medium text-gray-800 mb-3">{{ $service['name'] }} Products</h5>

                            @error("serviceItems.$serviceId.items")
                            <p class="text-xs text-error mb-2">{{ $message }}</p>
                            @enderror

                            @if(!empty($service['items']))
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($service['items'] as $item)
                                <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors
                            {{ $this->isProductSelected($serviceId, $item['id']) ? 'border-success bg-success/5' : 'border-gray-200' }}">
                                    <input type="checkbox"
                                        wire:change="toggleProductSelection({{ $serviceId }}, {{ $item['id'] }})"
                                        {{ $this->isProductSelected($serviceId, $item['id']) ? 'checked' : '' }}
                                        class="mt-1 text-success">
                                    <div class="flex-1">
                                        <div class="font-medium text-sm">{{ $item['name'] }}</div>
                                        @if($item['description'])
                                        <div class="text-xs text-gray-500 mt-1">{{ Str::limit($item['description'], 80) }}</div>
                                        @endif
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @else
                            <div class="text-center py-4 text-gray-500">
                                <x-mary-icon name="o-cube" class="w-8 h-8 mx-auto mb-2 text-gray-300" />
                                <p class="text-sm">No products available for this service</p>
                            </div>
                            @endif
                        </div>
                        @endif
                        @endforeach

                        {{-- Selection Summary --}}
                        <div class="bg-blue/5 border border-blue/20 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-blue-600">Total Selected:</span>
                                <div class="flex gap-4 text-sm">
                                    <span>{{ count($selectedServices) }} Service(s)</span>
                                    <span>
                                        @php
                                        $totalProducts = 0;
                                        foreach($serviceItems as $serviceData) {
                                        $totalProducts += count($serviceData['items'] ?? []);
                                        }
                                        @endphp
                                        {{ $totalProducts }} Product(s)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-8 text-gray-500">
                        <x-mary-icon name="o-cog-6-tooth" class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                        <p class="text-sm">Select services to choose their products</p>
                    </div>
                    @endif
                </div>
            </div>

            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.cancel" />
                <x-mary-button
                    label="{{ $isEdit ? 'Update Client' : 'Create Client' }}"
                    class="btn-primary"
                    @click="$wire.saveClient"
                    spinner="saveClient" />
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
            <x-mary-button icon="o-plus" label="Add Client" class="btn-primary" @click="$wire.newClient" />
        </div>
        <x-mary-hr />

        <x-mary-table
            :headers="$headers"
            :rows="$clients"
            striped
            :sort-by="$sortBy"
            per-page="perPage"
            :row-decoration="$row_decoration"
            :per-page-values="[5, 10, 20, 50]"
            with-pagination
            show-empty-text
            empty-text="No clients found!"
            wire:model.live="selected"
            selectable>

            @scope('cell_owner_company', $row)
            <div class="text-sm">
                <div class="font-medium text-blue-600">{{ $row->company->name ?? 'N/A' }}</div>
                @if($row->company && $row->company->legal_name)
                <div class="text-xs text-gray-500">{{ $row->company->legal_name }}</div>
                @endif
            </div>
            @endscope

            @scope('cell_client_id', $row)
            <x-mary-badge :value="$row->client_id" class="badge-outline badge-sm font-mono" />
            @endscope

            @scope('cell_name', $row)
            <div>
                <div class="font-semibold">{{ $row->name }}</div>
                @if($row->email)
                <div class="text-xs text-gray-500">{{ $row->email }}</div>
                @endif
            </div>
            @endscope

            @scope('cell_company_name', $row)
            {{ $row->company_name ?: 'Individual' }}
            @endscope

            @scope('cell_phone', $row)
            <div class="text-sm">
                <div>{{ $row->phone }}</div>
                @if($row->alternate_phone)
                <div class="text-xs text-gray-500">{{ $row->alternate_phone }}</div>
                @endif
            </div>
            @endscope

            @scope('cell_services', $row)
            <div class="flex flex-wrap gap-1">
                @php
                $services = $row->services_items ? array_keys($row->services_items) : [];
                $serviceNames = \App\Models\ProductCategory::whereIn('id', $services)->pluck('name');
                @endphp
                @foreach($serviceNames as $serviceName)
                <x-mary-badge :value="$serviceName" class="badge-info badge-xs" />
                @endforeach
                @if($serviceNames->isEmpty())
                <span class="text-gray-400 text-xs">No services</span>
                @endif
            </div>
            @endscope

            @scope('cell_total_amount', $row)
            <span class="font-mono font-semibold text-primary">₹{{ number_format($row->total_amount, 2) }}</span>
            @endscope

            @scope('cell_is_active', $row)
            <x-mary-badge
                :value="$row->is_active ? 'Active' : 'Inactive'"
                :class="$row->is_active ? 'badge-success' : 'badge-error'" />
            @endscope

            @scope('cell_created_at', $row)
            {{ $row->created_at->format('d/m/Y') }}
            @endscope

            @scope('cell_actions', $row)
            <div class="flex gap-2 justify-center items-center">
                <x-mary-button
                    icon="o-eye"
                    spinner
                    class="btn-circle btn-ghost btn-xs"
                    tooltip-left="View Profile"
                    link="/clients/{{ $row->id }}/profile" />
                <x-mary-button
                    icon="o-document-text"
                    spinner
                    class="btn-circle btn-ghost btn-xs text-blue-600"
                    tooltip-left="Create Invoice"
                    link="/invoices/create?client={{ $row->id }}" />
                <x-mary-button
                    icon="o-pencil"
                    spinner
                    class="btn-circle btn-ghost btn-xs"
                    tooltip-left="Edit"
                    @click="$wire.editClient({{ $row->id }})" />
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

        @if($clientToDelete)
        <div class="space-y-4">
            <div class="p-4 bg-base-100 rounded-lg border">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-error/10 rounded-full flex items-center justify-center">
                        <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6 text-error" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-base-content">Delete Client</h3>
                        <p class="text-sm text-base-content/70">
                            Are you sure you want to delete "<strong>{{ $clientToDelete->name }}</strong>"?
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-base-content/60">Client ID:</span>
                    <span class="font-medium">{{ $clientToDelete->client_id }}</span>
                </div>
                <div>
                    <span class="text-base-content/60">Total Amount:</span>
                    <span class="font-medium">₹{{ number_format($clientToDelete->total_amount, 2) }}</span>
                </div>
            </div>

            <div class="alert alert-warning">
                <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
                <div>
                    <h4 class="font-semibold">Warning</h4>
                    <p class="text-sm">This action cannot be undone. The client and all their service configurations will be permanently deleted.</p>
                </div>
            </div>
        </div>
        @endif

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.closeDeleteModal()" />
            <x-mary-button
                label="Delete Client"
                class="btn-error"
                spinner="deleteClient"
                @click="$wire.deleteClient()" />
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

        <div class="space-y-4">
            {{-- Owner Company Filter (New) --}}
            <div>
                <label class="block text-sm font-medium mb-2">Owner Company</label>
                <x-mary-choices
                    wire:model="companyFilter"
                    :options="$companyOptions"
                    clearable
                    class="text-sm" />
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Status</label>
                <x-mary-choices
                    wire:model="statusFilter"
                    :options="$statusOptions"
                    clearable
                    class="text-sm" />
            </div>
        </div>

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