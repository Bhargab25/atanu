<div>
    <x-mary-header title="Employee Payments" subtitle="Manage and track all employee salary payments" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                {{-- Company Selection --}}
                @if(count($companyOptions) > 1)
                <x-mary-select
                    wire:model.live="selectedCompanyId"
                    :options="$companyOptions"
                    option-value="id"
                    option-label="name"
                    placeholder="Select Company..."
                    icon="o-building-office"
                    class="w-48" />
                @endif

                <x-mary-input
                    icon="o-magnifying-glass"
                    placeholder="Search payments..."
                    wire:model.live.debounce.300ms="search"
                    class="w-64" />
            </div>
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button icon="o-funnel" label="Filters" class="btn-outline btn-sm" />
            <x-mary-button icon="o-document-arrow-down" label="Export" class="btn-outline btn-sm" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Company Info Banner --}}
    @if($selectedCompanyId)
    @php
    $selectedCompany = collect($companyOptions)->firstWhere('id', $selectedCompanyId);
    @endphp
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <x-mary-icon name="o-building-office" class="w-6 h-6 text-blue-600" />
            </div>
            <div>
                <h3 class="font-semibold text-blue-900">{{ $selectedCompany['name'] ?? 'Company' }}</h3>
                <p class="text-sm text-blue-600">Viewing payments for this company</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-currency-dollar" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Paid</div>
                    <div class="text-xl font-bold text-gray-800">₹{{ number_format($totalPaid, 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-calendar" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">This Month</div>
                    <div class="text-xl font-bold text-gray-800">₹{{ number_format($thisMonthPaid, 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-purple-50 to-purple-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-users" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Employees</div>
                    <div class="text-xl font-bold text-gray-800">{{ $employees->count() }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-orange-50 to-orange-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-document-text" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Transactions</div>
                    <div class="text-xl font-bold text-gray-800">{{ $payments->total() }}</div>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Employee Ledger Summary --}}
    @if($selectedCompanyId && count($employeeLedgerSummary) > 0)
    <x-mary-card class="mb-6">
        <x-mary-header title="Employee Ledger Summary" subtitle="Outstanding balances and advances" />
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @foreach($employeeLedgerSummary as $summary)
            <div class="p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center gap-3 mb-2">
                    <div class="avatar">
                        <div class="w-8 h-8 rounded-full">
                            <img src="{{ $summary['employee']->photo_url ?? '' }}" alt="{{ $summary['name'] }}" />
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 text-sm">{{ $summary['name'] }}</h4>
                        <p class="text-xs text-gray-500">{{ $summary['employee']->employee_id ?? 'N/A' }}</p>
                    </div>
                </div>
                <p class="text-lg font-bold {{ $summary['balance'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                    ₹{{ number_format(abs($summary['balance']), 2) }}
                    <span class="text-sm font-normal">{{ $summary['balance'] < 0 ? 'Outstanding' : 'Advance' }}</span>
                </p>
            </div>
            @endforeach
        </div>
    </x-mary-card>
    @endif

    {{-- Filters --}}
    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <x-mary-select
                label="Employee"
                wire:model.live="employeeFilter"
                :options="$employees->map(fn($emp) => ['value' => $emp->id, 'label' => $emp->name . ' (' . $emp->employee_id . ')'])"
                option-value="value"
                option-label="label"
                placeholder="All employees" />

            {{-- Company Filter (only show if multiple companies available) --}}
            @if(count($companyOptions) > 1)
            <x-mary-select
                label="Company"
                wire:model.live="companyFilter"
                :options="$companyOptions"
                option-value="id"
                option-label="name"
                placeholder="All Companies" />
            @endif

            <x-mary-input
                label="Month/Year"
                type="month"
                wire:model.live="monthFilter" />

            <x-mary-select
                label="Status"
                wire:model.live="statusFilter"
                :options="[
                    ['value' => 'paid', 'label' => 'Paid'],
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'cancelled', 'label' => 'Cancelled']
                ]"
                option-value="value"
                option-label="label"
                placeholder="All statuses" />

            <div class="flex items-end">
                <x-mary-button label="Clear Filters" class="btn-outline w-full" wire:click="clearFilters" />
            </div>
        </div>
    </x-mary-card>

    {{-- Payments Table --}}
    <x-mary-card>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Employee</th>
                        <th>Company</th>
                        <th>Date</th>
                        <th>Month/Year</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th>Processed By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td>
                            <x-mary-badge :value="$payment->payment_id" class="badge-outline badge-sm font-mono" />
                        </td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="avatar">
                                    <div class="w-8 h-8 rounded-full">
                                        <img src="{{ $payment->employee->photo_url }}" alt="{{ $payment->employee->name }}" />
                                    </div>
                                </div>
                                <div>
                                    <div class="font-semibold">{{ $payment->employee->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $payment->employee->employee_id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="text-sm">
                                <div class="font-medium text-blue-600">{{ $payment->employee->company->name ?? 'N/A' }}</div>
                                @if($payment->employee->company && $payment->employee->company->legal_name)
                                <div class="text-xs text-gray-500">{{ $payment->employee->company->legal_name }}</div>
                                @endif
                            </div>
                        </td>
                        <td>{{ $payment->payment_date->format('d M, Y') }}</td>
                        <td>{{ Carbon\Carbon::createFromFormat('Y-m', $payment->month_year)->format('M Y') }}</td>
                        <td class="font-mono font-semibold">₹{{ number_format($payment->amount, 2) }}</td>
                        <td>
                            <x-mary-badge
                                :value="ucfirst(str_replace('_', ' ', $payment->payment_method))"
                                class="badge-info badge-sm" />
                        </td>
                        <td>{{ $payment->reference_number ?: 'N/A' }}</td>
                        <td>
                            <x-mary-badge
                                :value="ucfirst($payment->status)"
                                :class="$payment->status === 'paid' ? 'badge-success' : ($payment->status === 'pending' ? 'badge-warning' : 'badge-error')" />
                        </td>
                        <td>{{ $payment->creator->name ?? 'System' }}</td>
                        <td>
                            <div class="flex gap-2">
                                <x-mary-button
                                    icon="o-eye"
                                    class="btn-circle btn-ghost btn-xs"
                                    tooltip="View Employee"
                                    link="/employees/{{ $payment->employee_id }}/profile" />
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-8">
                            <x-mary-icon name="o-credit-card" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                            <div class="text-lg font-medium text-gray-600 mb-2">No payments found</div>
                            <div class="text-gray-500">Try adjusting your search criteria or filters</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $payments->links() }}
        </div>
    </x-mary-card>
</div>