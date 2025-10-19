<div class="space-y-6">
    {{-- Key Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-arrow-trending-up" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Income</div>
                    <div class="text-xl font-bold text-gray-800">₹{{ number_format($reportData['summary']['total_income'], 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-red-50 to-red-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-arrow-trending-down" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Expenses</div>
                    <div class="text-xl font-bold text-gray-800">₹{{ number_format($reportData['summary']['total_expenses'], 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-banknotes" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Net Profit</div>
                    <div class="text-xl font-bold {{ $reportData['summary']['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        ₹{{ number_format($reportData['summary']['net_profit'], 2) }}
                    </div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-purple-50 to-purple-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-wallet" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Available Cash</div>
                    <div class="text-xl font-bold text-gray-800">₹{{ number_format($reportData['summary']['total_available'], 2) }}</div>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Balance Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-currency-dollar" class="w-5 h-5 text-green-600" />
                    Cash & Bank Balances
                </div>
            </x-slot:title>

            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Cash in Hand</span>
                    <span class="font-semibold">₹{{ number_format($reportData['summary']['cash_balance'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Bank Balance</span>
                    <span class="font-semibold">₹{{ number_format($reportData['summary']['bank_balance'], 2) }}</span>
                </div>
                <div class="border-t pt-2">
                    <div class="flex justify-between items-center">
                        <span class="font-medium">Total Available</span>
                        <span class="text-lg font-bold text-green-600">₹{{ number_format($reportData['summary']['total_available'], 2) }}</span>
                    </div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-users" class="w-5 h-5 text-blue-600" />
                    Outstanding Balances
                </div>
            </x-slot:title>

            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Client Receivables</span>
                    <span class="font-semibold text-blue-600">₹{{ number_format($reportData['summary']['client_receivables'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Employee Liabilities</span>
                    <span class="font-semibold text-orange-600">₹{{ number_format($reportData['summary']['employee_liabilities'], 2) }}</span>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Expenses by Category --}}
    @if(!empty($reportData['expenses_by_category']))
    <x-mary-card>
        <x-slot:title>
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-chart-pie" class="w-5 h-5 text-purple-600" />
                Expenses by Category
            </div>
        </x-slot:title>

        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Total Amount</th>
                        <th>Transactions</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $totalExpenses = collect($reportData['expenses_by_category'])->sum('total');
                    @endphp
                    @foreach($reportData['expenses_by_category'] as $category => $data)
                    @php
                    $percentage = $totalExpenses > 0 ? ($data['total'] / $totalExpenses) * 100 : 0;
                    @endphp
                    <tr>
                        <td class="font-medium">{{ $category }}</td>
                        <td class="font-mono">₹{{ number_format($data['total'], 2) }}</td>
                        <td>{{ $data['count'] }} transactions</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                                <span class="text-sm font-medium">{{ number_format($percentage, 1) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-mary-card>
    @endif

    {{-- Monthly Trends --}}
    @if(!empty($reportData['monthly_trends']))
    <x-mary-card>
        <x-slot:title>
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-chart-bar" class="w-5 h-5 text-indigo-600" />
                Monthly Trends (Last 6 Months)
            </div>
        </x-slot:title>

        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Income</th>
                        <th>Expenses</th>
                        <th>Net Profit</th>
                        <th>Trend</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['monthly_trends'] as $trend)
                    <tr>
                        <td class="font-medium">{{ $trend['month'] }}</td>
                        <td class="font-mono text-green-600">₹{{ number_format($trend['income'], 2) }}</td>
                        <td class="font-mono text-red-600">₹{{ number_format($trend['expenses'], 2) }}</td>
                        <td class="font-mono {{ $trend['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ₹{{ number_format($trend['net_profit'], 2) }}
                        </td>
                        <td>
                            @if($trend['net_profit'] >= 0)
                            <x-mary-icon name="o-arrow-trending-up" class="w-4 h-4 text-green-600" />
                            @else
                            <x-mary-icon name="o-arrow-trending-down" class="w-4 h-4 text-red-600" />
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-mary-card>
    @endif
</div>