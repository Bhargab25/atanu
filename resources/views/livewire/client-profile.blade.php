{{-- resources/views/livewire/client-profile.blade.php --}}
<div>
    <x-mary-header title="Client Profile" subtitle="{{ $client->name }} - {{ $client->client_id }}" separator>
        <x-slot:actions>
            <x-mary-button icon="o-pencil" label="Edit Client" class="btn-outline btn-sm"
                link="/clients" />
            <x-mary-button icon="o-document-text" label="Create Invoice" class="btn-primary btn-sm"
                link="/invoices/create?client={{ $client->id }}" />
        </x-slot:actions>
    </x-mary-header>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Client Information Card --}}
        <div class="xl:col-span-1">
            <x-mary-card class="h-fit">
                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-800 border-b pb-2">Client Information</h4>

                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Client ID:</span>
                            <x-mary-badge :value="$client->client_id" class="badge-outline badge-sm" />
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Name:</span>
                            <span class="text-sm font-medium">{{ $client->name }}</span>
                        </div>

                        @if($client->company_name)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Company:</span>
                            <span class="text-sm font-medium">{{ $client->company_name }}</span>
                        </div>
                        @endif

                        @if($client->email)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Email:</span>
                            <span class="text-sm">{{ $client->email }}</span>
                        </div>
                        @endif

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Phone:</span>
                            <span class="text-sm">{{ $client->phone }}</span>
                        </div>

                        @if($client->alternate_phone)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Alt Phone:</span>
                            <span class="text-sm">{{ $client->alternate_phone }}</span>
                        </div>
                        @endif

                        @if($client->full_address)
                        <div>
                            <span class="text-sm text-gray-600">Address:</span>
                            <div class="text-sm mt-1">{{ $client->full_address }}</div>
                        </div>
                        @endif

                        @if($client->gstin)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">GSTIN:</span>
                            <span class="text-sm font-mono">{{ $client->gstin }}</span>
                        </div>
                        @endif

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Status:</span>
                            <x-mary-badge
                                :value="$client->is_active ? 'Active' : 'Inactive'"
                                :class="$client->is_active ? 'badge-success' : 'badge-error'" />
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Total Amount:</span>
                            <span class="text-lg font-bold text-primary">₹{{ number_format($client->total_amount, 2) }}</span>
                        </div>
                    </div>

                    @if($client->notes)
                    <div class="mt-4 pt-4 border-t">
                        <h5 class="text-sm font-semibold text-gray-800 mb-2">Notes</h5>
                        <p class="text-sm text-gray-600">{{ $client->notes }}</p>
                    </div>
                    @endif
                </div>
            </x-mary-card>
        </div>

        {{-- Services & Items Details --}}
        <div class="xl:col-span-2">
            <x-mary-card>
                <x-mary-header title="Services & Items Configuration" subtitle="Detailed breakdown of services and pricing">
                    <x-slot:actions>
                        <x-mary-button icon="o-document-text" label="Generate Invoice" class="btn-primary btn-sm"
                            link="/invoices/create?client={{ $client->id }}" />
                    </x-slot:actions>
                </x-mary-header>

                @if(!empty($serviceDetails))
                <div class="space-y-6">
                    @foreach($serviceDetails as $serviceDetail)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">{{ $serviceDetail['service']->name }}</h4>
                            <div class="text-right">
                                <div class="text-sm text-gray-600">Service Total</div>
                                <div class="text-lg font-bold text-primary">₹{{ number_format($serviceDetail['total'], 2) }}</div>
                            </div>
                        </div>

                        @if($serviceDetail['service']->description)
                        <p class="text-sm text-gray-600 mb-4">{{ $serviceDetail['service']->description }}</p>
                        @endif

                        @if(!empty($serviceDetail['items']))
                        <div class="overflow-x-auto">
                            <table class="table table-sm w-full">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-center">Price</th>
                                        <th class="text-center">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($serviceDetail['items'] as $item)
                                    <tr>
                                        <td>
                                            <div>
                                                <div class="font-medium">{{ $item['item_name'] }}</div>
                                                @if(!empty($item['description']))
                                                <div class="text-xs text-gray-500">{{ $item['description'] }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">{{ number_format($item['quantity'] ?? 0, 2) }}</td>
                                        <td class="text-center font-mono">₹{{ number_format($item['price'] ?? 0, 2) }}</td>
                                        <td class="text-center font-mono font-semibold">₹{{ number_format(($item['quantity'] ?? 0) * ($item['price'] ?? 0), 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-4 text-gray-500">
                            <p class="text-sm">No items configured for this service</p>
                        </div>
                        @endif
                    </div>
                    @endforeach

                    {{-- Total Summary --}}
                    <div class="bg-primary/5 border border-primary/20 rounded-lg p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold text-primary">Total Amount</h3>
                                <p class="text-sm text-gray-600">All services and items</p>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-primary">₹{{ number_format($client->total_amount, 2) }}</div>
                                <div class="text-sm text-gray-600">{{ count($serviceDetails) }} service(s)</div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="text-center py-8">
                    <x-mary-icon name="o-cog-6-tooth" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                    <h3 class="text-lg font-medium text-gray-600 mb-2">No Services Configured</h3>
                    <p class="text-gray-500 mb-4">This client doesn't have any services or items configured.</p>
                    <x-mary-button icon="o-pencil" label="Configure Services" class="btn-primary"
                        link="/clients" />
                </div>
                @endif
            </x-mary-card>
        </div>
    </div>
</div>