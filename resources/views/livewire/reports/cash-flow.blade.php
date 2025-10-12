{{-- Cash Flow Report --}}
<div class="space-y-6">
    {{-- Cash Flow Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-arrow-down-circle" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Cash Inflows</div>
                    <div class="text-xl font-bold text-green-600">₹{{ number_format($reportData['cash_inflows'], 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-red-50 to-red-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-arrow-up-circle" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Cash Outflows</div>
                    <div class="text-xl font-bold text-red-600">₹{{ number_format($reportData['cash_outflows'], 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-scale" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Net Cash Flow</div>
                    <div class="text-xl font-bold {{ $reportData['net_cash_flow'] >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                        ₹{{ number_format(abs($reportData['net_cash_flow']), 2) }}
                        @if($reportData['net_cash_flow'] < 0) (Outflow) @endif
                    </div>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Cash Balance Summary --}}
    <x-mary-card>
        <x-mary-header title="Cash Balance Movement" subtitle="Opening to closing balance analysis" />
        <div class="space-y-4">
            <div class="flex justify-between items-center p-4 bg-blue-50 rounded-lg">
                <span class="font-medium">Opening Balance</span>
                <span class="font-bold text-blue-600">₹{{ number_format($reportData['opening_balance'], 2) }}</span>
            </div>
            
            <div class="flex justify-between items-center p-4 bg-green-50 rounded-lg">
                <span class="font-medium">Cash Inflows</span>
                <span class="font-bold text-green-600">+₹{{ number_format($reportData['cash_inflows'], 2) }}</span>
            </div>
            
            <div class="flex justify-between items-center p-4 bg-red-50 rounded-lg">
                <span class="font-medium">Cash Outflows</span>
                <span class="font-bold text-red-600">-₹{{ number_format($reportData['cash_outflows'], 2) }}</span>
            </div>
            
            <div class="border-t-2 pt-4">
                <div class="flex justify-between items-center p-4 bg-gray-100 rounded-lg">
                    <span class="font-bold text-lg">Closing Balance</span>
                    <span class="font-bold text-xl text-blue-700">₹{{ number_format($reportData['closing_balance'], 2) }}</span>
                </div>
            </div>
        </div>
    </x-mary-card>

    {{-- Cash Transactions Detail --}}
    <x-mary-card>
        <x-mary-header title="Cash Transactions" subtitle="Detailed transaction history" />
        
        @if($reportData['transactions']->count() > 0)
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Account</th>
                            <th>Inflow</th>
                            <th>Outflow</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['transactions'] as $transaction)
                            <tr>
                                <td>{{ $transaction->date->format('d/m/Y') }}</td>
                                <td class="max-w-xs truncate">{{ $transaction->description }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <x-mary-icon name="{{ $transaction->ledger->ledger_type === 'cash' ? 'o-banknotes' : 'o-building-library' }}" 
                                                     class="w-4 h-4 text-gray-500" />
                                        <span class="text-sm">{{ $transaction->ledger->ledger_name }}</span>
                                    </div>
                                </td>
                                <td class="text-green-600 font-medium">
                                    @if($transaction->debit_amount > 0)
                                        ₹{{ number_format($transaction->debit_amount, 2) }}
                                    @endif
                                </td>
                                <td class="text-red-600 font-medium">
                                    @if($transaction->credit_amount > 0)
                                        ₹{{ number_format($transaction->credit_amount, 2) }}
                                    @endif
                                </td>
                                <td class="text-sm text-gray-600">{{ $transaction->reference }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <x-mary-icon name="o-document-text" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <h3 class="text-lg font-medium text-gray-600 mb-2">No Cash Transactions</h3>
                <p class="text-gray-500">No cash transactions found for the selected period.</p>
            </div>
        @endif
        
        @if($reportData['transactions']->count() > 0)
            <div class="mt-4">
                {{-- Add pagination if needed --}}
            </div>
        @endif
    </x-mary-card>
</div>
