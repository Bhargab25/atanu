<div class="space-y-6">
    <x-mary-card>
        <x-slot:title>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-chart-bar-square" class="w-5 h-5 text-blue-600" />
                    Profit & Loss Statement
                </div>
                <div class="text-sm text-gray-500">
                    {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
                </div>
            </div>
        </x-slot:title>

        <div class="space-y-6">
            {{-- Revenue Section --}}
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Revenue</h3>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Sales Revenue</span>
                        <span class="font-mono">₹{{ number_format($reportData['revenue']['sales_revenue'], 2) }}</span>
                    </div>
                    @if($reportData['revenue']['other_income'] > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Other Income</span>
                        <span class="font-mono">₹{{ number_format($reportData['revenue']['other_income'], 2) }}</span>
                    </div>
                    @endif
                </div>
                <div class="border-t pt-2 mt-3">
                    <div class="flex justify-between items-center font-semibold">
                        <span>Total Revenue</span>
                        <span class="text-green-600 font-mono">₹{{ number_format($reportData['revenue']['total'], 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Expenses Section --}}
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Operating Expenses</h3>
                <div class="space-y-2">
                    @foreach($reportData['expenses'] as $category => $amount)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">{{ $category }}</span>
                        <span class="font-mono">₹{{ number_format($amount, 2) }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="border-t pt-2 mt-3">
                    <div class="flex justify-between items-center font-semibold">
                        <span>Total Operating Expenses</span>
                        <span class="text-red-600 font-mono">₹{{ number_format($reportData['total_operating_expenses'], 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Profit Calculations --}}
            <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b">
                    <span class="font-semibold text-lg">Gross Profit</span>
                    <span class="font-mono text-lg {{ $reportData['gross_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        ₹{{ number_format($reportData['gross_profit'], 2) }}
                    </span>
                </div>

                <div class="flex justify-between items-center py-2 border-b bg-gray-50 px-4 rounded">
                    <span class="font-bold text-xl">Net Profit</span>
                    <span class="font-mono text-xl font-bold {{ $reportData['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        ₹{{ number_format($reportData['net_profit'], 2) }}
                    </span>
                </div>
            </div>

            {{-- Profit Margins --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600">Gross Profit Margin</div>
                    <div class="text-2xl font-bold text-blue-600">{{ $reportData['gross_profit_margin'] }}%</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600">Net Profit Margin</div>
                    <div class="text-2xl font-bold text-green-600">{{ $reportData['net_profit_margin'] }}%</div>
                </div>
            </div>
        </div>
    </x-mary-card>
</div>