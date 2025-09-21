<div>
    <x-mary-header title="Employee Payments" subtitle="Manage and track all employee salary payments" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
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

    {{-- Filters --}}
    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-mary-select
                label="Employee"
                wire:model.live="employeeFilter"
                :options="$employees->map(fn($emp) => ['value' => $emp->id, 'label' => $emp->name . ' (' . $emp->employee_id . ')'])"
                option-value="value"
                option-label="label"
                placeholder="All employees" />

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
                        <td colspan="10" class="text-center py-8">
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