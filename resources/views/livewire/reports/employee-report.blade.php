{{-- Employee Report --}}
<div class="space-y-6">
    {{-- Employee Payment Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-mary-card class="bg-gradient-to-r from-purple-50 to-purple-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-currency-dollar" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Payments</div>
                    <div class="text-xl font-bold text-purple-600">₹{{ number_format($reportData['total_amount'], 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-document-text" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Transactions</div>
                    <div class="text-xl font-bold text-blue-600">{{ $reportData['total_count'] }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-calculator" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Average Payment</div>
                    <div class="text-xl font-bold text-green-600">
                        ₹{{ $reportData['total_count'] > 0 ? number_format($reportData['total_amount'] / $reportData['total_count'], 2) : '0.00' }}
                    </div>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Employee Breakdown --}}
    @if(!empty($reportData['employee_breakdown']))
        <x-mary-card>
            <x-mary-header title="Employee Breakdown" subtitle="Payment summary by employee" />
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($reportData['employee_breakdown'] as $employeeName => $employeeData)
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-semibold text-gray-900 mb-2">{{ $employeeName }}</h4>
                        <div class="space-y-1">
                            <p class="text-2xl font-bold text-purple-600">₹{{ number_format($employeeData['total'], 2) }}</p>
                            <p class="text-sm text-gray-600">{{ $employeeData['count'] }} payments</p>
                            <p class="text-xs text-gray-500">Avg: ₹{{ number_format($employeeData['avg'], 2) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-mary-card>
    @endif

    {{-- Filters --}}
    <x-mary-card>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-mary-select
                label="Employee"
                wire:model.live="employeeFilter"
                :options="$employees->map(fn($emp) => ['value' => $emp->id, 'label' => $emp->name . ' (' . $emp->employee_id . ')'])"
                option-value="value"
                option-label="label"
                placeholder="All Employees" />

            <x-mary-select
                label="Status"
                wire:model.live="statusFilter"
                :options="[
                    ['value' => '', 'label' => 'All Status'],
                    ['value' => 'paid', 'label' => 'Paid'],
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'cancelled', 'label' => 'Cancelled']
                ]"
                option-value="value"
                option-label="label" />

            <div class="flex items-end">
                <x-mary-button label="Clear Filters" class="btn-outline w-full" 
                    wire:click="$set('employeeFilter', ''); $set('statusFilter', '')" />
            </div>
        </div>
    </x-mary-card>

    {{-- Payment Details --}}
    <x-mary-card>
        <x-mary-header title="Payment Details" subtitle="Complete payment transactions" />
        
        @if($reportData['payments']->count() > 0)
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
                            <th>Status</th>
                            <th>Processed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['payments'] as $payment)
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
                                <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $payment->month_year)->format('M Y') }}</td>
                                <td class="font-mono font-semibold text-purple-600">₹{{ number_format($payment->amount, 2) }}</td>
                                <td>
                                    <x-mary-badge :value="ucfirst(str_replace('_', ' ', $payment->payment_method))" class="badge-info badge-sm" />
                                </td>
                                <td>
                                    <x-mary-badge 
                                        :value="ucfirst($payment->status)"
                                        :class="$payment->status === 'paid' ? 'badge-success' : ($payment->status === 'pending' ? 'badge-warning' : 'badge-error')" />
                                </td>
                                <td class="text-sm">{{ $payment->creator->name ?? 'System' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <x-mary-icon name="o-users" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <h3 class="text-lg font-medium text-gray-600 mb-2">No Employee Payments Found</h3>
                <p class="text-gray-500">No employee payments found for the selected criteria.</p>
            </div>
        @endif
    </x-mary-card>
</div>
