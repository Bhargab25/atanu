{{-- resources/views/livewire/invoice-management.blade.php --}}
<div>
    <x-mary-header title="Invoice Management" subtitle="Manage invoices and billing" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                <x-mary-button icon="o-plus" label="Cash Invoice" class="btn-success" @click="$wire.openInvoiceModal('cash')" />
                <x-mary-button icon="o-plus" label="Client Invoice" class="btn-primary" @click="$wire.openInvoiceModal('client')" />
            </div>
        </x-slot:middle>
    </x-mary-header>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Total Invoices</p>
                    <p class="text-3xl font-bold">{{ number_format($totalInvoices) }}</p>
                </div>
                <x-mary-icon name="o-document-text" class="w-12 h-12 text-blue-200" />
            </div>
        </div>

        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Total Amount</p>
                    <p class="text-3xl font-bold">₹{{ number_format($totalAmount) }}</p>
                </div>
                <x-mary-icon name="o-currency-rupee" class="w-12 h-12 text-green-200" />
            </div>
        </div>

        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Paid Amount</p>
                    <p class="text-3xl font-bold">₹{{ number_format($paidAmount) }}</p>
                </div>
                <x-mary-icon name="o-check-circle" class="w-12 h-12 text-purple-200" />
            </div>
        </div>

        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm">Pending Amount</p>
                    <p class="text-3xl font-bold">₹{{ number_format($pendingAmount) }}</p>
                </div>
                <x-mary-icon name="o-exclamation-triangle" class="w-12 h-12 text-orange-200" />
            </div>
        </div>
    </div>

    <div class="mb-6">
        <div class="bg-gray-100 rounded-xl p-1 inline-flex">
            <button
                wire:click="switchTab('invoices')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ $activeTab === 'invoices' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Invoices
            </button>
            <button
                wire:click="switchTab('monthly_bills')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ $activeTab === 'monthly_bills' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Monthly Bills
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="md:col-span-2">
                <x-mary-input
                    label="Search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search invoices..."
                    icon="o-magnifying-glass" />
            </div>

            <x-mary-select
                label="Status"
                wire:model.live="statusFilter"
                :options="[
                    ['value' => '', 'label' => 'All Status'],
                    ['value' => 'paid', 'label' => 'Paid'],
                    ['value' => 'unpaid', 'label' => 'Unpaid'],
                    ['value' => 'partial', 'label' => 'Partial'],
                    ['value' => 'overdue', 'label' => 'Overdue']
                ]"
                option-value="value"
                option-label="label" />

            <x-mary-select
                label="Type"
                wire:model.live="typeFilter"
                :options="[
                    ['value' => '', 'label' => 'All Types'],
                    ['value' => 'cash', 'label' => 'Cash'],
                    ['value' => 'client', 'label' => 'Client']
                ]"
                option-value="value"
                option-label="label" />

            <x-mary-input
                label="From Date"
                wire:model.live="dateFrom"
                type="date" />

            <x-mary-input
                label="To Date"
                wire:model.live="dateTo"
                type="date" />
        </div>
    </x-mary-card>

    {{-- Add Monthly Bills Table (add this after the main invoice table) --}}
    @if($activeTab === 'monthly_bills')
    <x-mary-card>
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Monthly Bills</h3>
        </div>

        @if($monthlyBills->count() > 0)
        <x-mary-table
            :headers="[
                    ['label' => '#', 'key' => 'sl_no'],
                    ['label' => 'Bill No.', 'key' => 'bill_number'],
                    ['label' => 'Client', 'key' => 'client'],
                    ['label' => 'Period', 'key' => 'period'],
                    ['label' => 'Invoice Count', 'key' => 'count'],
                    ['label' => 'Amount', 'key' => 'amount'],
                    ['label' => 'Status', 'key' => 'status'],
                    ['label' => 'Actions', 'key' => 'actions']
                ]"
            :rows="$monthlyBills"
            striped>

            @scope('cell_sl_no', $bill)
            <span class="font-medium">{{ $loop->iteration }}</span>
            @endscope

            @scope('cell_bill_number', $bill)
            <div class="font-medium text-primary">{{ $bill->bill_number }}</div>
            <div class="text-sm text-gray-500">{{ $bill->bill_date->format('d/m/Y') }}</div>
            @endscope

            @scope('cell_client', $bill)
            <div class="font-medium">{{ $bill->client->name }}</div>
            @if($bill->client->company)
            <div class="text-sm text-gray-500">{{ $bill->client->company }}</div>
            @endif
            @endscope

            @scope('cell_period', $bill)
            <div class="text-sm">
                <div>{{ $bill->period_from->format('d/m/Y') }}</div>
                <div>to {{ $bill->period_to->format('d/m/Y') }}</div>
            </div>
            @endscope

            @scope('cell_count', $bill)
            <div class="text-center">
                <span class="badge badge-info">{{ $bill->invoice_count }}</span>
            </div>
            @endscope

            @scope('cell_amount', $bill)
            <div class="text-right font-bold">
                ₹{{ number_format($bill->total_amount, 2) }}
            </div>
            @endscope

            @scope('cell_status', $bill)
            <x-mary-badge
                :value="ucfirst($bill->status)"
                :class="$bill->status === 'paid' ? 'badge-success' : ($bill->status === 'sent' ? 'badge-warning' : 'badge-info')" />
            @endscope

            @scope('cell_actions', $bill)
            <div class="flex gap-1">
                <x-mary-button
                    icon="o-arrow-down-tray"
                    class="btn-circle btn-ghost btn-xs text-primary"
                    tooltip="Download PDF"
                    @click="window.open('{{ route('monthly-bill.download', $bill->id) }}', '_blank')" />
            </div>
            @endscope
        </x-mary-table>

        {{-- Pagination Links for Monthly Bills --}}
        <div class="mt-4">
            {{ $monthlyBills->links() }}
        </div>
        @else
        <div class="text-center py-8 text-gray-500">
            <p>No monthly bills found</p>
        </div>
        @endif
    </x-mary-card>
    @else
    {{-- Your existing invoice table --}}
    <x-mary-card>
        <x-mary-table
            :headers="[
                ['label' => '#', 'key' => 'sl_no'],
                ['label' => 'Invoice No.', 'key' => 'invoice_number'],
                ['label' => 'Date', 'key' => 'invoice_date'],
                ['label' => 'Client', 'key' => 'client'],
                ['label' => 'Type', 'key' => 'type'],
                ['label' => 'Amount', 'key' => 'amount'],
                ['label' => 'Status', 'key' => 'status'],
                ['label' => 'Actions', 'key' => 'actions']
            ]"
            :rows="$invoices"
            striped
            with-pagination>

            {{-- Your existing invoice table scopes --}}
            @scope('cell_sl_no', $invoice)
            <span class="font-medium">{{ $loop->iteration }}</span>
            @endscope

            @scope('cell_invoice_number', $invoice)
            <div class="font-medium text-primary">{{ $invoice->invoice_number }}</div>
            @if($invoice->is_gst_invoice)
            <span class="badge badge-info badge-xs">GST</span>
            @endif
            @endscope

            @scope('cell_invoice_date', $invoice)
            {{ $invoice->invoice_date->format('d/m/Y') }}
            @endscope

            @scope('cell_client', $invoice)
            <div class="font-medium">{{ $invoice->display_client_name }}</div>
            @if($invoice->client_phone)
            <div class="text-sm text-gray-500">{{ $invoice->client_phone }}</div>
            @endif
            @endscope

            @scope('cell_type', $invoice)
            <x-mary-badge
                :value="ucfirst($invoice->invoice_type)"
                :class="$invoice->invoice_type === 'client' ? 'badge-primary' : 'badge-success'" />
            @endscope

            @scope('cell_amount', $invoice)
            <div class="text-right">
                <div class="font-bold">₹{{ number_format($invoice->total_amount, 2) }}</div>
                @if($invoice->balance_amount > 0)
                <div class="text-sm text-error">Bal: ₹{{ number_format($invoice->balance_amount, 2) }}</div>
                @endif
            </div>
            @endscope

            @scope('cell_status', $invoice)
            <x-mary-badge
                :value="ucfirst($invoice->payment_status)"
                :class="$invoice->status_badge_class" />
            @if($invoice->is_monthly_billed)
            <div class="mt-1">
                <span class="badge badge-secondary badge-xs">Monthly Billed</span>
            </div>
            @endif
            @endscope

            @scope('cell_actions', $invoice)
            <div class="flex gap-1">
                <x-mary-button
                    icon="o-eye"
                    class="btn-circle btn-ghost btn-xs"
                    tooltip="View"
                    @click="$wire.viewInvoice({{ $invoice->id }})" />

                <x-mary-button
                    icon="o-arrow-down-tray"
                    class="btn-circle btn-ghost btn-xs text-primary"
                    tooltip="Download PDF"
                    @click="window.open('{{ route('invoice.download', $invoice->id) }}', '_blank')" />

                @if($invoice->invoice_type === 'client' && !$invoice->is_monthly_billed)
                <x-mary-button
                    icon="o-document-duplicate"
                    class="btn-circle btn-ghost btn-xs text-info"
                    tooltip="Monthly Bill"
                    @click="$wire.openMonthlyBillModal({{ $invoice->client_id }})" />
                @endif

                @if($invoice->balance_amount > 0)
                <x-mary-button
                    icon="o-currency-rupee"
                    class="btn-circle btn-ghost btn-xs text-success"
                    tooltip="Add Payment"
                    @click="$wire.openPaymentModal({{ $invoice->id }})" />

                <x-mary-button
                    icon="o-document-duplicate"
                    class="btn-circle btn-ghost btn-xs text-secondary"
                    tooltip="Duplicate Invoice"
                    @click="$wire.duplicateInvoice({{ $invoice->id }})" />
                
                <x-mary-button
                    icon="o-trash"
                    class="btn-circle btn-ghost btn-xs text-error"
                    tooltip="Delete Invoice"
                    @click="$wire.deleteInvoice({{ $invoice->id }})" />
                    
                @endif
            </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
    @endif

    {{-- Invoice Modal --}}
    <x-mary-modal
        wire:model="showInvoiceModal"
        title="Create {{ ucfirst($invoiceType) }} Invoice"
        box-class="backdrop-blur max-w-6xl max-h-[90vh] overflow-y-auto">

        <div class="space-y-6">
            {{-- Basic Information --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-mary-input
                    label="Invoice Date"
                    wire:model="invoiceDate"
                    type="date"
                    required />

                @if($invoiceType === 'client')
                <x-mary-input
                    label="Due Date"
                    wire:model="dueDate"
                    type="date" />
                @endif

                <div class="flex items-end">
                    <x-mary-checkbox
                        label="GST Invoice"
                        wire:model.live="isGstInvoice" />
                </div>
            </div>

            {{-- Client Information --}}
            @if($invoiceType === 'client')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-select
                    label="Client *"
                    wire:model.live="clientId"
                    :options="$clients"
                    option-value="id"
                    option-label="name"
                    placeholder="Select client"
                    required />

                @if($isGstInvoice)
                <x-mary-input
                    label="Client GSTIN"
                    wire:model="clientGstin"
                    placeholder="00XXXXXXXXX0XX" />
                @endif
            </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input
                    label="Customer Name *"
                    wire:model="clientName"
                    placeholder="Enter customer name"
                    required />

                <x-mary-input
                    label="Phone"
                    wire:model="clientPhone"
                    placeholder="Customer phone" />
            </div>
            @endif

            @if($isGstInvoice)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input
                    label="Place of Supply"
                    wire:model="placeOfSupply"
                    placeholder="State name" />

                <x-mary-select
                    label="GST Type"
                    wire:model="gstType"
                    :options="[
                            ['value' => 'cgst_sgst', 'label' => 'CGST + SGST'],
                            ['value' => 'igst', 'label' => 'IGST']
                        ]"
                    option-value="value"
                    option-label="label" />
            </div>
            @endif

            {{-- Invoice Items --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Invoice Items</h3>
                    <x-mary-button
                        icon="o-plus-circle"
                        class="btn-sm btn-secondary"
                        label="Add Item"
                        @click="$wire.addInvoiceItem()" />
                </div>

                @foreach($invoiceItems as $index => $item)
                <div class="grid grid-cols-12 gap-3 items-end p-4 bg-base-100 rounded-lg border" wire:key="item-{{ $index }}">
                    {{-- Product --}}
                    <div class="col-span-3">
                        <x-mary-select
                            label="Product *"
                            wire:model.live="invoiceItems.{{ $index }}.product_id"
                            :options="$products"
                            option-value="id"
                            option-label="name"
                            placeholder="Select product"
                            :error="$errors->first('invoiceItems.' . $index . '.product_id')" />
                    </div>

                    {{-- Quantity --}}
                    <div class="col-span-1">
                        <x-mary-input
                            label="Qty *"
                            wire:model="invoiceItems.{{ $index }}.quantity"
                            type="number"
                            step="0.01"
                            placeholder="0"
                            :error="$errors->first('invoiceItems.' . $index . '.quantity')" />
                    </div>

                    {{-- Unit Display --}}
                    <div class="col-span-1">
                        @php
                        $product = $products->find($item['product_id'] ?? 0);
                        $productUnit = $product ? $product->unit : '';
                        @endphp
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text text-xs">Unit</span>
                            </label>
                            <div class="text-sm font-medium text-center py-2">
                                {{ strtoupper($productUnit) }}
                            </div>
                        </div>
                    </div>

                    {{-- Alternative Unit --}}
                    <div class="col-span-1">
                        <x-mary-input
                            label="Alt Unit"
                            wire:model="invoiceItems.{{ $index }}.invoice_unit"
                            placeholder="kg/box" />
                    </div>

                    {{-- Unit Price --}}
                    <div class="col-span-2">
                        <x-mary-input
                            label="Unit Price *"
                            wire:model="invoiceItems.{{ $index }}.unit_price"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            prefix="₹"
                            :error="$errors->first('invoiceItems.' . $index . '.unit_price')" />
                    </div>

                    {{-- GST Rates --}}
                    @if($isGstInvoice)
                    @if($gstType === 'cgst_sgst')
                    <div class="col-span-1">
                        <x-mary-input
                            label="CGST%"
                            wire:model="invoiceItems.{{ $index }}.cgst_rate"
                            type="number"
                            step="0.01"
                            placeholder="0" />
                    </div>
                    <div class="col-span-1">
                        <x-mary-input
                            label="SGST%"
                            wire:model="invoiceItems.{{ $index }}.sgst_rate"
                            type="number"
                            step="0.01"
                            placeholder="0" />
                    </div>
                    @else
                    <div class="col-span-2">
                        <x-mary-input
                            label="IGST%"
                            wire:model="invoiceItems.{{ $index }}.igst_rate"
                            type="number"
                            step="0.01"
                            placeholder="0" />
                    </div>
                    @endif
                    @else
                    <div class="col-span-2"></div>
                    @endif

                    {{-- Discount --}}
                    <div class="col-span-1">
                        <x-mary-input
                            label="Disc%"
                            wire:model="invoiceItems.{{ $index }}.discount_percentage"
                            type="number"
                            step="0.01"
                            placeholder="0" />
                    </div>

                    {{-- Remove Button --}}
                    <div class="col-span-1">
                        @if(count($invoiceItems) > 1)
                        <x-mary-button
                            icon="o-trash"
                            class="btn-circle btn-ghost btn-sm btn-error"
                            @click="$wire.removeInvoiceItem({{ $index }})" />
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Notes --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-textarea
                    label="Notes"
                    wire:model="notes"
                    placeholder="Additional notes..."
                    rows="3" />

                <x-mary-textarea
                    label="Terms & Conditions"
                    wire:model="termsConditions"
                    rows="3" />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button
                label="Cancel"
                @click="$wire.closeInvoiceModal()" />
            <x-mary-button
                label="Save Invoice"
                class="btn-primary"
                type="submit"
                spinner="saveInvoice"
                @click="$wire.saveInvoice()" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- Monthly Bill Modal --}}
    <x-mary-modal
        wire:model="showMonthlyBillModal"
        title="Generate Monthly Bill"
        :subtitle="$selectedClient ? $selectedClient->name : ''"
        box-class="backdrop-blur max-w-4xl">

        @if($selectedClient)
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input
                    label="Period From"
                    wire:model.live="monthlyBillPeriodFrom"
                    type="date" />
                <x-mary-input
                    label="Period To"
                    wire:model.live="monthlyBillPeriodTo"
                    type="date" />
            </div>

            <div class="space-y-2">
                <h3 class="text-lg font-semibold">Unbilled Invoices</h3>
                @if(count($unbilledInvoices) > 0)
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($unbilledInvoices as $invoice)
                    <label class="flex items-center p-3 bg-base-100 rounded-lg border cursor-pointer hover:bg-base-200">
                        <input
                            type="checkbox"
                            wire:model="selectedInvoicesForBilling"
                            value="{{ $invoice->id }}"
                            class="checkbox checkbox-primary mr-3" />

                        <div class="flex-1 flex justify-between items-center">
                            <div>
                                <div class="font-medium">{{ $invoice->invoice_number }}</div>
                                <div class="text-sm text-gray-500">{{ $invoice->invoice_date->format('d/m/Y') }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold">₹{{ number_format($invoice->total_amount, 2) }}</div>
                                <x-mary-badge
                                    :value="ucfirst($invoice->payment_status)"
                                    :class="$invoice->status_badge_class . ' badge-xs'" />
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8 text-gray-500">
                    No unbilled invoices found for the selected period
                </div>
                @endif
            </div>
        </div>
        @endif

        <x-slot:actions>
            <x-mary-button
                label="Cancel"
                @click="$wire.closeMonthlyBillModal()" />
            <x-mary-button
                label="Generate Monthly Bill"
                class="btn-primary"
                spinner="generateMonthlyBill"
                @click="$wire.generateMonthlyBill()" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- View Invoice Modal --}}
    <x-mary-modal
        wire:model="showViewModal"
        :title="$viewingInvoice ? 'Invoice: ' . $viewingInvoice->invoice_number : 'Invoice Details'"
        box-class="backdrop-blur max-w-4xl max-h-[90vh] overflow-y-auto">

        @if($viewingInvoice)
        <div class="space-y-6">
            {{-- Invoice Header Info --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-base-100 rounded-lg">
                <div>
                    <label class="text-sm font-medium text-gray-600">Invoice Number</label>
                    <p class="text-lg font-bold text-primary">{{ $viewingInvoice->invoice_number }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Date</label>
                    <p class="font-medium">{{ $viewingInvoice->invoice_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Status</label>
                    <x-mary-badge
                        :value="ucfirst($viewingInvoice->payment_status)"
                        :class="$viewingInvoice->status_badge_class" />
                </div>
            </div>

            {{-- Client Info --}}
            <div class="p-4 bg-base-100 rounded-lg">
                <h4 class="font-semibold mb-3">Client Details</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Name</label>
                        <p class="font-medium">{{ $viewingInvoice->display_client_name }}</p>
                    </div>
                    @if($viewingInvoice->client_phone)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Phone</label>
                        <p>{{ $viewingInvoice->client_phone }}</p>
                    </div>
                    @endif
                    @if($viewingInvoice->client_gstin)
                    <div>
                        <label class="text-sm font-medium text-gray-600">GSTIN</label>
                        <p>{{ $viewingInvoice->client_gstin }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Invoice Items --}}
            <div class="p-4 bg-base-100 rounded-lg">
                <h4 class="font-semibold mb-3">Invoice Items</h4>
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right">Qty</th>
                                <th>Unit</th>
                                <th class="text-right">Rate</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($viewingInvoice->items as $item)
                            <tr>
                                <td class="font-medium">{{ $item->product_name }}</td>
                                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                                <td>{{ strtoupper($item->display_unit) }}</td>
                                <td class="text-right">₹{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-right font-bold">₹{{ number_format($item->total_amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Totals --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Payment Summary --}}
                <div class="p-4 bg-base-100 rounded-lg">
                    <h4 class="font-semibold mb-3">Payment Summary</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span>₹{{ number_format($viewingInvoice->subtotal, 2) }}</span>
                        </div>
                        @if($viewingInvoice->is_gst_invoice && $viewingInvoice->total_tax > 0)
                        <div class="flex justify-between">
                            <span>Total Tax:</span>
                            <span>₹{{ number_format($viewingInvoice->total_tax, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between font-bold text-lg border-t pt-2">
                            <span>Total Amount:</span>
                            <span>₹{{ number_format($viewingInvoice->total_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-success">
                            <span>Paid Amount:</span>
                            <span>₹{{ number_format($viewingInvoice->paid_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-error">
                            <span>Balance:</span>
                            <span>₹{{ number_format($viewingInvoice->balance_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Payment History --}}
                @if($viewingInvoice->payments->count() > 0)
                <div class="p-4 bg-base-100 rounded-lg">
                    <h4 class="font-semibold mb-3">Payment History</h4>
                    <div class="space-y-2 max-h-32 overflow-y-auto">
                        @foreach($viewingInvoice->payments as $payment)
                        <div class="flex justify-between items-center p-2 bg-success/10 rounded">
                            <div>
                                <p class="font-medium">₹{{ number_format($payment->amount, 2) }}</p>
                                <p class="text-xs text-gray-500">{{ $payment->payment_date->format('d/m/Y') }}</p>
                            </div>
                            <span class="badge badge-success badge-xs">{{ ucfirst($payment->payment_method) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            @if($viewingInvoice->notes)
            <div class="p-4 bg-base-100 rounded-lg">
                <h4 class="font-semibold mb-2">Notes</h4>
                <p class="text-gray-600">{{ $viewingInvoice->notes }}</p>
            </div>
            @endif
        </div>
        @endif

        <x-slot:actions>
            <x-mary-button
                label="Close"
                @click="$wire.closeViewModal()" />
            @if($viewingInvoice)
            <x-mary-button
                label="Download PDF"
                class="btn-primary"
                icon="o-arrow-down-tray"
                @click="$wire.downloadInvoicePdf({{ $viewingInvoice->id }})" />
            <x-mary-button
                label="Duplicate"
                class="btn-secondary"
                icon="o-document-duplicate"
                @click="$wire.duplicateInvoice({{ $viewingInvoice->id }})" />
            @endif
        </x-slot:actions>
    </x-mary-modal>

    {{-- Payment Modal --}}
    <x-mary-modal
        wire:model="showPaymentModal"
        :title="$paymentInvoice ? 'Add Payment - ' . $paymentInvoice->invoice_number : 'Add Payment'"
        box-class="backdrop-blur max-w-lg">

        @if($paymentInvoice)
        <div class="space-y-4">
            {{-- Invoice Summary --}}
            <div class="p-3 bg-base-100 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-medium">Total Amount:</span>
                    <span class="font-bold">₹{{ number_format($paymentInvoice->total_amount, 2) }}</span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-success">Paid Amount:</span>
                    <span class="text-success">₹{{ number_format($paymentInvoice->paid_amount, 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-error">Balance Amount:</span>
                    <span class="text-error font-bold">₹{{ number_format($paymentInvoice->balance_amount, 2) }}</span>
                </div>
            </div>

            {{-- Payment Form --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input
                    label="Payment Amount *"
                    wire:model="paymentAmount"
                    type="number"
                    step="0.01"
                    :max="$paymentInvoice->balance_amount"
                    prefix="₹"
                    placeholder="0.00"
                    :error="$errors->first('paymentAmount')"
                    required />

                <x-mary-select
                    label="Payment Method *"
                    wire:model="paymentMethod"
                    :options="[
                        ['value' => 'cash', 'label' => 'Cash'],
                        ['value' => 'bank', 'label' => 'Bank Transfer'],
                        ['value' => 'upi', 'label' => 'UPI'],
                        ['value' => 'card', 'label' => 'Card'],
                        ['value' => 'cheque', 'label' => 'Cheque']
                    ]"
                    option-value="value"
                    option-label="label"
                    :error="$errors->first('paymentMethod')"
                    required />
            </div>

            <x-mary-input
                label="Reference Number"
                wire:model="paymentReference"
                placeholder="Transaction ID / Cheque No."
                :error="$errors->first('paymentReference')" />

            <x-mary-textarea
                label="Notes"
                wire:model="paymentNotes"
                placeholder="Additional notes..."
                rows="2"
                :error="$errors->first('paymentNotes')" />
        </div>
        @endif

        <x-slot:actions>
            <x-mary-button
                label="Cancel"
                @click="$wire.closePaymentModal()" />
            <x-mary-button
                label="Save Payment"
                class="btn-success"
                spinner="savePayment"
                @click="$wire.savePayment()" />
        </x-slot:actions>
    </x-mary-modal>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('invoice-created', (event) => {
                // You can add additional handling here
                console.log('Invoice created:', event.invoiceId);
            });

            Livewire.on('monthly-bill-generated', (event) => {
                // You can add additional handling here
                console.log('Monthly bill generated:', event.billId);
            });
        });
    </script>
</div>