<div>
    <x-mary-header title="Employees" subtitle="Manage employee records and information" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                <x-mary-input
                    icon="o-magnifying-glass"
                    placeholder="Search employees..."
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
                @if(count($appliedStatusFilter) > 0 || count($appliedDepartmentFilter) > 0)
                <x-mary-badge :value="count($appliedStatusFilter) + count($appliedDepartmentFilter)" class="badge-primary badge-sm" />
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
                        <button wire:click="updateSort('joining_date', 'desc')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-all hover:bg-base-200">
                            <x-mary-icon name="o-calendar" class="w-4 h-4" />
                            <span class="text-sm">Newest First</span>
                        </button>
                        <button wire:click="updateSort('salary_amount', 'desc')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-all hover:bg-base-200">
                            <x-mary-icon name="o-currency-dollar" class="w-4 h-4" />
                            <span class="text-sm">Highest Salary</span>
                        </button>
                    </div>
                </div>
            </x-mary-dropdown>
        </x-slot:actions>
    </x-mary-header>

    {{-- Employee Modal --}}
    <x-mary-modal wire:model="myModal"
        title="{{ $isEdit ? 'Edit Employee' : 'Create Employee' }}"
        subtitle="{{ $isEdit ? 'Update employee details' : 'Add a new employee to the system' }}"
        box-class="backdrop-blur max-w-3xl">

        <x-mary-form no-separator>
            {{-- Company Selection (New Field) --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Company Assignment</h3>
                <x-mary-select
                    label="Company *"
                    wire:model="company_profile_id"
                    :options="$companyOptions"
                    option-value="id"
                    option-label="name"
                    placeholder="Select a company..."
                    icon="o-building-office"
                    hint="Select which company this employee belongs to" />
            </div>

            {{-- Employee Details --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Personal Information --}}
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Personal Information</h3>

                    <x-mary-input label="Full Name *" icon="o-user" wire:model="name" placeholder="Enter full name" />

                    <x-mary-input label="Email" icon="o-envelope" type="email" wire:model="email" placeholder="employee@company.com" />

                    <div class="grid grid-cols-2 gap-4">
                        <x-mary-input label="Phone *" icon="o-phone" wire:model="phone" placeholder="+91 9876543210" />
                        <x-mary-input label="Alternate Phone" icon="o-device-phone-mobile" wire:model="alternate_phone" placeholder="+91 9876543211" />
                    </div>

                    <x-mary-textarea label="Address" icon="o-map-pin" wire:model="address" placeholder="Complete address" rows="2" />

                    <div class="grid grid-cols-3 gap-4">
                        <x-mary-input label="City" wire:model="city" placeholder="City" />
                        <x-mary-input label="State" wire:model="state" placeholder="State" />
                        <x-mary-input label="Postal Code" wire:model="postal_code" placeholder="123456" />
                    </div>
                </div>

                {{-- Employment Information --}}
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Employment Information</h3>

                    <x-mary-input label="Position" icon="o-briefcase" wire:model="position" placeholder="Job title/position" />

                    <x-mary-input label="Department" icon="o-building-office" wire:model="department" placeholder="Department name" />

                    <x-mary-input label="Joining Date *" type="date" icon="o-calendar" wire:model="joining_date" />

                    <x-mary-input label="Salary Amount *" type="number" step="0.01" icon="o-currency-dollar" wire:model="salary_amount" placeholder="0.00" />

                    <x-mary-toggle label="Active Status" wire:model="is_active" />
                </div>
            </div>

            {{-- File Uploads --}}
            <div class="mt-6">
                <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Documents & Photo</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <x-mary-file
                            label="Employee Photo"
                            wire:model="photo"
                            accept="image/*"
                            hint="Upload employee photo (max 2MB)" />
                    </div>
                    <div>
                        <x-mary-file
                            label="Document (ID Proof)"
                            wire:model="document"
                            accept="image/*,.pdf"
                            hint="Upload ID proof (max 5MB)" />
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div class="mt-6">
                <x-mary-textarea label="Notes" icon="o-pencil-square" wire:model="notes" placeholder="Additional notes about the employee..." rows="3" />
            </div>

            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.cancel" />
                <x-mary-button
                    label="{{ $isEdit ? 'Update Employee' : 'Create Employee' }}"
                    class="btn-primary"
                    @click="$wire.saveEmployee"
                    spinner="saveEmployee" />
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
            <x-mary-button icon="o-plus" label="Add Employee" class="btn-primary" @click="$wire.newEmployee" />
        </div>
        <x-mary-hr />

        <x-mary-table
            :headers="$headers"
            :rows="$employees"
            striped
            :sort-by="$sortBy"
            per-page="perPage"
            :row-decoration="$row_decoration"
            :per-page-values="[5, 10, 20, 50]"
            with-pagination
            show-empty-text
            empty-text="No employees found!"
            wire:model.live="selected"
            selectable>

            @scope('cell_company', $row)
            <div class="text-sm">
                <div class="font-medium">{{ $row->company->name ?? 'N/A' }}</div>
                @if($row->company && $row->company->legal_name)
                <div class="text-xs text-gray-500">{{ $row->company->legal_name }}</div>
                @endif
            </div>
            @endscope

            @scope('cell_photo', $row)
            <div class="avatar">
                <div class="w-12 h-12 rounded-full">
                    <img src="{{ $row->photo_url }}" alt="{{ $row->name }}" class="object-cover w-full h-full rounded-full" />
                </div>
            </div>
            @endscope

            @scope('cell_employee_id', $row)
            <x-mary-badge :value="$row->employee_id" class="badge-outline badge-sm font-mono" />
            @endscope

            @scope('cell_name', $row)
            <div>
                <div class="font-semibold">{{ $row->name }}</div>
                @if($row->email)
                <div class="text-xs text-gray-500">{{ $row->email }}</div>
                @endif
            </div>
            @endscope

            @scope('cell_phone', $row)
            <div class="text-sm">
                <div>{{ $row->phone }}</div>
                @if($row->alternate_phone)
                <div class="text-xs text-gray-500">{{ $row->alternate_phone }}</div>
                @endif
            </div>
            @endscope

            @scope('cell_position', $row)
            {{ $row->position ?: 'N/A' }}
            @endscope

            @scope('cell_department', $row)
            @if($row->department)
            <x-mary-badge :value="$row->department" class="badge-outline badge-sm" />
            @else
            <span class="text-gray-400">N/A</span>
            @endif
            @endscope

            @scope('cell_salary_amount', $row)
            <span class="font-mono font-semibold">₹{{ number_format($row->salary_amount, 2) }}</span>
            @endscope

            @scope('cell_is_active', $row)
            <x-mary-badge
                :value="$row->is_active ? 'Active' : 'Inactive'"
                :class="$row->is_active ? 'badge-success' : 'badge-error'" />
            @endscope

            @scope('cell_joining_date', $row)
            {{ $row->joining_date->format('d/m/Y') }}
            @endscope

            @scope('cell_actions', $row)
            <div class="flex gap-2 justify-center items-center">
                <x-mary-button
                    icon="o-eye"
                    spinner
                    class="btn-circle btn-ghost btn-xs"
                    tooltip-left="View Profile"
                    link="/employees/{{ $row->id }}/profile" />
                <x-mary-button
                    icon="o-pencil"
                    spinner
                    class="btn-circle btn-ghost btn-xs"
                    tooltip-left="Edit"
                    @click="$wire.editEmployee({{ $row->id }})" />
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

        @if($employeeToDelete)
        <div class="space-y-4">
            <div class="p-4 bg-base-100 rounded-lg border">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-error/10 rounded-full flex items-center justify-center">
                        <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6 text-error" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-base-content">Delete Employee</h3>
                        <p class="text-sm text-base-content/70">
                            Are you sure you want to delete "<strong>{{ $employeeToDelete->name }}</strong>"?
                        </p>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning">
                <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
                <div>
                    <h4 class="font-semibold">Warning</h4>
                    <p class="text-sm">This action cannot be undone. All employee data and payment history will be permanently deleted.</p>
                </div>
            </div>
        </div>
        @endif

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.closeDeleteModal()" />
            <x-mary-button
                label="Delete Employee"
                class="btn-error"
                spinner="deleteEmployee"
                @click="$wire.deleteEmployee()" />
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
            {{-- Company Filter (New) --}}
            <div>
                <label class="block text-sm font-medium mb-2">Company</label>
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

            <div>
                <label class="block text-sm font-medium mb-2">Department</label>
                <x-mary-choices
                    wire:model="departmentFilter"
                    :options="$departmentOptions"
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