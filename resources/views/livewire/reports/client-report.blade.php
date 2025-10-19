<div class="space-y-6">
    {{-- Client Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Total Clients</div>
                <div class="text-xl font-bold text-blue-600">{{ $reportData['total_clients'] }}</div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Total Receivables</div>
                <div class="text-xl font-bold text-green-600">₹{{ number_format($reportData['total_receivables'], 2) }}</div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-purple-50 to-purple-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Active Clients</div>
                <div class="text-xl font-bold text-purple-600">{{ $reportData['clients']->where('is_active', true)->count() }}</div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-orange-50 to-orange-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Payments Received</div>
                <div class="text-xl font-bold text-orange-600">₹{{ number_format(collect($reportData['client_payments'])->sum('total_paid'), 2) }}</div>
            </div>
        </x-mary-card>
    </div>

    {{-- Client Balances --}}
    @if(!empty($reportData['client_ledgers']))
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-scale" class="w-5 h-5 text-blue-600" />
                    Client Account Balances
                </div>
            </x-slot:title>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Client ID</th>
                            <th>Company Name</th>
                            <th>Account Balance</th>
                            <th>Status</th>
                            <th>Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['client_ledgers'] as $ledger)
                            @if($ledger->ledgerable)
                                <tr>
                                    <td class="font-medium">{{ $ledger->ledgerable->name }}</td>
                                    <td class="font-mono text-sm">{{ $ledger->ledgerable->client_id }}</td>
                                    <td class="text-sm">{{ $ledger->ledgerable->company_name ?? 'N/A' }}</td>
                                    <td class="font-mono {{ $ledger->current_balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        ₹{{ number_format(abs($ledger->current_balance), 2) }}
                                        {{ $ledger->current_balance >= 0 ? '(Receivable)' : '(Payable)' }}
                                    </td>
                                    <td>
                                        <x-mary-badge 
                                            :value="$ledger->ledgerable->is_active ? 'Active' : 'Inactive'"
                                            :class="$ledger->ledgerable->is_active ? 'badge-success' : 'badge-error' . ' badge-sm'" />
                                    </td>
                                    <td class="text-sm">
                                        {{ $ledger->ledgerable->phone }}<br>
                                        <span class="text-gray-500">{{ $ledger->ledgerable->email }}</span>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    @endif

    {{-- Client Invoice Summary --}}
    @if(!empty($reportData['clients']))
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-document-text" class="w-5 h-5 text-green-600" />
                    Client Invoice Summary
                </div>
            </x-slot:title>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Total Invoices</th>
                            <th>Invoice Amount</th>
                            <th>Amount Paid</th>
                            <th>Outstanding</th>
                            <th>Payment Ratio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['clients'] as $client)
                            @php
                                $outstanding = $client->invoices_sum_total_amount - $client->invoices_sum_paid_amount;
                                $paymentRatio = $client->invoices_sum_total_amount > 0 
                                    ? ($client->invoices_sum_paid_amount / $client->invoices_sum_total_amount) * 100 
                                    : 0;
                            @endphp
                            <tr>
                                <td class="font-medium">
                                    {{ $client->name }}
                                    @if($client->company_name)
                                        <br><span class="text-sm text-gray-500">{{ $client->company_name }}</span>
                                    @endif
                                </td>
                                <td>{{ $client->invoices_count ?? 0 }}</td>
                                <td class="font-mono">₹{{ number_format($client->invoices_sum_total_amount ?? 0, 2) }}</td>
                                <td class="font-mono text-green-600">₹{{ number_format($client->invoices_sum_paid_amount ?? 0, 2) }}</td>
                                <td class="font-mono {{ $outstanding > 0 ? 'text-red-600' : 'text-gray-600' }}">
                                    ₹{{ number_format($outstanding, 2) }}
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="w-full bg-gray-200 rounded-full h-2 max-w-20">
                                            <div class="bg-green-600 h-2 rounded-full" style="width: {{ $paymentRatio }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium">{{ number_format($paymentRatio, 1) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    @endif

    {{-- Recent Payments by Client --}}
    @if(!empty($reportData['client_payments']))
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-currency-dollar" class="w-5 h-5 text-purple-600" />
                    Recent Payments by Client
                </div>
            </x-slot:title>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Total Paid</th>
                            <th>Payment Count</th>
                            <th>Average Payment</th>
                            <th>Last Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['client_payments'] as $clientName => $paymentData)
                            <tr>
                                <td class="font-medium">{{ $clientName }}</td>
                                <td class="font-mono text-green-600">₹{{ number_format($paymentData['total_paid'], 2) }}</td>
                                <td>{{ $paymentData['payment_count'] }}</td>
                                <td class="font-mono">₹{{ number_format($paymentData['total_paid'] / max($paymentData['payment_count'], 1), 2) }}</td>
                                <td class="text-sm text-gray-600">
                                    {{ \Carbon\Carbon::parse($dateFrom)->format('M d') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    @endif

    {{-- Client Analysis --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Top Paying Clients --}}
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-star" class="w-5 h-5 text-yellow-600" />
                    Top Paying Clients
                </div>
            </x-slot:title>
            
            <div class="space-y-3">
                @php
                    $topPayingClients = collect($reportData['client_payments'])
                        ->sortByDesc('total_paid')
                        ->take(5);
                @endphp
                
                @foreach($topPayingClients as $clientName => $paymentData)
                    <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                        <div>
                            <div class="font-medium">{{ $clientName }}</div>
                            <div class="text-sm text-gray-600">{{ $paymentData['payment_count'] }} payments</div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-semibold text-yellow-600">₹{{ number_format($paymentData['total_paid'], 2) }}</div>
                        </div>
                    </div>
                @endforeach
                
                @if($topPayingClients->count() === 0)
                    <div class="text-center py-4 text-gray-500">
                        No payment data available for this period
                    </div>
                @endif
            </div>
        </x-mary-card>

        {{-- Clients with Outstanding Balances --}}
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 text-red-600" />
                    Outstanding Receivables
                </div>
            </x-slot:title>
            
            <div class="space-y-3">
                @php
                    $outstandingClients = $reportData['client_ledgers']
                        ->where('current_balance', '>', 0)
                        ->sortByDesc('current_balance')
                        ->take(5);
                @endphp
                
                @foreach($outstandingClients as $ledger)
                    @if($ledger->ledgerable)
                        <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                            <div>
                                <div class="font-medium">{{ $ledger->ledgerable->name }}</div>
                                <div class="text-sm text-gray-600">{{ $ledger->ledgerable->client_id }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-mono font-semibold text-red-600">₹{{ number_format($ledger->current_balance, 2) }}</div>
                                <div class="text-xs text-gray-500">Outstanding</div>
                            </div>
                        </div>
                    @endif
                @endforeach
                
                @if($outstandingClients->count() === 0)
                    <div class="text-center py-4 text-gray-500">
                        No outstanding receivables
                    </div>
                @endif
            </div>
        </x-mary-card>
    </div>
</div>
