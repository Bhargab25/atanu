<div>
    <x-mary-header title="Invoice Management" subtitle="Create and manage client invoices" separator>
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
                    placeholder="Search invoices..."
                    wire:model.live.debounce.300ms="search"
                    class="w-64" />
            </div>
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button icon="o-plus" label="Create Invoice" class="btn-primary btn-sm"
                @click="$wire.openInvoiceModal()" />
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
                <p class="text-sm text-blue-600">Viewing invoices for this company</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Statistics Cards --}}
    @if($selectedCompanyId)
    @php
    $totalInvoices = \App\Models\Invoice::forCompany($selectedCompanyId)->count();
    $totalAmounts = \App\Models\Invoice::forCompany($selectedCompanyId)->sum('total_amount');
    $paidAmount = \App\Models\Invoice::forCompany($selectedCompanyId)->where('payment_status', 'paid')->sum('total_amount');
    $unpaidAmount = \App\Models\Invoice::forCompany($selectedCompanyId)->where('payment_status', 'unpaid')->sum('total_amount');
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-document-text" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Invoices</div>
                    <div class="text-xl font-bold text-gray-800">{{ $totalInvoices }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-currency-dollar" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Amount</div>
                    <div class="text-xl font-bold text-gray-800">₹{{ number_format($totalAmounts, 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-emerald-50 to-emerald-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-emerald-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-check-circle" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Paid Amount</div>
                    <div class="text-xl font-bold text-gray-800">₹{{ number_format($paidAmount, 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-red-50 to-red-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-exclamation-circle" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Unpaid Amount</div>
                    <div class="text-xl font-bold text-gray-800">₹{{ number_format($unpaidAmount, 2) }}</div>
                </div>
            </div>
        </x-mary-card>
    </div>
    @endif

    {{-- Filters --}}
    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <x-mary-select
                label="Status"
                wire:model.live="statusFilter"
                :options="[
                    ['value' => '', 'label' => 'All Status'],
                    ['value' => 'draft', 'label' => 'Draft'],
                    ['value' => 'sent', 'label' => 'Sent'],
                    ['value' => 'paid', 'label' => 'Paid'],
                    ['value' => 'overdue', 'label' => 'Overdue'],
                    ['value' => 'cancelled', 'label' => 'Cancelled']
                ]"
                option-value="value"
                option-label="label" />

            <x-mary-select
                label="Payment Status"
                wire:model.live="paymentStatusFilter"
                :options="[
                    ['value' => '', 'label' => 'All Payment Status'],
                    ['value' => 'unpaid', 'label' => 'Unpaid'],
                    ['value' => 'partially_paid', 'label' => 'Partially Paid'],
                    ['value' => 'paid', 'label' => 'Paid']
                ]"
                option-value="value"
                option-label="label" />

            <x-mary-input
                label="From Date"
                type="date"
                wire:model.live="dateFrom" />

            <x-mary-input
                label="To Date"
                type="date"
                wire:model.live="dateTo" />

            <div class="flex items-end">
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
            </div>
        </div>
    </x-mary-card>

    {{-- Invoices Table --}}
    <x-mary-card>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Outstanding</th>
                        <th>Status</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr class="{{ $invoice->is_overdue ? 'bg-red-50' : '' }}">
                        <td>
                            <div class="flex flex-col">
                                <x-mary-badge :value="$invoice->invoice_number" class="badge-outline badge-sm font-mono" />
                                @if($invoice->is_overdue)
                                <span class="text-xs text-red-600 font-medium">OVERDUE</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <div class="font-semibold">{{ $invoice->client->name }}</div>
                                <div class="text-xs text-gray-500">{{ $invoice->client->client_id }}</div>
                                @if($invoice->client->company_name)
                                <div class="text-xs text-gray-500">{{ $invoice->client->company_name }}</div>
                                @endif
                            </div>
                        </td>
                        <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                        <td>
                            @if($invoice->due_date)
                            <span class="{{ $invoice->is_overdue ? 'text-red-600 font-medium' : '' }}">
                                {{ $invoice->due_date->format('d/m/Y') }}
                            </span>
                            @else
                            <span class="text-gray-400">No due date</span>
                            @endif
                        </td>
                        <td class="font-mono font-semibold">₹{{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="font-mono text-green-600">₹{{ number_format($invoice->paid_amount, 2) }}</td>
                        <td class="font-mono {{ $invoice->outstanding_amount > 0 ? 'text-red-600' : 'text-green-600' }}">
                            ₹{{ number_format($invoice->outstanding_amount, 2) }}
                        </td>
                        <td>
                            <x-mary-badge :value="ucfirst($invoice->status)" :class="$invoice->status_badge_class" />
                        </td>
                        <td>
                            <x-mary-badge :value="ucfirst(str_replace('_', ' ', $invoice->payment_status))"
                                :class="$invoice->payment_status_badge_class" />
                        </td>
                        <td>
                            <div class="flex gap-1">
                                {{-- View (Always Available for ALL statuses) --}}
                                <x-mary-button
                                    icon="o-eye"
                                    class="btn-circle btn-ghost btn-xs"
                                    tooltip="View Invoice"
                                    @click="$wire.viewInvoice({{ $invoice->id }})" />

                                @if($invoice->status === 'draft')
                                {{-- DRAFT ACTIONS --}}
                                <x-mary-button
                                    icon="o-pencil"
                                    class="btn-circle btn-ghost btn-xs text-primary"
                                    tooltip="Edit Invoice"
                                    @click="$wire.editInvoice({{ $invoice->id }})" />

                                <x-mary-button
                                    icon="o-paper-airplane"
                                    class="btn-circle btn-ghost btn-xs text-blue-600"
                                    tooltip="Mark as Sent"
                                    @click="$wire.markAsSent({{ $invoice->id }})" />

                                <x-mary-button
                                    icon="o-document-arrow-down"
                                    class="btn-circle btn-ghost btn-xs text-green-600"
                                    tooltip="Download PDF"
                                    @click="$wire.downloadInvoice({{ $invoice->id }})" />

                                <x-mary-button
                                    icon="o-trash"
                                    class="btn-circle btn-ghost btn-xs text-error"
                                    tooltip="Delete Draft"
                                    @click="$wire.deleteInvoice({{ $invoice->id }})" />

                                @elseif($invoice->status === 'cancelled')
                                {{-- CANCELLED/VOIDED ACTIONS - Limited --}}
                                <x-mary-button
                                    icon="o-document-arrow-down"
                                    class="btn-circle btn-ghost btn-xs text-green-600"
                                    tooltip="Download PDF"
                                    @click="$wire.downloadInvoice({{ $invoice->id }})" />

                                <x-mary-badge value="VOIDED" class="badge-error badge-xs ml-1" />

                                @else
                                {{-- SENT/PAID ACTIONS - Full functionality --}}
                                <x-mary-button
                                    icon="o-document-arrow-down"
                                    class="btn-circle btn-ghost btn-xs text-green-600"
                                    tooltip="Download PDF"
                                    @click="$wire.downloadInvoice({{ $invoice->id }})" />

                                @if($invoice->outstanding_amount > 0)
                                <x-mary-button
                                    icon="o-credit-card"
                                    class="btn-circle btn-ghost btn-xs text-yellow-600"
                                    tooltip="Record Payment"
                                    @click="$wire.openPaymentModal({{ $invoice->id }})" />
                                @endif

                                {{-- ADD VOID BUTTON - New functionality --}}
                                <x-mary-button
                                    icon="o-x-circle"
                                    class="btn-circle btn-ghost btn-xs text-orange-600"
                                    tooltip="Void Invoice"
                                    @click="$wire.openVoidModal({{ $invoice->id }})" />
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-8">
                            <x-mary-icon name="o-document-text" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                            <div class="text-lg font-medium text-gray-600 mb-2">No invoices found</div>
                            <div class="text-gray-500">Try adjusting your search criteria or create a new invoice</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $invoices->links() }}
        </div>
    </x-mary-card>

    {{-- Create/Edit Invoice Modal --}}
    <x-mary-modal wire:model="showInvoiceModal"
        :title="$editingInvoice ? 'Edit Invoice' : 'Create New Invoice'"
        box-class="backdrop-blur max-w-6xl">

        <div class="space-y-6">
            {{-- Company and Client Selection --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <x-mary-select
                    label="Company *"
                    wire:model.live="selectedCompanyId"
                    :options="$companyOptions"
                    option-value="id"
                    option-label="name"
                    placeholder="Select company..."
                    icon="o-building-office" />

                <x-mary-select
                    label="Client *"
                    wire:model.live="selectedClientId"
                    :options="$clientOptions"
                    option-value="id"
                    option-label="name"
                    placeholder="Select client..."
                    icon="o-user" />
            </div>

            {{-- Invoice Details --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input
                    label="Invoice Date *"
                    type="date"
                    wire:model="invoiceDate"
                    icon="o-calendar" />

                <x-mary-input
                    label="Due Date"
                    type="date"
                    wire:model="dueDate"
                    icon="o-calendar-days"
                    hint="Leave empty for 30 days from invoice date" />
            </div>

            {{-- Invoice Items --}}
            @if(!empty($invoiceItems))
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Invoice Items</h3>

                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Item</th>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoiceItems as $index => $item)
                            <tr>
                                <td class="text-sm">
                                    <x-mary-badge :value="$item['service_name']" class="badge-info badge-sm" />
                                </td>
                                <td class="text-sm font-medium">{{ $item['item_name'] }}</td>
                                <td class="text-xs text-gray-600 max-w-xs">{{ $item['description'] }}</td>
                                <td>
                                    {{-- FIX: Use wire:input for real-time updates --}}
                                    <x-mary-input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value="{{ $item['quantity'] }}"
                                        wire:input="updateItemQuantity({{ $index }}, $event.target.value)"
                                        class="w-20" />
                                </td>
                                <td>
                                    {{-- FIX: Use wire:input for real-time updates --}}
                                    <x-mary-input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        prefix="₹"
                                        value="{{ $item['unit_price'] }}"
                                        wire:input="updateItemPrice({{ $index }}, $event.target.value)"
                                        class="w-28" />
                                </td>
                                <td class="font-mono font-semibold">₹{{ number_format($item['total'], 2) }}</td>
                                <td>
                                    <x-mary-button
                                        icon="o-trash"
                                        class="btn-circle btn-ghost btn-xs text-error"
                                        @click="$wire.removeItem({{ $index }})" />
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <x-mary-textarea
                            label="Invoice Notes"
                            wire:model="notes"
                            placeholder="Additional notes..."
                            rows="3" />
                    </div>

                    <div class="space-y-3 p-4 bg-gray-50 rounded-lg">
                        <div class="flex justify-between">
                            <span class="font-medium">Subtotal:</span>
                            <span class="font-mono">₹{{ number_format($subtotal, 2) }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="font-medium">Tax Amount:</span>
                            <x-mary-input
                                type="number"
                                step="0.01"
                                min="0"
                                prefix="₹"
                                wire:model.live="taxAmount"
                                class="w-32" />
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="font-medium">Discount:</span>
                            <x-mary-input
                                type="number"
                                step="0.01"
                                min="0"
                                prefix="₹"
                                wire:model.live="discountAmount"
                                class="w-32" />
                        </div>

                        <div class="border-t pt-3">
                            <div class="flex justify-between">
                                <span class="text-lg font-bold">Total Amount:</span>
                                <span class="text-lg font-bold text-primary">₹{{ number_format($totalAmount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @elseif($selectedClientId)
            <div class="text-center py-8">
                <x-mary-icon name="o-exclamation-triangle" class="w-16 h-16 mx-auto text-yellow-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-600 mb-2">No Products/Services Found</h3>
                <p class="text-gray-500">The selected client doesn't have any products or services configured.</p>
            </div>
            @else
            <div class="text-center py-8">
                <x-mary-icon name="o-user" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <h3 class="text-lg font-medium text-gray-600 mb-2">Select a Client</h3>
                <p class="text-gray-500">Please select a client to load their products and services.</p>
            </div>
            @endif
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.closeInvoiceModal()" />
            @if(!empty($invoiceItems))
            <x-mary-button
                label="{{ $editingInvoice ? 'Update' : 'Create' }} Invoice"
                class="btn-primary"
                spinner="saveInvoice"
                @click="$wire.saveInvoice()" />
            @endif
        </x-slot:actions>
    </x-mary-modal>

    {{-- View Invoice Modal --}}
    <x-mary-modal wire:model="showViewModal"
        :title="$viewingInvoice ? 'Invoice Details: ' . $viewingInvoice->invoice_number : 'Invoice Details'"
        box-class="backdrop-blur max-w-4xl">

        @if($viewingInvoice)
        <div class="space-y-6">
            {{-- Invoice Header --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Company Info --}}
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h4 class="font-semibold text-blue-800 mb-3">From (Company)</h4>
                    <div class="space-y-1">
                        <p class="font-medium">{{ $viewingInvoice->company->name }}</p>
                        @if($viewingInvoice->company->legal_name)
                        <p class="text-sm text-gray-600">{{ $viewingInvoice->company->legal_name }}</p>
                        @endif
                        @if($viewingInvoice->company->address)
                        <p class="text-sm text-gray-600">{{ $viewingInvoice->company->address }}</p>
                        @endif
                    </div>
                </div>

                {{-- Client Info --}}
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <h4 class="font-semibold text-green-800 mb-3">To (Client)</h4>
                    <div class="space-y-1">
                        <p class="font-medium">{{ $viewingInvoice->client->name }}</p>
                        @if($viewingInvoice->client->company_name)
                        <p class="text-sm text-gray-600">{{ $viewingInvoice->client->company_name }}</p>
                        @endif
                        <p class="text-sm text-gray-600">{{ $viewingInvoice->client->phone }}</p>
                        @if($viewingInvoice->client->full_address)
                        <p class="text-sm text-gray-600">{{ $viewingInvoice->client->full_address }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Invoice Details --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Invoice Date</label>
                    <p class="font-medium">{{ $viewingInvoice->invoice_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Due Date</label>
                    <p class="font-medium {{ $viewingInvoice->is_overdue ? 'text-red-600' : '' }}">
                        {{ $viewingInvoice->due_date?->format('d/m/Y') ?? 'No due date' }}
                    </p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Status</label>
                    <div class="mt-1">
                        <x-mary-badge :value="ucfirst($viewingInvoice->status)" :class="$viewingInvoice->status_badge_class" />
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Payment Status</label>
                    <div class="mt-1">
                        <x-mary-badge :value="ucfirst(str_replace('_', ' ', $viewingInvoice->payment_status))"
                            :class="$viewingInvoice->payment_status_badge_class" />
                    </div>
                </div>
            </div>

            {{-- Invoice Items --}}
            <div>
                <h4 class="font-semibold text-gray-800 mb-3">Invoice Items</h4>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Item</th>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($viewingInvoice->invoice_items as $item)
                            <tr>
                                <td>
                                    <x-mary-badge :value="$item['service_name']" class="badge-info badge-sm" />
                                </td>
                                <td class="font-medium">{{ $item['item_name'] }}</td>
                                <td class="text-sm text-gray-600">{{ $item['description'] }}</td>
                                <td>{{ number_format($item['quantity'], 2) }}</td>
                                <td class="font-mono">₹{{ number_format($item['unit_price'], 2) }}</td>
                                <td class="font-mono font-semibold">₹{{ number_format($item['total'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Totals --}}
            <div class="flex justify-end">
                <div class="w-full max-w-sm space-y-2 p-4 bg-gray-50 rounded-lg">
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span class="font-mono">₹{{ number_format($viewingInvoice->subtotal, 2) }}</span>
                    </div>
                    @if($viewingInvoice->tax_amount > 0)
                    <div class="flex justify-between">
                        <span>Tax:</span>
                        <span class="font-mono">₹{{ number_format($viewingInvoice->tax_amount, 2) }}</span>
                    </div>
                    @endif
                    @if($viewingInvoice->discount_amount > 0)
                    <div class="flex justify-between">
                        <span>Discount:</span>
                        <span class="font-mono">-₹{{ number_format($viewingInvoice->discount_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="border-t pt-2">
                        <div class="flex justify-between font-bold">
                            <span>Total:</span>
                            <span class="font-mono">₹{{ number_format($viewingInvoice->total_amount, 2) }}</span>
                        </div>
                        @if($viewingInvoice->paid_amount > 0)
                        <div class="flex justify-between text-green-600">
                            <span>Paid:</span>
                            <span class="font-mono">₹{{ number_format($viewingInvoice->paid_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-red-600 font-medium">
                            <span>Outstanding:</span>
                            <span class="font-mono">₹{{ number_format($viewingInvoice->outstanding_amount, 2) }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Payment History --}}
            @if($viewingInvoice->payments->count() > 0)
            <div>
                <h4 class="font-semibold text-gray-800 mb-3">Payment History</h4>
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>Payment Ref</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Processed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($viewingInvoice->payments as $payment)
                            <tr>
                                <td>
                                    <x-mary-badge :value="$payment->payment_reference" class="badge-outline badge-sm font-mono" />
                                </td>
                                <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                <td class="font-mono font-semibold text-green-600">₹{{ number_format($payment->amount, 2) }}</td>
                                <td>
                                    <x-mary-badge :value="$payment->payment_method_label" class="badge-info badge-sm" />
                                </td>
                                <td class="text-sm">{{ $payment->reference_number ?: 'N/A' }}</td>
                                <td class="text-sm">{{ $payment->creator->name }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Notes --}}
            @if($viewingInvoice->notes)
            <div>
                <h4 class="font-semibold text-gray-800 mb-3">Notes</h4>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-gray-700">{{ $viewingInvoice->notes }}</p>
                </div>
            </div>
            @endif
        </div>
        @endif

        <x-slot:actions>
            @if($viewingInvoice)
            @if($viewingInvoice->status === 'draft')
            <x-mary-button
                label="Mark as Sent"
                class="btn-info"
                @click="$wire.markAsSent({{ $viewingInvoice->id }})" />
            @endif

            <x-mary-button
                label="Download PDF"
                class="btn-success"
                @click="$wire.downloadInvoice({{ $viewingInvoice->id }})" />

            @if($viewingInvoice->outstanding_amount > 0)
            <x-mary-button
                label="Record Payment"
                class="btn-warning"
                @click="$wire.openPaymentModal({{ $viewingInvoice->id }})" />
            @endif
            @endif

            <x-mary-button label="Close" @click="$wire.closeViewModal()" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- Payment Modal --}}
    <x-mary-modal wire:model="showPaymentModal"
        :title="$paymentInvoice ? 'Record Payment - ' . $paymentInvoice->invoice_number : 'Record Payment'"
        box-class="backdrop-blur max-w-lg">

        @if($paymentInvoice)
        <div class="space-y-4">
            {{-- Invoice Summary --}}
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-medium">Invoice Total:</span>
                    <span class="font-bold">₹{{ number_format($paymentInvoice->total_amount, 2) }}</span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="font-medium">Already Paid:</span>
                    <span class="font-bold text-green-600">₹{{ number_format($paymentInvoice->paid_amount, 2) }}</span>
                </div>
                <div class="flex justify-between items-center border-t pt-2">
                    <span class="font-medium">Outstanding:</span>
                    <span class="font-bold text-red-600">₹{{ number_format($paymentInvoice->outstanding_amount, 2) }}</span>
                </div>
            </div>

            {{-- Payment Form --}}
            <x-mary-input
                label="Payment Amount *"
                type="number"
                step="0.01"
                min="0.01"
                :max="$paymentInvoice->outstanding_amount"
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
        @endif

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.closePaymentModal()" />
            <x-mary-button
                label="Record Payment"
                class="btn-primary"
                spinner="processPayment"
                @click="$wire.processPayment()" />
        </x-slot:actions>
    </x-mary-modal>


    {{-- Void Invoice Modal --}}
    <x-mary-modal wire:model="showVoidModal"
        :title="$voidingInvoice ? 'Void Invoice - ' . $voidingInvoice->invoice_number : 'Void Invoice'"
        box-class="backdrop-blur max-w-2xl">

        @if($voidingInvoice)
        <div class="space-y-6">
            {{-- Warning Alert --}}
            <div class="alert alert-warning shadow-lg">
                <div>
                    <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6" />
                    <div>
                        <h3 class="font-bold">⚠️ Warning: This action will:</h3>
                        <ul class="list-disc ml-5 mt-2 text-sm space-y-1">
                            <li>Mark invoice <strong>{{ $voidingInvoice->invoice_number }}</strong> as <strong class="text-error">CANCELLED</strong></li>
                            <li>Reverse all accounting ledger entries</li>
                            @if($voidingInvoice->payments->count() > 0)
                            <li>Reverse <strong>{{ $voidingInvoice->payments->count() }}</strong> payment transaction(s) totaling <strong>₹{{ number_format($voidingInvoice->paid_amount, 2) }}</strong></li>
                            @endif
                            <li>Keep the invoice record for audit trail purposes</li>
                            <li class="text-error font-semibold">This action cannot be undone!</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Invoice Summary Card --}}
            <div class="card bg-base-200">
                <div class="card-body">
                    <h4 class="card-title text-base">Invoice Summary</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Client:</span>
                            <p class="font-semibold">{{ $voidingInvoice->client->name }}</p>
                        </div>
                        <div>
                            <span class="text-gray-600">Invoice Date:</span>
                            <p class="font-semibold">{{ $voidingInvoice->invoice_date->format('d M Y') }}</p>
                        </div>
                        <div>
                            <span class="text-gray-600">Total Amount:</span>
                            <p class="font-semibold text-lg">₹{{ number_format($voidingInvoice->total_amount, 2) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-600">Paid Amount:</span>
                            <p class="font-semibold text-lg text-green-600">₹{{ number_format($voidingInvoice->paid_amount, 2) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-600">Current Status:</span>
                            <div class="mt-1">
                                <x-mary-badge :value="ucfirst($voidingInvoice->status)" :class="$voidingInvoice->status_badge_class" />
                            </div>
                        </div>
                        <div>
                            <span class="text-gray-600">Payment Count:</span>
                            <p class="font-semibold">{{ $voidingInvoice->payments->count() }} payment(s)</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Void Reason Input --}}
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold">
                        Reason for Voiding <span class="text-error">*</span>
                    </span>
                    <span class="label-text-alt {{ strlen($voidReason) > 500 ? 'text-error' : 'text-gray-500' }}">
                        {{ strlen($voidReason) }}/500
                    </span>
                </label>
                <textarea
                    wire:model="voidReason"
                    class="textarea textarea-bordered h-24 @error('voidReason') textarea-error @enderror"
                    placeholder="Enter a detailed reason for voiding this invoice (minimum 10 characters required)..."
                    maxlength="500"></textarea>
                @error('voidReason')
                <label class="label">
                    <span class="label-text-alt text-error">
                        <x-mary-icon name="o-exclamation-circle" class="w-4 h-4 inline mr-1" />
                        {{ $message }}
                    </span>
                </label>
                @enderror
                <label class="label">
                    <span class="label-text-alt text-gray-500">
                        💡 This reason will be permanently recorded in the invoice notes for audit purposes.
                    </span>
                </label>
            </div>

            {{-- Confirmation Checkbox (Optional but recommended) --}}
            <div class="form-control">
                <label class="label cursor-pointer justify-start gap-3">
                    <input type="checkbox" class="checkbox checkbox-error" wire:model="confirmVoid" />
                    <span class="label-text">
                        I understand this action will void the invoice and reverse all accounting entries
                    </span>
                </label>
            </div>
        </div>
        @endif

        <x-slot:actions>
            <x-mary-button
                label="Cancel"
                @click="$wire.closeVoidModal()"
                wire:loading.attr="disabled" />

            <x-mary-button
                label="Void Invoice"
                class="btn-error"
                icon="o-x-circle"
                spinner="voidInvoice"
                @click="$wire.voidInvoice()"
                wire:loading.attr="disabled" />
        </x-slot:actions>
    </x-mary-modal>
</div>