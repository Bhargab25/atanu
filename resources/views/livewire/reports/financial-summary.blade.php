{{-- Financial Summary Report --}}
<div class="space-y-6">
    {{-- Key Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-arrow-trending-up" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Net Profit</div>
                    <div class="text-xl font-bold {{ $reportData['summary']['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        ₹{{ number_format(abs($reportData['summary']['net_profit']), 2) }}
                    </div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-banknotes" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Available</div>
                    <div class="text-xl font-bold text-blue-600">
                        ₹{{ number_format($reportData['summary']['total_available'], 2) }}
                    </div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-red-50 to-red-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-receipt-percent" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Expenses</div>
                    <div class="text-xl font-bold text-red-600">
                        ₹{{ number_format($reportData['summary']['total_expenses'], 2) }}
                    </div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-purple-50 to-purple-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-users" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Employee Payments</div>
                    <div class="text-xl font-bold text-purple-600">
                        ₹{{ number_format($reportData['summary']['employee_payments'], 2) }}
                    </div>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Detailed Breakdown --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Cash & Bank Summary --}}
        <x-mary-card>
            <x-mary-header title="Cash & Bank Balances" subtitle="Available funds breakdown" />
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <x-mary-icon name="o-banknotes" class="w-5 h-5 text-green-600" />
                        <span class="font-medium">Cash in Hand</span>
                    </div>
                    <span class="font-bold text-green-600">₹{{ number_format($reportData['summary']['cash_balance'], 2) }}</span>
                </div>
                
                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <x-mary-icon name="o-building-library" class="w-5 h-5 text-blue-600" />
                        <span class="font-medium">Bank Balance</span>
                    </div>
                    <span class="font-bold text-blue-600">₹{{ number_format($reportData['summary']['bank_balance'], 2) }}</span>
                </div>
                
                <div class="border-t pt-3">
                    <div class="flex justify-between items-center">
                        <span class="font-semibold">Total Available</span>
                        <span class="font-bold text-lg">₹{{ number_format($reportData['summary']['total_available'], 2) }}</span>
                    </div>
                </div>
            </div>
        </x-mary-card>

        {{-- Outstanding Balances --}}
        <x-mary-card>
            <x-mary-header title="Outstanding Balances" subtitle="Receivables and payables" />
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <x-mary-icon name="o-user-group" class="w-5 h-5 text-yellow-600" />
                        <span class="font-medium">Client Balances</span>
                    </div>
                    <span class="font-bold text-yellow-600">₹{{ number_format($reportData['summary']['client_balances'], 2) }}</span>
                </div>
                
                <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <x-mary-icon name="o-users" class="w-5 h-5 text-orange-600" />
                        <span class="font-medium">Employee Balances</span>
                    </div>
                    <span class="font-bold text-orange-600">₹{{ number_format(abs($reportData['summary']['employee_balances']), 2) }}</span>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Expenses by Category --}}
    @if(!empty($reportData['expenses_by_category']))
        <x-mary-card>
            <x-mary-header title="Expenses by Category" subtitle="Category-wise expense breakdown" />
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($reportData['expenses_by_category'] as $categoryName => $categoryData)
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-semibold text-gray-900">{{ $categoryName }}</h4>
                        <p class="text-2xl font-bold text-red-600">₹{{ number_format($categoryData['total'], 2) }}</p>
                        <p class="text-sm text-gray-600">{{ $categoryData['count'] }} transactions</p>
                    </div>
                @endforeach
            </div>
        </x-mary-card>
    @endif

    {{-- Monthly Trends --}}
    @if(!empty($reportData['monthly_trends']))
        <x-mary-card>
            <x-mary-header title="Monthly Trends" subtitle="6-month financial overview" />
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Expenses</th>
                            <th>Employee Payments</th>
                            <th>Total Outflow</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['monthly_trends'] as $trend)
                            <tr>
                                <td class="font-medium">{{ $trend['month'] }}</td>
                                <td class="text-red-600">₹{{ number_format($trend['expenses'], 2) }}</td>
                                <td class="text-purple-600">₹{{ number_format($trend['employee_payments'], 2) }}</td>
                                <td class="font-bold">₹{{ number_format($trend['total_outflow'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    @endif
</div>
