<div>
    <x-mary-header title="Invoice Payments" subtitle="Track and manage all invoice payments" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                {{-- Company Selection --}}
                @if(count($companyOptions) > 1)
                <x-mary-select
                    wire:model.live="selectedCompanyId"
                    :options="$companyOptions"
                    option-value="id"
                    option-label="name"
                    placeholder="Select Company..."
                    icon="o-building-office"
                    class="w-48" />
                @endif

                <x-mary-input
                    icon="o-magnifying-glass"
                    placeholder="Search payments..."
                    wire:model.live.debounce.300ms="search"
                    class="w-64" />
            </div>
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button icon="o-plus" label="Record Payment" class="btn-primary btn-sm"
                @click="$wire.openPaymentModal()" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Company Info Banner --}}
    @if($selectedCompanyId)
    @php
    $selectedCompany = collect($companyOptions)->firstWhere('id', $selectedCompanyId);
    @endphp
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <x-mary-icon name="o-building-office" class="w-6 h-6 text-blue-600" />
            </div>
            <div>
                <h3 class="font-semibold text-blue-900">{{ $selectedCompany['name'] ?? 'Company' }}</h3>
                <p class="text-sm text-blue-600">Viewing payments for this company</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Statistics Cards --}}
    @if($selectedCompanyId)
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-currency-dollar" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Amount</div>
                    <div class="text-xl font-bold text-gray-800">₹{{ number_format($totalAmount, 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-document-text" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Payments</div>
                    <div class="text-xl font-bold text-gray-800">{{ $totalPayments }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-purple-50 to-purple-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-calendar" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">This Month</div>
                    <div class="text-xl font-bold text-gray-800">₹{{ number_format($thisMonthAmount, 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-orange-50 to-orange-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-calculator" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Average Payment</div>
                    <div class="text-xl font-bold text-gray-800">₹{{ number_format($avgPaymentAmount, 2) }}</div>
                </div>
            </div>
        </x-mary-card>
    </div>
    @endif

    {{-- Filters --}}
    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <x-mary-select
                label="Client"
                wire:model.live="clientFilter"
                :options="$clients"
                option-value="id"
                option-label="name"
                placeholder="All Clients" />

            <x-mary-select
                label="Payment Method"
                wire:model.live="paymentMethodFilter"
                :options="$paymentMethods"
                option-value="value"
                option-label="label"
                placeholder="All Methods" />

            <x-mary-input
                label="From Date"
                type="date"
                wire:model.live="dateFrom" />

            <x-mary-input
                label="To Date"
                type="date"
                wire:model.live="dateTo" />

            <x-mary-select
                label="Per Page"
                wire:model.live="perPage"
                :options="[
                    ['value' => 10, 'label' => '10'],
                    ['value' => 15, 'label' => '15'],
                    ['value' => 25, 'label' => '25'],
                    ['value' => 50, 'label' => '50']
                ]"
                option-value="value"
                option-label="label" />

            <div class="flex items-end">
                <x-mary-button label="Clear Filters" class="btn-outline w-full"
                    wire:click="$set('clientFilter', ''); $set('paymentMethodFilter', ''); $set('dateFrom', ''); $set('dateTo', '')" />
            </div>
        </div>
    </x-mary-card>

    {{-- Payments Table --}}
    <x-mary-card>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Payment Ref</th>
                        <th>Invoice</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Processed By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td>
                            <x-mary-badge :value="$payment->payment_reference" class="badge-outline badge-sm font-mono" />
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <x-mary-badge :value="$payment->invoice->invoice_number" class="badge-info badge-sm" />
                                <div class="text-xs text-gray-500 mt-1">
                                    Status: <span class="capitalize">{{ str_replace('_', ' ', $payment->invoice->payment_status) }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <div class="font-semibold">{{ $payment->invoice->client->name }}</div>
                                <div class="text-xs text-gray-500">{{ $payment->invoice->client->client_id }}</div>
                                @if($payment->invoice->client->company_name)
                                <div class="text-xs text-gray-500">{{ $payment->invoice->client->company_name }}</div>
                                @endif
                            </div>
                        </td>
                        <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td class="font-mono font-semibold text-green-600">₹{{ number_format($payment->amount, 2) }}</td>
                        <td>
                            <x-mary-badge :value="$payment->payment_method_label" class="badge-info badge-sm" />
                        </td>
                        <td class="text-sm">{{ $payment->reference_number ?: 'N/A' }}</td>
                        <td class="text-sm">{{ $payment->creator->name }}</td>
                        <td>
                            <div class="flex gap-1">
                                <x-mary-button
                                    icon="o-eye"
                                    class="btn-circle btn-ghost btn-xs"
                                    tooltip="View Payment"
                                    @click="$wire.viewPayment({{ $payment->id }})" />

                                <x-mary-button
                                    icon="o-arrow-uturn-left"
                                    class="btn-circle btn-ghost btn-xs text-error"
                                    tooltip="Reverse Payment"
                                    @click="$wire.openReverseModal({{ $payment->id }})" />
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-8">
                            <x-mary-icon name="o-credit-card" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                            <div class="text-lg font-medium text-gray-600 mb-2">No payments found</div>
                            <div class="text-gray-500">Try adjusting your search criteria or record a new payment</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $payments->links() }}
        </div>
    </x-mary-card>

    {{-- Record Payment Modal --}}
    <x-mary-modal wire:model="showPaymentModal"
        title="Record Invoice Payment"
        box-class="backdrop-blur max-w-lg">

        <div class="space-y-4">
            <x-mary-select
                label="Invoice *"
                wire:model.live="selectedInvoiceId"
                :options="$invoiceOptions"
                option-value="id"
                option-label="label"
                placeholder="Select invoice..."
                hint="Only unpaid invoices are shown" />

            <x-mary-input
                label="Payment Amount *"
                type="number"
                step="0.01"
                min="0.01"
                prefix="₹"
                wire:model="paymentAmount"
                placeholder="0.00" />

            <x-mary-input
                label="Payment Date *"
                type="date"
                wire:model="paymentDate" />

            <x-mary-select
                label="Payment Method *"
                wire:model="paymentMethod"
                :options="$paymentMethods"
                option-value="value"
                option-label="label" />

            <x-mary-input
                label="Reference Number"
                wire:model="paymentReference"
                placeholder="Transaction ID, Cheque Number, etc." />

            <x-mary-textarea
                label="Payment Notes"
                wire:model="paymentNotes"
                placeholder="Additional notes..."
                rows="3" />
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.closePaymentModal()" />
            <x-mary-button
                label="Record Payment"
                class="btn-primary"
                spinner="recordPayment"
                @click="$wire.recordPayment()" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- View Payment Modal --}}
    <x-mary-modal wire:model="showViewModal"
        :title="$viewingPayment ? 'Payment Details: ' . $viewingPayment->payment_reference : 'Payment Details'"
        box-class="backdrop-blur max-w-3xl">

        @if($viewingPayment)
        <div class="space-y-6">
            {{-- Payment Information --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <h4 class="font-semibold text-green-800 mb-3">Payment Information</h4>
                    <div class="space-y-2">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Payment Reference</label>
                            <p class="font-mono">{{ $viewingPayment->payment_reference }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Amount</label>
                            <p class="text-lg font-bold text-green-600">₹{{ number_format($viewingPayment->amount, 2) }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Payment Date</label>
                            <p class="font-medium">{{ $viewingPayment->payment_date->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Payment Method</label>
                            <p class="font-medium">{{ $viewingPayment->payment_method_label }}</p>
                        </div>
                        @if($viewingPayment->reference_number)
                        <div>
                            <label class="text-sm font-medium text-gray-600">Reference Number</label>
                            <p class="font-medium">{{ $viewingPayment->reference_number }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h4 class="font-semibold text-blue-800 mb-3">Invoice Information</h4>
                    <div class="space-y-2">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Invoice Number</label>
                            <p class="font-mono">{{ $viewingPayment->invoice->invoice_number }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Client</label>
                            <p class="font-medium">{{ $viewingPayment->invoice->client->name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Invoice Total</label>
                            <p class="font-bold">₹{{ number_format($viewingPayment->invoice->total_amount, 2) }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Amount Paid</label>
                            <p class="font-bold text-green-600">₹{{ number_format($viewingPayment->invoice->paid_amount, 2) }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Outstanding</label>
                            <p class="font-bold text-red-600">₹{{ number_format($viewingPayment->invoice->outstanding_amount, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Processing Information --}}
            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <h4 class="font-semibold text-gray-800 mb-3">Processing Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Processed By</label>
                        <p class="font-medium">{{ $viewingPayment->creator->name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Processed On</label>
                        <p class="font-medium">{{ $viewingPayment->created_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            @if($viewingPayment->notes)
            <div>
                <h4 class="font-semibold text-gray-800 mb-3">Notes</h4>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-gray-700 whitespace-pre-line">{{ $viewingPayment->notes }}</p>
                </div>
            </div>
            @endif
        </div>
        @endif

        <x-slot:actions>
            @if($viewingPayment)
            <x-mary-button
                label="Reverse Payment"
                class="btn-error"
                @click="$wire.openReverseModal({{ $viewingPayment->id }})" />
            @endif
            <x-mary-button label="Close" @click="$wire.closeViewModal()" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- Reverse Payment Modal --}}
    <x-mary-modal wire:model="showReverseModal"
        :title="$reversingPayment ? 'Reverse Payment: ' . $reversingPayment->payment_reference : 'Reverse Payment'"
        box-class="backdrop-blur max-w-lg">

        @if($reversingPayment)
        <div class="space-y-4">
            {{-- Warning --}}
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center gap-3">
                    <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6 text-red-600" />
                    <div>
                        <h4 class="font-semibold text-red-800">Warning: Payment Reversal</h4>
                        <p class="text-sm text-red-700">This action will reverse the payment and update all related ledger entries. This cannot be undone.</p>
                    </div>
                </div>
            </div>

            {{-- Payment Details --}}
            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <h4 class="font-semibold text-gray-800 mb-2">Payment Details</h4>
                <div class="space-y-1 text-sm">
                    <div><strong>Payment Reference:</strong> {{ $reversingPayment->payment_reference }}</div>
                    <div><strong>Invoice:</strong> {{ $reversingPayment->invoice->invoice_number }}</div>
                    <div><strong>Client:</strong> {{ $reversingPayment->invoice->client->name }}</div>
                    <div><strong>Amount:</strong> ₹{{ number_format($reversingPayment->amount, 2) }}</div>
                    <div><strong>Date:</strong> {{ $reversingPayment->payment_date->format('d/m/Y') }}</div>
                </div>
            </div>

            {{-- Reason --}}
            <x-mary-textarea
                label="Reason for Reversal *"
                wire:model="reverseReason"
                placeholder="Please provide a detailed reason for reversing this payment..."
                rows="4"
                hint="This reason will be recorded in the payment history and audit logs" />
        </div>
        @endif

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.closeReverseModal()" />
            <x-mary-button
                label="Reverse Payment"
                class="btn-error"
                spinner="reversePayment"
                @click="$wire.reversePayment()" />
        </x-slot:actions>
    </x-mary-modal>
</div>