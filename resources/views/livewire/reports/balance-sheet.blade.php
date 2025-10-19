<div class="space-y-6">
    <x-mary-card>
        <x-slot:title>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-scale" class="w-5 h-5 text-purple-600" />
                    Balance Sheet
                </div>
                <div class="text-sm text-gray-500">
                    As of {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
                </div>
            </div>
        </x-slot:title>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Assets --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">ASSETS</h3>
                
                {{-- Current Assets --}}
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-700 mb-3">Current Assets</h4>
                    <div class="space-y-2 pl-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Cash</span>
                            <span class="font-mono">₹{{ number_format($reportData['assets']['current_assets']['cash'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Bank</span>
                            <span class="font-mono">₹{{ number_format($reportData['assets']['current_assets']['bank'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Accounts Receivable</span>
                            <span class="font-mono">₹{{ number_format($reportData['assets']['current_assets']['accounts_receivable'], 2) }}</span>
                        </div>
                        <div class="border-t pt-2 mt-2">
                            <div class="flex justify-between items-center font-semibold">
                                <span>Total Current Assets</span>
                                <span class="font-mono">₹{{ number_format($reportData['assets']['current_assets']['total'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Total Assets --}}
                <div class="border-t pt-4">
                    <div class="flex justify-between items-center text-lg font-bold">
                        <span>TOTAL ASSETS</span>
                        <span class="font-mono text-blue-600">₹{{ number_format($reportData['assets']['total'], 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Liabilities & Equity --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">LIABILITIES & EQUITY</h3>
                
                {{-- Current Liabilities --}}
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-700 mb-3">Current Liabilities</h4>
                    <div class="space-y-2 pl-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Accounts Payable</span>
                            <span class="font-mono">₹{{ number_format($reportData['liabilities']['current_liabilities']['accounts_payable'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Employee Payables</span>
                            <span class="font-mono">₹{{ number_format($reportData['liabilities']['current_liabilities']['employee_payables'], 2) }}</span>
                        </div>
                        <div class="border-t pt-2 mt-2">
                            <div class="flex justify-between items-center font-semibold">
                                <span>Total Current Liabilities</span>
                                <span class="font-mono">₹{{ number_format($reportData['liabilities']['current_liabilities']['total'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Equity --}}
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-700 mb-3">Owner's Equity</h4>
                    <div class="space-y-2 pl-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Retained Earnings</span>
                            <span class="font-mono">₹{{ number_format($reportData['equity']['retained_earnings'], 2) }}</span>
                        </div>
                        <div class="border-t pt-2 mt-2">
                            <div class="flex justify-between items-center font-semibold">
                                <span>Total Equity</span>
                                <span class="font-mono">₹{{ number_format($reportData['equity']['total'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Total Liabilities & Equity --}}
                <div class="border-t pt-4">
                    <div class="flex justify-between items-center text-lg font-bold">
                        <span>TOTAL LIABILITIES & EQUITY</span>
                        <span class="font-mono text-purple-600">₹{{ number_format($reportData['liabilities']['total'] + $reportData['equity']['total'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Balance Check --}}
        @php
            $totalAssetsBalance = $reportData['assets']['total'];
            $totalLiabilitiesEquity = $reportData['liabilities']['total'] + $reportData['equity']['total'];
            $isBalanced = abs($totalAssetsBalance - $totalLiabilitiesEquity) < 0.01;
        @endphp
        
        <div class="mt-6 p-4 rounded-lg {{ $isBalanced ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }} border">
            <div class="flex items-center gap-2">
                <x-mary-icon name="{{ $isBalanced ? 'o-check-circle' : 'o-exclamation-triangle' }}" 
                    class="w-5 h-5 {{ $isBalanced ? 'text-green-600' : 'text-red-600' }}" />
                <span class="font-semibold {{ $isBalanced ? 'text-green-800' : 'text-red-800' }}">
                    Balance Sheet {{ $isBalanced ? 'Balanced' : 'Not Balanced' }}
                </span>
            </div>
        </div>
    </x-mary-card>
</div>
