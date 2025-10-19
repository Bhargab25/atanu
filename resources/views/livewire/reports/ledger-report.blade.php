<div class="space-y-6">
    {{-- Ledger Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Total Debits</div>
                <div class="text-xl font-bold text-green-600">₹{{ number_format($reportData['total_debits'], 2) }}</div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-red-50 to-red-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Total Credits</div>
                <div class="text-xl font-bold text-red-600">₹{{ number_format($reportData['total_credits'], 2) }}</div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Net Difference</div>
                <div class="text-xl font-bold {{ ($reportData['total_debits'] - $reportData['total_credits']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    ₹{{ number_format(abs($reportData['total_debits'] - $reportData['total_credits']), 2) }}
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-purple-50 to-purple-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Trial Balance</div>
                <div class="text-xl font-bold {{ abs($reportData['total_trial_debits'] - $reportData['total_trial_credits']) < 0.01 ? 'text-green-600' : 'text-red-600' }}">
                    {{ abs($reportData['total_trial_debits'] - $reportData['total_trial_credits']) < 0.01 ? 'Balanced' : 'Unbalanced' }}
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Filters --}}
    <x-mary-card>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-mary-select
                label="Ledger Type"
                wire:model.live="ledgerTypeFilter"
                :options="[
                    ['id' => 'cash', 'name' => 'Cash'],
                    ['id' => 'bank', 'name' => 'Bank'],
                    ['id' => 'client', 'name' => 'Client'],
                    ['id' => 'employee', 'name' => 'Employee'],
                    ['id' => 'expenses', 'name' => 'Expenses'],
                    ['id' => 'income', 'name' => 'Income'],
                ]"
                option-value="id"
                option-label="name"
                placeholder="All Ledger Types" />

            <div class="flex items-end">
                <x-mary-button label="Clear Filters" class="btn-outline w-full" 
                    wire:click="$set('ledgerTypeFilter', '')" />
            </div>
        </div>
    </x-mary-card>

    {{-- Ledger Type Breakdown --}}
    @if(!empty($reportData['ledger_breakdown']))
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-chart-pie" class="w-5 h-5 text-blue-600" />
                    Transactions by Ledger Type
                </div>
            </x-slot:title>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Ledger Type</th>
                            <th>Total Debits</th>
                            <th>Total Credits</th>
                            <th>Net Balance</th>
                            <th>Transaction Count</th>
                            <th>Transaction Types</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['ledger_breakdown'] as $ledgerType => $data)
                            <tr>
                                <td class="font-medium capitalize">{{ str_replace('_', ' ', $ledgerType) }}</td>
                                <td class="font-mono text-green-600">₹{{ number_format($data['total_debits'], 2) }}</td>
                                <td class="font-mono text-red-600">₹{{ number_format($data['total_credits'], 2) }}</td>
                                <td class="font-mono {{ ($data['total_debits'] - $data['total_credits']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ₹{{ number_format(abs($data['total_debits'] - $data['total_credits']), 2) }}
                                    {{ ($data['total_debits'] - $data['total_credits']) >= 0 ? 'Dr' : 'Cr' }}
                                </td>
                                <td>{{ $data['count'] }}</td>
                                <td>
                                    @if(isset($data['by_transaction_type']))
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($data['by_transaction_type'] as $transType => $transData)
                                                <x-mary-badge 
                                                    :value="ucfirst($transType) . ' (' . $transData['count'] . ')'"
                                                    class="badge-outline badge-xs" />
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    @endif

    {{-- Trial Balance --}}
    @if(!empty($reportData['trial_balance']))
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-scale" class="w-5 h-5 text-purple-600" />
                        Trial Balance
                    </div>
                    <div class="text-sm {{ abs($reportData['total_trial_debits'] - $reportData['total_trial_credits']) < 0.01 ? 'text-green-600' : 'text-red-600' }}">
                        {{ abs($reportData['total_trial_debits'] - $reportData['total_trial_credits']) < 0.01 ? '✓ Balanced' : '⚠ Unbalanced' }}
                    </div>
                </div>
            </x-slot:title>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Ledger Account</th>
                            <th>Ledger Type</th>
                            <th>Debit Balance</th>
                            <th>Credit Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['trial_balance'] as $balance)
                            <tr>
                                <td class="font-medium">{{ $balance['ledger_name'] }}</td>
                                <td>
                                    <x-mary-badge 
                                        :value="ucfirst(str_replace('_', ' ', $balance['ledger_type']))"
                                        class="badge-info badge-sm" />
                                </td>
                                <td class="font-mono {{ $balance['debit_balance'] > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $balance['debit_balance'] > 0 ? '₹' . number_format($balance['debit_balance'], 2) : '-' }}
                                </td>
                                <td class="font-mono {{ $balance['credit_balance'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                    {{ $balance['credit_balance'] > 0 ? '₹' . number_format($balance['credit_balance'], 2) : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 font-semibold">
                            <td colspan="2">TOTAL</td>
                            <td class="font-mono text-green-600">₹{{ number_format($reportData['total_trial_debits'], 2) }}</td>
                            <td class="font-mono text-red-600">₹{{ number_format($reportData['total_trial_credits'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Balance Verification --}}
            <div class="mt-4 p-4 rounded-lg {{ abs($reportData['total_trial_debits'] - $reportData['total_trial_credits']) < 0.01 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }} border">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="{{ abs($reportData['total_trial_debits'] - $reportData['total_trial_credits']) < 0.01 ? 'o-check-circle' : 'o-exclamation-triangle' }}" 
                        class="w-5 h-5 {{ abs($reportData['total_trial_debits'] - $reportData['total_trial_credits']) < 0.01 ? 'text-green-600' : 'text-red-600' }}" />
                    <span class="font-semibold {{ abs($reportData['total_trial_debits'] - $reportData['total_trial_credits']) < 0.01 ? 'text-green-800' : 'text-red-800' }}">
                        @if(abs($reportData['total_trial_debits'] - $reportData['total_trial_credits']) < 0.01)
                            Trial Balance is Balanced - Books are in order
                        @else
                            Trial Balance Difference: ₹{{ number_format(abs($reportData['total_trial_debits'] - $reportData['total_trial_credits']), 2) }}
                        @endif
                    </span>
                </div>
            </div>
        </x-mary-card>
    @endif

    {{-- Recent Transactions --}}
    @if(!empty($reportData['transactions']) && $reportData['transactions']->count() > 0)
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-list-bullet" class="w-5 h-5 text-gray-600" />
                    Recent Ledger Transactions
                </div>
            </x-slot:title>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Ledger Account</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['transactions']->take(100) as $transaction)
                            <tr>
                                <td>{{ $transaction->date->format('M d, Y') }}</td>
                                <td class="font-medium">{{ $transaction->ledger->ledger_name }}</td>
                                <td class="max-w-xs truncate">{{ $transaction->description }}</td>
                                <td>
                                    <x-mary-badge 
                                        :value="ucfirst($transaction->type)"
                                        class="badge-outline badge-sm" />
                                </td>
                                <td class="font-mono text-green-600">
                                    {{ $transaction->debit_amount > 0 ? '₹' . number_format($transaction->debit_amount, 2) : '-' }}
                                </td>
                                <td class="font-mono text-red-600">
                                    {{ $transaction->credit_amount > 0 ? '₹' . number_format($transaction->credit_amount, 2) : '-' }}
                                </td>
                                <td class="font-mono text-sm">{{ $transaction->reference ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    @endif
</div>
