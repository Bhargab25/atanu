<div class="space-y-6">
    {{-- Cash Flow Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Cash Inflows</div>
                <div class="text-xl font-bold text-green-600">₹{{ number_format($reportData['operating_cash_flows']['inflows'], 2) }}</div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-red-50 to-red-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Cash Outflows</div>
                <div class="text-xl font-bold text-red-600">₹{{ number_format($reportData['operating_cash_flows']['outflows'], 2) }}</div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Net Cash Flow</div>
                <div class="text-xl font-bold {{ $reportData['operating_cash_flows']['net'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    ₹{{ number_format($reportData['operating_cash_flows']['net'], 2) }}
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-purple-50 to-purple-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Closing Balance</div>
                <div class="text-xl font-bold text-purple-600">₹{{ number_format($reportData['closing_balance'], 2) }}</div>
            </div>
        </x-mary-card>
    </div>

    {{-- Cash Flow by Transaction Type --}}
    @if(!empty($reportData['transactions_by_type']))
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-arrow-path" class="w-5 h-5 text-blue-600" />
                    Cash Flow by Transaction Type
                </div>
            </x-slot:title>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Transaction Type</th>
                            <th>Inflows</th>
                            <th>Outflows</th>
                            <th>Net Flow</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['transactions_by_type'] as $type => $data)
                            <tr>
                                <td class="font-medium capitalize">{{ str_replace('_', ' ', $type) }}</td>
                                <td class="font-mono text-green-600">₹{{ number_format($data['inflows'], 2) }}</td>
                                <td class="font-mono text-red-600">₹{{ number_format($data['outflows'], 2) }}</td>
                                <td class="font-mono {{ ($data['inflows'] - $data['outflows']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ₹{{ number_format($data['inflows'] - $data['outflows'], 2) }}
                                </td>
                                <td>{{ $data['count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    @endif

    {{-- Recent Transactions --}}
    @if(!empty($reportData['transactions']) && $reportData['transactions']->count() > 0)
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-list-bullet" class="w-5 h-5 text-gray-600" />
                    Recent Cash Transactions
                </div>
            </x-slot:title>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Ledger</th>
                            <th>Inflow</th>
                            <th>Outflow</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['transactions']->take(20) as $transaction)
                            <tr>
                                <td>{{ $transaction->date->format('M d, Y') }}</td>
                                <td class="max-w-xs truncate">{{ $transaction->description }}</td>
                                <td>
                                    <x-mary-badge :value="ucfirst($transaction->type)" 
                                        class="badge-outline badge-sm" />
                                </td>
                                <td class="text-sm">{{ $transaction->ledger->ledger_name }}</td>
                                <td class="font-mono text-green-600">
                                    @if($transaction->debit_amount > 0)
                                        ₹{{ number_format($transaction->debit_amount, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="font-mono text-red-600">
                                    @if($transaction->credit_amount > 0)
                                        ₹{{ number_format($transaction->credit_amount, 2) }}
                                    @else
                                        -
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
