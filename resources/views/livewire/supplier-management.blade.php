<div>
    <x-mary-header title="Suppliers" subtitle="Manage supplier information" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                <x-mary-input
                    icon="o-magnifying-glass"
                    placeholder="Search suppliers..."
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
                @if(count($appliedStatusFilter) > 0 || count($appliedLocationFilter) > 0)
                <x-mary-badge :value="count($appliedStatusFilter) + count($appliedLocationFilter)" class="badge-primary badge-sm" />
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
                            <div class="font-semibold text-sm">Sort Suppliers</div>
                            <div class="text-xs text-base-content/60">Choose how to order your list</div>
                        </div>
                    </div>

                    <div class="space-y-1">
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

    {{-- Supplier Modal --}}
    <x-mary-modal wire:model="myModal"
        title="{{ $isEdit ? 'Edit Supplier' : 'Create Supplier' }}"
        subtitle="{{ $isEdit ? 'Update the supplier information' : 'Add a new supplier to your database' }}">
        <x-mary-form no-separator>
            {{-- Basic Information --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-base-content border-b pb-2">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-mary-input label="Supplier Name *" icon="o-building-office" placeholder="Supplier name" wire:model="name" />
                    <x-mary-input label="Contact Person" icon="o-user" placeholder="Contact person name" wire:model="contact_person" />
                    <x-mary-input label="Phone" icon="o-phone" placeholder="+91 98765 43210" wire:model="phone" />
                    <x-mary-input label="Email" icon="o-envelope" type="email" placeholder="supplier@example.com" wire:model="email" />
                </div>
            </div>

            {{-- Address Information --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-base-content border-b pb-2">Address Information</h3>
                <div class="grid grid-cols-1 gap-4">
                    <x-mary-textarea label="Address" icon="o-map-pin" placeholder="Full address" wire:model="address" />
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <x-mary-input label="City" placeholder="City" wire:model="city" />
                        <x-mary-input label="State" placeholder="State" wire:model="state" />
                        <x-mary-input label="Country" placeholder="Country" wire:model="country" />
                        <x-mary-input label="Pincode" placeholder="123456" wire:model="pincode" />
                    </div>
                </div>
            </div>

            {{-- Tax & Business Information --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-base-content border-b pb-2">Tax & Business Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-mary-input label="GSTIN" icon="o-document-text" placeholder="22AAAAA0000A1Z5" wire:model="gstin" />
                    <x-mary-input label="PAN" icon="o-identification" placeholder="AAAAA0000A" wire:model="pan" />
                    <x-mary-input label="TIN" icon="o-document" placeholder="TIN Number" wire:model="tin" />
                </div>
            </div>

            {{-- Banking Information --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-base-content border-b pb-2">Banking Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-mary-input label="Bank Name" icon="o-building-library" placeholder="Bank name" wire:model="bank_name" />
                    <x-mary-input label="Branch" icon="o-map-pin" placeholder="Branch name" wire:model="branch" />
                    <x-mary-input label="Account Number" icon="o-credit-card" placeholder="Account number" wire:model="account_number" />
                    <x-mary-input label="IFSC Code" icon="o-hashtag" placeholder="IFSC Code" wire:model="ifsc_code" />
                </div>
            </div>

            {{-- Status --}}
            <div class="mb-4">
                <x-mary-toggle label="Active Status" wire:model="is_active" />
            </div>

            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.cancel" />
                <x-mary-button
                    label="{{ $isEdit ? 'Update' : 'Create' }}"
                    class="btn-primary"
                    @click="$wire.saveSupplier"
                    spinner="saveSupplier" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    {{-- Details Modal --}}
    <x-mary-modal wire:model="showDetailsModal" box-class="max-w-5xl"
        title="{{ $selectedSupplier?->name ?? 'Supplier' }} - Complete Details & Ledger">

        @if($selectedSupplier)
        {{-- Statistics Cards --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <div class="text-2xl font-bold text-yellow-600">
                    {{ $selectedSupplier->ledger?->getFormattedBalance() ?? '₹0.00 Dr' }}
                </div>
                <div class="text-sm text-yellow-600">Current Balance</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <div class="text-2xl font-bold text-green-600">
                    ₹{{ number_format($selectedSupplier->ledger?->transactions->where('type', 'purchase')->sum('debit_amount') ?? 0, 2) }}
                </div>
                <div class="text-sm text-green-600">Total Purchases</div>
            </div>
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <div class="text-2xl font-bold text-blue-600">{{ $selectedSupplier->ledger?->transactions->count() ?? 0 }}</div>
                <div class="text-sm text-blue-600">Transactions</div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="mb-6">
            <div class="bg-gray-100 rounded-xl p-1 inline-flex">
                <button
                    wire:click="switchTab('details')"
                    class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ $activeTab === 'details' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                    Supplier Details
                </button>
                <button
                    wire:click="switchTab('ledger')"
                    class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ $activeTab === 'ledger' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                    Ledger
                </button>
                <button
                    wire:click="switchTab('transaction')"
                    class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ $activeTab === 'transaction' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                    Add Transaction
                </button>
            </div>
        </div>


        {{-- Persistent Tab Content Containers --}}
        <div class="tab-content-wrapper">
            {{-- Details Tab - Always present, controlled by CSS --}}
            <div wire:key="supplier-details-tab"
                class="space-y-6 {{ $activeTab === 'details' ? '' : 'hidden' }}">

                {{-- Row 1: Basic Information & Address --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Basic Information Card --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                        <div class="flex items-center mb-4">
                            <x-mary-icon name="o-information-circle" class="w-5 h-5 text-gray-600 mr-2" />
                            <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Name</dt>
                                <dd class="text-base font-semibold text-gray-900">{{ $selectedSupplier->name }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Contact Person</dt>
                                <dd class="text-base text-gray-900">{{ $selectedSupplier->contact_person ?? '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="text-base text-gray-900">{{ $selectedSupplier->email ?? '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                <dd class="text-base text-gray-900">{{ $selectedSupplier->phone ?? '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <x-mary-badge
                                        :value="$selectedSupplier->is_active ? 'Active' : 'Inactive'"
                                        :class="$selectedSupplier->is_active ? 'badge-success' : 'badge-error'" />
                                </dd>
                            </div>
                        </div>
                    </div>

                    {{-- Address Card --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                        <div class="flex items-center mb-4">
                            <x-mary-icon name="o-map-pin" class="w-5 h-5 text-gray-600 mr-2" />
                            <h3 class="text-lg font-semibold text-gray-900">Address</h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Address</dt>
                                <dd class="text-base text-gray-900">{{ $selectedSupplier->address ?? '-' }}</dd>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">City</dt>
                                    <dd class="text-base text-gray-900">{{ $selectedSupplier->city ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">State</dt>
                                    <dd class="text-base text-gray-900">{{ $selectedSupplier->state ?? '-' }}</dd>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Country</dt>
                                    <dd class="text-base text-gray-900">{{ $selectedSupplier->country ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Pincode</dt>
                                    <dd class="text-base text-gray-900">{{ $selectedSupplier->pincode ?? '-' }}</dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 2: Tax & Business Information & Banking Information --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Tax & Business Information Card --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                        <div class="flex items-center mb-4">
                            <x-mary-icon name="o-document-text" class="w-5 h-5 text-gray-600 mr-2" />
                            <h3 class="text-lg font-semibold text-gray-900">Tax & Business Information</h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">GSTIN</dt>
                                <dd class="text-base font-mono text-gray-900">{{ $selectedSupplier->gstin ?? '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">PAN</dt>
                                <dd class="text-base font-mono text-gray-900">{{ $selectedSupplier->pan ?? '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">TIN</dt>
                                <dd class="text-base font-mono text-gray-900">{{ $selectedSupplier->tin ?? '-' }}</dd>
                            </div>
                        </div>
                    </div>

                    {{-- Banking Information Card --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                        <div class="flex items-center mb-4">
                            <x-mary-icon name="o-building-library" class="w-5 h-5 text-gray-600 mr-2" />
                            <h3 class="text-lg font-semibold text-gray-900">Banking Information</h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Bank Name</dt>
                                <dd class="text-base text-gray-900">{{ $selectedSupplier->bank_name ?? '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Branch</dt>
                                <dd class="text-base text-gray-900">{{ $selectedSupplier->branch ?? '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Account Number</dt>
                                <dd class="text-base font-mono text-gray-900">{{ $selectedSupplier->account_number ?? '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">IFSC Code</dt>
                                <dd class="text-base font-mono text-gray-900">{{ $selectedSupplier->ifsc_code ?? '-' }}</dd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            {{-- Ledger Tab - Always present, controlled by CSS --}}
            {{-- Ledger Tab - Always present, controlled by CSS --}}
            <div wire:key="supplier-ledger-tab"
                class="space-y-6 {{ $activeTab === 'ledger' ? '' : 'hidden' }}">

                {{-- Ledger Summary Cards (keep existing) --}}
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <!-- Your existing summary cards -->
                </div>

                {{-- Filter and Download Controls --}}
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <label class="text-sm font-medium text-gray-700">Show:</label>
                        <x-mary-select
                            wire:model.live="transactionFilter"
                            :options="[
                                ['id' => '10', 'name' => 'Last 10 Transactions'],
                                ['id' => '100', 'name' => 'Last 100 Transactions'],
                                ['id' => 'month', 'name' => 'Last Month'],
                                ['id' => '3month', 'name' => 'Last 3 Months'],
                                ['id' => 'all', 'name' => 'All Transactions'],
                            ]"
                            class="w-48" />
                    </div>

                    <x-mary-button
                        wire:click="downloadLedgerPdf"
                        icon="o-arrow-down-tray"
                        class="btn-primary"
                        spinner="downloadLedgerPdf">
                        Download PDF
                    </x-mary-button>
                </div>

                {{-- Transaction History --}}
                <div>
                    <h3 class="text-lg font-semibold mb-4">Transaction History</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @php
                                $runningBalance = $selectedSupplier->ledger?->opening_balance ?? 0;
                                @endphp
                                @forelse($this->filteredTransactions as $transaction)
                                @php
                                $runningBalance += ($transaction->debit_amount - $transaction->credit_amount);
                                @endphp
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction->date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-mary-badge
                                            :value="ucfirst($transaction->type)"
                                            :class="match($transaction->type) {
                                        'purchase' => 'badge-info',
                                        'payment' => 'badge-success', 
                                        'return' => 'badge-warning',
                                        'adjustment' => 'badge-secondary',
                                        default => 'badge-ghost'
                                    }" />
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $transaction->description }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-mono text-green-600">
                                        {{ $transaction->debit_amount > 0 ? '₹' . number_format($transaction->debit_amount, 2) : '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-mono text-red-600">
                                        {{ $transaction->credit_amount > 0 ? '₹' . number_format($transaction->credit_amount, 2) : '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-mono font-bold">
                                        ₹{{ number_format(abs($runningBalance), 2) }} {{ $runningBalance >= 0 ? 'Dr' : 'Cr' }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No transactions found for selected filter.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Transaction Count Info --}}
                    <div class="mt-4 text-sm text-gray-600">
                        Showing {{ $this->filteredTransactions->count() }} transactions
                    </div>
                </div>
            </div>


            {{-- Transaction Tab - Always present, controlled by CSS --}}
            <div wire:key="supplier-transaction-tab"
                class="space-y-6 {{ $activeTab === 'transaction' ? '' : 'hidden' }}">

                <h3 class="text-lg font-semibold mb-4">Add New Transaction</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-mary-input
                        label="Date"
                        type="date"
                        wire:model="newTransaction.date"
                        required />

                    <x-mary-select
                        label="Transaction Type"
                        wire:model="newTransaction.type"
                        :options="[
                            ['id' => 'purchase', 'name' => 'Purchase'],
                            ['id' => 'payment', 'name' => 'Payment'],
                            ['id' => 'return', 'name' => 'Return'],
                            ['id' => 'adjustment', 'name' => 'Adjustment'],
                        ]"
                        option-value="id"
                        option-label="name"
                        required />

                    <div class="md:col-span-2">
                        <x-mary-input
                            label="Description"
                            wire:model="newTransaction.description"
                            placeholder="Transaction description"
                            required />
                    </div>

                    <x-mary-input
                        label="Amount"
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model="newTransaction.amount"
                        required />

                    <x-mary-input
                        label="Reference"
                        wire:model="newTransaction.reference"
                        placeholder="Invoice number, etc." />
                </div>

                <div class="flex justify-end">
                    <x-mary-button
                        label="Add Transaction"
                        class="btn-primary"
                        wire:click="addTransaction"
                        spinner="addTransaction" />
                </div>
            </div>
        </div>
        @endif

        <x-slot:actions>
            <x-mary-button label="Close" @click="$wire.showDetailsModal = false" />
        </x-slot:actions>
    </x-mary-modal>



    {{-- Confirmation Delete Modal --}}
    <x-mary-modal wire:model="showConfirmDeleteModal" title="Confirm Delete" subtitle="This action cannot be undone" size="sm">
        <div class="py-4">
            <p class="text-base-content">Are you sure you want to delete this supplier?</p>
            <p class="text-sm text-base-content/60 mt-2">This will permanently remove the supplier from your database.</p>
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.cancelDeletion" />
            <x-mary-button label="Delete" class="btn-error" @click="$wire.deleteSupplier" spinner="deleteSupplier" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- Main Content --}}
    <x-mary-card class="bg-base-200">
        <div class="flex justify-end mb-4 gap-2">
            <x-mary-button
                class="btn-secondary"
                :badge="count($selected)"
                label="Toggle Status"
                icon="o-arrow-path"
                wire:click="toggleStatus"
                spinner="toggleStatus"
                :disabled="count($selected) === 0" />
            <x-mary-button icon="o-plus" class="btn-primary" @click="$wire.newSupplier" />
        </div>
        <x-mary-hr />

        <x-mary-table
            :headers="$headers"
            :rows="$suppliers"
            striped
            :sort-by="$sortBy"
            per-page="perPage"
            :row-decoration="$row_decoration"
            :per-page-values="[5, 10, 20, 50]"
            with-pagination
            show-empty-text
            empty-text="No suppliers found!"
            wire:model.live="selected"
            selectable>
            @scope('cell_sl_no', $row)
            <!-- $startIndex -->
            <span class="font-medium">{{ $loop->index + 1 + 0 }}</span>
            @endscope

            @scope('cell_contact_person', $row)
            @if($row->contact_person)
            <span class="text-sm">{{ $row->contact_person }}</span>
            @else
            <span class="text-gray-400 text-xs">Not specified</span>
            @endif
            @endscope

            @scope('cell_phone', $row)
            @if($row->phone)
            <a href="tel:{{ $row->phone }}" class="text-sm text-blue-600 hover:underline">{{ $row->phone }}</a>
            @else
            <span class="text-gray-400 text-xs">Not specified</span>
            @endif
            @endscope

            @scope('cell_gstin', $row)
            @if($row->gstin)
            <span class="font-mono text-xs">{{ $row->gstin }}</span>
            @else
            <span class="text-gray-400 text-xs">Not specified</span>
            @endif
            @endscope

            @scope('cell_is_active', $row)
            <x-mary-badge
                :value="$row->is_active ? 'Active' : 'Inactive'"
                :class="$row->is_active ? 'badge-success badge-soft' : 'badge-error badge-soft'" />
            @endscope

            @scope('cell_actions', $row)
            <div class="flex gap-2 justify-center items-center">
                <x-mary-button
                    icon="o-eye"
                    spinner
                    class="btn-circle btn-ghost btn-xs"
                    tooltip-left="View Details"
                    @click="$wire.showDetails({{ $row->id }})" />
                <x-mary-button
                    icon="o-pencil"
                    spinner
                    class="btn-circle btn-ghost btn-xs"
                    tooltip-left="Edit"
                    @click="$wire.editSupplier({{ $row->id }})" />
                <x-mary-button
                    icon="o-trash"
                    spinner
                    class="btn-circle btn-ghost btn-xs btn-error"
                    tooltip-left="Delete"
                    @click="$wire.confirmDeletion({{ $row->id }})" />
            </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

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
                <span class="text-sm">{{ $suppliers->total() ?? 0 }} Results</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-primary rounded-full"></div>
                <span class="text-sm">{{ count($appliedStatusFilter) + count($appliedLocationFilter) }} Filters</span>
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
                <label class="block text-sm font-medium mb-2">Location</label>
                <x-mary-choices
                    wire:model="locationFilter"
                    :options="$locationOptions"
                    clearable
                    class="text-sm" />
            </div>
        </div>

        {{-- Applied Filters Preview --}}
        @if(count($appliedStatusFilter) > 0 || count($appliedLocationFilter) > 0)
        <div class="mt-4 p-2 bg-base-200 rounded text-center">
            <div class="text-xs text-base-content/60 mb-1">Applied:</div>
            <div class="flex flex-wrap gap-1 justify-center">
                @foreach($appliedStatusFilter as $filter)
                <span class="px-1.5 py-0.5 bg-primary text-primary-content text-xs rounded">
                    Status: {{ ucfirst($filter) }}
                </span>
                @endforeach
                @foreach($appliedLocationFilter as $location)
                <span class="px-1.5 py-0.5 bg-secondary text-secondary-content text-xs rounded">
                    {{ $location }}
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