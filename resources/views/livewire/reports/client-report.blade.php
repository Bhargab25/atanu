{{-- Client Report --}}
<div class="space-y-6">
    {{-- Client Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-user-group" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Clients</div>
                    <div class="text-xl font-bold text-blue-600">{{ $reportData['clients']->count() }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-currency-dollar" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Receivables</div>
                    <div class="text-xl font-bold text-green-600">₹{{ number_format($reportData['total_receivables'], 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-yellow-50 to-yellow-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-calculator" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Average Balance</div>
                    <div class="text-xl font-bold text-yellow-600">
                        ₹{{ $reportData['clients']->count() > 0 ? number_format($reportData['total_receivables'] / $reportData['clients']->count(), 2) : '0.00' }}
                    </div>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Client Balances --}}
    @if($reportData['client_ledgers']->count() > 0)
        <x-mary-card>
            <x-mary-header title="Client Balances" subtitle="Outstanding amounts from clients" />
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($reportData['client_ledgers'] as $ledger)
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="avatar">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                    <x-mary-icon name="o-user" class="w-5 h-5 text-blue-600" />
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 text-sm">{{ $ledger->ledger_name }}</h4>
                                <p class="text-xs text-gray-500">{{ $ledger->ledgerable->client_id ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <p class="text-lg font-bold {{ $ledger->current_balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ₹{{ number_format(abs($ledger->current_balance), 2) }}
                            <span class="text-sm font-normal">{{ $ledger->current_balance >= 0 ? 'Receivable' : 'Credit' }}</span>
                        </p>
                    </div>
                @endforeach
            </div>
        </x-mary-card>
    @endif

    {{-- Client Details --}}
    <x-mary-card>
        <x-mary-header title="Client Details" subtitle="Complete client information" />
        
        @if($reportData['clients']->count() > 0)
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Client ID</th>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Services</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['clients'] as $client)
                            <tr>
                                <td>
                                    <x-mary-badge :value="$client->client_id" class="badge-outline badge-sm font-mono" />
                                </td>
                                <td>
                                    <div class="font-semibold">{{ $client->name }}</div>
                                    @if($client->email)
                                        <div class="text-xs text-gray-500">{{ $client->email }}</div>
                                    @endif
                                </td>
                                <td>{{ $client->company_name ?: 'Individual' }}</td>
                                <td>
                                    <div class="text-sm">
                                        <div>{{ $client->phone }}</div>
                                        @if($client->alternate_phone)
                                            <div class="text-xs text-gray-500">{{ $client->alternate_phone }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @php
                                            $services = $client->services_items ? array_keys($client->services_items) : [];
                                            $serviceNames = \App\Models\ProductCategory::whereIn('id', $services)->pluck('name');
                                        @endphp
                                        @foreach($serviceNames as $serviceName)
                                            <x-mary-badge :value="$serviceName" class="badge-info badge-xs" />
                                        @endforeach
                                        @if($serviceNames->isEmpty())
                                            <span class="text-gray-400 text-xs">No services</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <x-mary-badge 
                                        :value="$client->is_active ? 'Active' : 'Inactive'"
                                        :class="$client->is_active ? 'badge-success' : 'badge-error'" />
                                </td>
                                <td>{{ $client->created_at->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <x-mary-icon name="o-user-group" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <h3 class="text-lg font-medium text-gray-600 mb-2">No Clients Found</h3>
                <p class="text-gray-500">No clients found for this company.</p>
            </div>
        @endif
    </x-mary-card>
</div>
