{{-- Ledger Report --}}
<div class="space-y-6">
    {{-- Ledger Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-arrow-trending-up" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Debits</div>
                    <div class="text-xl font-bold text-green-600">₹{{ number_format($reportData['total_debits'], 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-red-50 to-red-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-arrow-trending-down" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Credits</div>
                    <div class="text-xl font-bold text-red-600">₹{{ number_format($reportData['total_credits'], 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-document-text" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Transactions</div>
                    <div class="text-xl font-bold text-blue-600">{{ $reportData['transactions']->count() }}</div>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Ledger Type Breakdown --}}
    @if(!empty($reportData['ledger_breakdown']))
        <x-mary-card>
            <x-mary-header title="Ledger Type Breakdown" subtitle="Transaction summary by ledger type" />
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @foreach($reportData['ledger_breakdown'] as $ledgerType => $typeData)
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <x-mary-icon name="{{ match($ledgerType) {
                                'cash' => 'o-banknotes',
                                'bank' => 'o-building-library',
                                'employee' => 'o-users',
                                'client' => 'o-user-group',
                                'expenses' => 'o-receipt-percent',
                                default => 'o-document-text'
                            } }}" class="w-5 h-5 text-gray-600" />
                            <h4 class="font-semibold text-gray-900 capitalize">{{ str_replace('_', ' ', $ledgerType) }}</h4>
                        </div>
                        <div class="space-y-1">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Debits:</span>
                                <span class="font-medium text-green-600">₹{{ number_format($typeData['total_debits'], 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Credits:</span>
                                <span class="font-medium text-red-600">₹{{ number_format($typeData['total_credits'], 2) }}</span>
                            </div>
                            <div class="flex justify-between text-xs border-t pt-1">
                                <span class="text-gray-500">Transactions:</span>
                                <span class="font-medium">{{ $typeData['count'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-mary-card>
    @endif

    {{-- Filters --}}
    <x-mary-card>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-mary-select
                label="Ledger Type"
                wire:model.live="ledgerTypeFilter"
                :options="[
                    ['value' => '', 'label' => 'All Types'],
                    ['value' => 'cash', 'label' => 'Cash'],
                    ['value' => 'bank', 'label' => 'Bank'],
                    ['value' => 'employee', 'label' => 'Employee'],
                    ['value' => 'client', 'label' => 'Client'],
                    ['value' => 'expenses', 'label' => 'Expenses']
                ]"
                option-value="value"
                option-label="label" />

            <div class="flex items-end">
                <x-mary-button label="Clear Filters" class="btn-outline w-full" 
                    wire:click="$set('ledgerTypeFilter', '')" />
            </div>
        </div>
    </x-mary-card>

    {{-- Transaction Details --}}
    <x-mary-card>
        <x-mary-header title="Transaction Details" subtitle="Complete ledger transaction history" />
        
        @if($reportData['transactions']->count() > 0)
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Ledger</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['transactions'] as $transaction)
                            <tr>
                                <td>{{ $transaction->date->format('d/m/Y') }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <x-mary-icon name="{{ match($transaction->ledger->ledger_type) {
                                            'cash' => 'o-banknotes',
                                            'bank' => 'o-building-library',
                                            'employee' => 'o-users',
                                            'client' => 'o-user-group',
                                            'expenses' => 'o-receipt-percent',
                                            default => 'o-document-text'
                                        } }}" class="w-4 h-4 text-gray-500" />
                                        <div>
                                            <div class="font-medium text-sm">{{ $transaction->ledger->ledger_name }}</div>
                                            <div class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', $transaction->ledger->ledger_type) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <x-mary-badge :value="ucfirst($transaction->type)" class="badge-outline badge-sm" />
                                </td>
                                <td class="max-w-xs">
                                    <div class="truncate" title="{{ $transaction->description }}">{{ $transaction->description }}</div>
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
                                <td class="text-sm text-gray-600 font-mono">{{ $transaction->reference }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <x-mary-icon name="o-document-text" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <h3 class="text-lg font-medium text-gray-600 mb-2">No Transactions Found</h3>
                <p class="text-gray-500">No ledger transactions found for the selected criteria.</p>
            </div>
        @endif
    </x-mary-card>
</div>
