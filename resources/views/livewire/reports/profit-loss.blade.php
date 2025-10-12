{{-- Profit & Loss Report --}}
<div class="space-y-6">
    {{-- Revenue Section --}}
    <x-mary-card>
        <x-mary-header title="Revenue" subtitle="Income sources for the selected period" />
        <div class="space-y-3">
            <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                <span class="font-medium">Total Revenue</span>
                <span class="font-bold text-green-600">₹{{ number_format($reportData['revenue']['total'], 2) }}</span>
            </div>
            {{-- Add more revenue sources as needed --}}
            <div class="text-sm text-gray-600 p-3 bg-blue-50 rounded-lg">
                <x-mary-icon name="o-information-circle" class="w-4 h-4 inline mr-2" />
                Revenue tracking can be enhanced by integrating with your invoicing system
            </div>
        </div>
    </x-mary-card>

    {{-- Expenses Section --}}
    <x-mary-card>
        <x-mary-header title="Expenses" subtitle="Operating expenses breakdown" />
        <div class="space-y-3">
            @foreach($reportData['expenses'] as $categoryName => $amount)
                <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                    <span class="font-medium">{{ $categoryName }}</span>
                    <span class="font-bold text-red-600">₹{{ number_format($amount, 2) }}</span>
                </div>
            @endforeach
            
            <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                <span class="font-medium">Employee Costs</span>
                <span class="font-bold text-purple-600">₹{{ number_format($reportData['employee_costs'], 2) }}</span>
            </div>
            
            <div class="border-t pt-3">
                <div class="flex justify-between items-center p-3 bg-gray-100 rounded-lg">
                    <span class="font-semibold">Total Expenses</span>
                    <span class="font-bold text-red-600">₹{{ number_format($reportData['total_expenses'], 2) }}</span>
                </div>
            </div>
        </div>
    </x-mary-card>

    {{-- Profit Summary --}}
    <x-mary-card>
        <x-mary-header title="Profit Summary" subtitle="Net profit calculation" />
        <div class="space-y-4">
            <div class="flex justify-between items-center p-4 bg-green-50 rounded-lg">
                <span class="font-medium">Total Revenue</span>
                <span class="font-bold text-green-600">₹{{ number_format($reportData['revenue']['total'], 2) }}</span>
            </div>
            
            <div class="flex justify-between items-center p-4 bg-red-50 rounded-lg">
                <span class="font-medium">Total Expenses</span>
                <span class="font-bold text-red-600">₹{{ number_format($reportData['total_expenses'], 2) }}</span>
            </div>
            
            <div class="border-t-2 pt-4">
                <div class="flex justify-between items-center p-4 {{ $reportData['gross_profit'] >= 0 ? 'bg-green-100' : 'bg-red-100' }} rounded-lg">
                    <span class="font-bold text-lg">Net Profit/Loss</span>
                    <span class="font-bold text-xl {{ $reportData['gross_profit'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        ₹{{ number_format(abs($reportData['gross_profit']), 2) }}
                        @if($reportData['gross_profit'] < 0) (Loss) @endif
                    </span>
                </div>
            </div>
        </div>
    </x-mary-card>
</div>
