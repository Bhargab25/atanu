<div class="space-y-6">
    {{-- Employee Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Total Payments</div>
                <div class="text-xl font-bold text-blue-600">₹{{ number_format($reportData['total_amount'], 2) }}</div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Payment Count</div>
                <div class="text-xl font-bold text-green-600">{{ $reportData['total_count'] }}</div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-purple-50 to-purple-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Average Payment</div>
                <div class="text-xl font-bold text-purple-600">₹{{ number_format($reportData['average_payment'], 2) }}</div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-orange-50 to-orange-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Active Employees</div>
                <div class="text-xl font-bold text-orange-600">{{ count($reportData['employee_breakdown']) }}</div>
            </div>
        </x-mary-card>
    </div>

    {{-- Filters --}}
    <x-mary-card>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-mary-select
                label="Employee"
                wire:model.live="employeeFilter"
                :options="$employees"
                option-value="id"
                option-label="name"
                placeholder="All Employees" />

            <x-mary-select
                label="Status"
                wire:model.live="statusFilter"
                :options="[
                    ['id' => 'paid', 'name' => 'Paid'],
                    ['id' => 'pending', 'name' => 'Pending'],
                    ['id' => 'cancelled', 'name' => 'Cancelled']
                ]"
                option-value="id"
                option-label="name"
                placeholder="All Status" />

            <div class="flex items-end">
                <x-mary-button label="Clear Filters" class="btn-outline w-full" 
                    wire:click="$set('employeeFilter', ''); $set('statusFilter', '')" />
            </div>
        </div>
    </x-mary-card>

    {{-- Employee Breakdown --}}
    @if(!empty($reportData['employee_breakdown']))
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-users" class="w-5 h-5 text-blue-600" />
                    Payments by Employee
                </div>
            </x-slot:title>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Total Amount</th>
                            <th>Payment Count</th>
                            <th>Average</th>
                            <th>Payment Types</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['employee_breakdown'] as $employeeName => $data)
                            <tr>
                                <td class="font-medium">{{ $employeeName }}</td>
                                <td class="font-mono">₹{{ number_format($data['total'], 2) }}</td>
                                <td>{{ $data['count'] }}</td>
                                <td class="font-mono">₹{{ number_format($data['avg'], 2) }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @if(isset($data['by_type']))
                                            @foreach($data['by_type'] as $type => $typeData)
                                                <x-mary-badge 
                                                    :value="ucfirst($type) . ' (₹' . number_format($typeData['total'], 0) . ')'"
                                                    class="badge-outline badge-xs" />
                                            @endforeach
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    @endif

    {{-- Payment Type Analysis --}}
    @if(!empty($reportData['employee_breakdown']))
        @php
            $paymentTypesSummary = [];
            foreach($reportData['employee_breakdown'] as $employee => $data) {
                if(isset($data['by_type'])) {
                    foreach($data['by_type'] as $type => $typeData) {
                        if(!isset($paymentTypesSummary[$type])) {
                            $paymentTypesSummary[$type] = ['total' => 0, 'count' => 0];
                        }
                        $paymentTypesSummary[$type]['total'] += $typeData['total'];
                        $paymentTypesSummary[$type]['count'] += $typeData['count'];
                    }
                }
            }
        @endphp
        
        @if(!empty($paymentTypesSummary))
            <x-mary-card>
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-chart-bar" class="w-5 h-5 text-green-600" />
                        Payment Types Summary
                    </div>
                </x-slot:title>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($paymentTypesSummary as $type => $data)
                        <div class="p-4 border border-gray-200 rounded-lg">
                            <div class="text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $type) }}</div>
                            <div class="text-xl font-bold text-gray-800">₹{{ number_format($data['total'], 2) }}</div>
                            <div class="text-xs text-gray-500">{{ $data['count'] }} payments</div>
                        </div>
                    @endforeach
                </div>
            </x-mary-card>
        @endif
    @endif

    {{-- Recent Payments --}}
    @if(!empty($reportData['payments']) && $reportData['payments']->count() > 0)
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-list-bullet" class="w-5 h-5 text-gray-600" />
                    Recent Employee Payments
                </div>
            </x-slot:title>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Payment Type</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Month/Year</th>
                            <th>Status</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['payments']->take(50) as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                <td class="font-medium">{{ $payment->employee->name }}</td>
                                <td>
                                    <x-mary-badge 
                                        :value="ucfirst($payment->payment_type ?? 'salary')"
                                        class="badge-info badge-sm" />
                                </td>
                                <td class="font-mono">₹{{ number_format($payment->amount, 2) }}</td>
                                <td class="text-sm">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td>{{ \Carbon\Carbon::parse($payment->month_year)->format('M Y') }}</td>
                                <td>
                                    <x-mary-badge 
                                        :value="ucfirst($payment->status)"
                                        :class="match($payment->status) {
                                            'paid' => 'badge-success',
                                            'pending' => 'badge-warning',
                                            'cancelled' => 'badge-error',
                                            default => 'badge-outline'
                                        } . ' badge-sm'" />
                                </td>
                                <td class="text-sm">{{ $payment->creator->name ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    @endif
</div>
