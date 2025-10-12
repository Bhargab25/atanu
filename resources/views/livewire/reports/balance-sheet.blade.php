{{-- Balance Sheet Report --}}
<div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Assets --}}
        <x-mary-card>
            <x-mary-header title="Assets" subtitle="What the company owns" />
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-800 border-b pb-2">Current Assets</h4>
                
                <div class="space-y-3 ml-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Cash</span>
                        <span class="font-medium">₹{{ number_format($reportData['assets']['current_assets']['cash'], 2) }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Bank</span>
                        <span class="font-medium">₹{{ number_format($reportData['assets']['current_assets']['bank'], 2) }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Accounts Receivable</span>
                        <span class="font-medium">₹{{ number_format($reportData['assets']['current_assets']['accounts_receivable'], 2) }}</span>
                    </div>
                    
                    <div class="border-t pt-2">
                        <div class="flex justify-between items-center font-semibold">
                            <span>Total Current Assets</span>
                            <span>₹{{ number_format($reportData['assets']['current_assets']['total'], 2) }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="border-t-2 pt-4">
                    <div class="flex justify-between items-center font-bold text-lg">
                        <span>Total Assets</span>
                        <span class="text-green-600">₹{{ number_format($reportData['assets']['total'], 2) }}</span>
                    </div>
                </div>
            </div>
        </x-mary-card>

        {{-- Liabilities & Equity --}}
        <x-mary-card>
            <x-mary-header title="Liabilities & Equity" subtitle="What the company owes and owns" />
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-800 border-b pb-2">Current Liabilities</h4>
                
                <div class="space-y-3 ml-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Accounts Payable</span>
                        <span class="font-medium">₹{{ number_format(abs($reportData['liabilities']['current_liabilities']['accounts_payable']), 2) }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Accrued Expenses</span>
                        <span class="font-medium">₹{{ number_format($reportData['liabilities']['current_liabilities']['accrued_expenses'], 2) }}</span>
                    </div>
                    
                    <div class="border-t pt-2">
                        <div class="flex justify-between items-center font-semibold">
                            <span>Total Current Liabilities</span>
                            <span>₹{{ number_format(abs($reportData['liabilities']['current_liabilities']['total']), 2) }}</span>
                        </div>
                    </div>
                </div>
                
                <h4 class="font-semibold text-gray-800 border-b pb-2 mt-6">Equity</h4>
                
                <div class="space-y-3 ml-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Retained Earnings</span>
                        <span class="font-medium">₹{{ number_format($reportData['equity']['retained_earnings'], 2) }}</span>
                    </div>
                    
                    <div class="border-t pt-2">
                        <div class="flex justify-between items-center font-semibold">
                            <span>Total Equity</span>
                            <span>₹{{ number_format($reportData['equity']['total'], 2) }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="border-t-2 pt-4">
                    <div class="flex justify-between items-center font-bold text-lg">
                        <span>Total Liabilities & Equity</span>
                        <span class="text-blue-600">₹{{ number_format(abs($reportData['liabilities']['total']) + $reportData['equity']['total'], 2) }}</span>
                    </div>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Balance Check --}}
    <x-mary-card>
        <div class="flex items-center justify-center p-6">
            @php
                $totalLiabilitiesEquity = abs($reportData['liabilities']['total']) + $reportData['equity']['total'];
                $balanced = abs($reportData['assets']['total'] - $totalLiabilitiesEquity) < 0.01;
            @endphp
            
            @if($balanced)
                <div class="flex items-center gap-3 text-green-600">
                    <x-mary-icon name="o-check-circle" class="w-8 h-8" />
                    <span class="text-lg font-semibold">Balance Sheet is Balanced</span>
                </div>
            @else
                <div class="flex items-center gap-3 text-red-600">
                    <x-mary-icon name="o-exclamation-triangle" class="w-8 h-8" />
                    <span class="text-lg font-semibold">Balance Sheet is Not Balanced</span>
                </div>
            @endif
        </div>
    </x-mary-card>
</div>
