<div>
    {{-- Header --}}
    <x-mary-header title="Dashboard" subtitle="Business overview and key metrics" separator>
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
                
                {{-- Period Selection --}}
                <x-mary-select 
                    wire:model.live="period"
                    :options="[
                        ['id' => 'today', 'name' => 'Today'],
                        ['id' => 'this_week', 'name' => 'This Week'],
                        ['id' => 'this_month', 'name' => 'This Month'],
                        ['id' => 'this_year', 'name' => 'This Year']
                    ]"
                    option-value="id" 
                    option-label="name"
                    class="w-40" />
            </div>
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button 
                label="View Reports" 
                icon="o-document-chart-bar" 
                link="{{ route('financial-reports') }}" 
                class="btn-primary" />
        </x-slot:actions>
    </x-mary-header>

    @if($selectedCompanyId)
        {{-- Company Info Banner --}}
        @php
            $selectedCompany = collect($companyOptions)->firstWhere('id', $selectedCompanyId);
        @endphp
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                        <x-mary-icon name="o-building-office" class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-blue-900 text-lg">{{ $selectedCompany['name'] ?? 'Company' }}</h3>
                        <p class="text-sm text-blue-600">
                            Period: {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-600">Last Updated</div>
                    <div class="text-sm font-semibold">{{ now()->format('M d, Y H:i A') }}</div>
                </div>
            </div>
        </div>

        {{-- Key Financial Metrics --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Total Income --}}
            <x-mary-card class="bg-gradient-to-br from-green-50 to-emerald-50 border-green-200">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs text-gray-600 uppercase tracking-wide mb-1">Total Income</div>
                        <div class="text-2xl font-bold text-green-700">₹{{ number_format($stats['total_income'], 0) }}</div>
                        <div class="text-xs text-green-600 mt-1">{{ $stats['invoices_count'] }} invoices</div>
                    </div>
                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                        <x-mary-icon name="o-arrow-trending-up" class="w-6 h-6 text-white" />
                    </div>
                </div>
            </x-mary-card>

            {{-- Total Expenses --}}
            <x-mary-card class="bg-gradient-to-br from-red-50 to-rose-50 border-red-200">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs text-gray-600 uppercase tracking-wide mb-1">Total Expenses</div>
                        <div class="text-2xl font-bold text-red-700">₹{{ number_format($stats['total_expenses'], 0) }}</div>
                        <div class="text-xs text-red-600 mt-1">{{ $stats['expenses_count'] }} expenses</div>
                    </div>
                    <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                        <x-mary-icon name="o-arrow-trending-down" class="w-6 h-6 text-white" />
                    </div>
                </div>
            </x-mary-card>

            {{-- Net Profit --}}
            <x-mary-card class="bg-gradient-to-br from-blue-50 to-cyan-50 border-blue-200">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs text-gray-600 uppercase tracking-wide mb-1">Net Profit</div>
                        <div class="text-2xl font-bold {{ $stats['net_profit'] >= 0 ? 'text-blue-700' : 'text-red-700' }}">
                            ₹{{ number_format($stats['net_profit'], 0) }}
                        </div>
                        <div class="text-xs {{ $stats['net_profit'] >= 0 ? 'text-blue-600' : 'text-red-600' }} mt-1">
                            {{ $stats['net_profit'] >= 0 ? 'Profit' : 'Loss' }}
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                        <x-mary-icon name="o-banknotes" class="w-6 h-6 text-white" />
                    </div>
                </div>
            </x-mary-card>

            {{-- Available Cash --}}
            <x-mary-card class="bg-gradient-to-br from-purple-50 to-violet-50 border-purple-200">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs text-gray-600 uppercase tracking-wide mb-1">Available Cash</div>
                        <div class="text-2xl font-bold text-purple-700">₹{{ number_format($stats['total_available'], 0) }}</div>
                        <div class="text-xs text-purple-600 mt-1">Cash + Bank</div>
                    </div>
                    <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                        <x-mary-icon name="o-wallet" class="w-6 h-6 text-white" />
                    </div>
                </div>
            </x-mary-card>
        </div>

        {{-- Secondary Metrics --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <x-mary-card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-orange-600">₹{{ number_format($stats['outstanding_invoices'], 0) }}</div>
                    <div class="text-sm text-gray-600 mt-1">Outstanding Invoices</div>
                </div>
            </x-mary-card>

            <x-mary-card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-yellow-600">₹{{ number_format($stats['pending_expenses'], 0) }}</div>
                    <div class="text-sm text-gray-600 mt-1">Pending Expenses</div>
                </div>
            </x-mary-card>

            <x-mary-card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-indigo-600">{{ $stats['active_employees'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">Active Employees</div>
                </div>
            </x-mary-card>

            <x-mary-card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-teal-600">{{ $stats['active_clients'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">Active Clients</div>
                </div>
            </x-mary-card>
        </div>

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            {{-- Cash Flow (Last 7 Days) --}}
            <div class="lg:col-span-2">
                <x-mary-card>
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-chart-bar" class="w-5 h-5 text-blue-600" />
                            Cash Flow (Last 7 Days)
                        </div>
                    </x-slot:title>
                    
                    <div class="overflow-x-auto">
                        <table class="table table-sm w-full">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Inflows</th>
                                    <th>Outflows</th>
                                    <th>Net</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cashFlowData as $data)
                                    <tr>
                                        <td class="font-medium">{{ $data['date'] }}</td>
                                        <td class="font-mono text-green-600">₹{{ number_format($data['inflows'], 0) }}</td>
                                        <td class="font-mono text-red-600">₹{{ number_format($data['outflows'], 0) }}</td>
                                        <td class="font-mono {{ $data['net'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            ₹{{ number_format($data['net'], 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-mary-card>
            </div>

            {{-- Top Clients --}}
            <div>
                <x-mary-card>
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-star" class="w-5 h-5 text-yellow-600" />
                            Top Clients
                        </div>
                    </x-slot:title>
                    
                    <div class="space-y-3">
                        @forelse($topClients as $item)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="font-medium text-sm">{{ $item['client']->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $item['client']->client_id }}</div>
                                </div>
                                <div class="font-mono text-sm font-semibold text-green-600">
                                    ₹{{ number_format($item['revenue'], 0) }}
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-gray-500 py-4">No data available</div>
                        @endforelse
                    </div>
                </x-mary-card>
            </div>
        </div>

        {{-- Recent Activities --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Recent Invoices --}}
            <x-mary-card>
                <x-slot:title>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-document-text" class="w-5 h-5 text-blue-600" />
                            Recent Invoices
                        </div>
                        <a href="{{ route('invoices.index') }}" class="text-xs text-blue-600 hover:underline">View All</a>
                    </div>
                </x-slot:title>
                
                <div class="space-y-2">
                    @forelse($recentInvoices as $invoice)
                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                            <div class="flex-1">
                                <div class="text-sm font-medium">{{ $invoice->invoice_number }}</div>
                                <div class="text-xs text-gray-500">{{ $invoice->client->name }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-mono">₹{{ number_format($invoice->total_amount, 0) }}</div>
                                <x-mary-badge 
                                    :value="ucfirst($invoice->payment_status)"
                                    :class="match($invoice->payment_status) {
                                        'paid' => 'badge-success',
                                        'unpaid' => 'badge-error',
                                        'partially_paid' => 'badge-warning',
                                        default => 'badge-outline'
                                    } . ' badge-xs'" />
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-500 py-4 text-sm">No recent invoices</div>
                    @endforelse
                </div>
            </x-mary-card>

            {{-- Recent Expenses --}}
            <x-mary-card>
                <x-slot:title>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-receipt-percent" class="w-5 h-5 text-red-600" />
                            Recent Expenses
                        </div>
                        <a href="{{ route('expenses.index') }}" class="text-xs text-blue-600 hover:underline">View All</a>
                    </div>
                </x-slot:title>
                
                <div class="space-y-2">
                    @forelse($recentExpenses as $expense)
                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                            <div class="flex-1">
                                <div class="text-sm font-medium">{{ $expense->expense_title }}</div>
                                <div class="text-xs text-gray-500">{{ $expense->category->name }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-mono">₹{{ number_format($expense->amount, 0) }}</div>
                                <x-mary-badge 
                                    :value="ucfirst($expense->approval_status)"
                                    :class="match($expense->approval_status) {
                                        'approved' => 'badge-success',
                                        'rejected' => 'badge-error',
                                        'pending' => 'badge-warning',
                                        default => 'badge-outline'
                                    } . ' badge-xs'" />
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-500 py-4 text-sm">No recent expenses</div>
                    @endforelse
                </div>
            </x-mary-card>

            {{-- Recent Payments --}}
            <x-mary-card>
                <x-slot:title>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-currency-dollar" class="w-5 h-5 text-green-600" />
                            Recent Payments
                        </div>
                        <a href="{{ route('employees.index') }}" class="text-xs text-blue-600 hover:underline">View All</a>
                    </div>
                </x-slot:title>
                
                <div class="space-y-2">
                    @forelse($recentPayments as $payment)
                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                            <div class="flex-1">
                                <div class="text-sm font-medium">{{ $payment->employee->name }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ ucfirst($payment->payment_type ?? 'salary') }} - {{ \Carbon\Carbon::parse($payment->month_year)->format('M Y') }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-mono">₹{{ number_format($payment->amount, 0) }}</div>
                                <div class="text-xs text-gray-500">{{ $payment->payment_date->format('M d') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-500 py-4 text-sm">No recent payments</div>
                    @endforelse
                </div>
            </x-mary-card>
        </div>
    @else
        {{-- No Company Selected State --}}
        <x-mary-card>
            <div class="text-center py-12">
                <x-mary-icon name="o-building-office" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <h3 class="text-lg font-medium text-gray-600 mb-2">No Company Selected</h3>
                <p class="text-gray-500 mb-4">Please select a company to view dashboard</p>
                @if(count($companyOptions) > 0)
                    <div class="max-w-xs mx-auto">
                        <x-mary-select 
                            wire:model.live="selectedCompanyId"
                            :options="$companyOptions"
                            option-value="id" 
                            option-label="name"
                            placeholder="Select Company..."
                            icon="o-building-office" />
                    </div>
                @endif
            </div>
        </x-mary-card>
    @endif
</div>
