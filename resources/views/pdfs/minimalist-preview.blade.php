<div class="bg-white p-4 text-xs" style="font-family: Arial, sans-serif;">
    {{-- Header --}}
    <div class="border-b pb-3 mb-4">
        <div class="flex justify-between items-start">
            <div class="w-3/5">
                <h2 class="text-lg font-light text-gray-800">{{ $company['name'] }}</h2>
                <div class="text-gray-600 mt-2 space-y-1">
                    <p class="text-xs">{{ $company['address'] }}</p>
                    <p class="text-xs">{{ $company['city'] }}, {{ $company['state'] }} - {{ $company['pincode'] }}</p>
                    <p class="text-xs">{{ $company['phone'] }}</p>
                    @if($invoice->is_gst_invoice)
                    <p class="text-xs">GSTIN: {{ $company['gstin'] }}</p>
                    @endif
                </div>
            </div>
            <div class="w-2/5 text-right">
                <h3 class="text-2xl font-light text-gray-800 mb-3">
                    {{ $invoice->is_gst_invoice ? 'Invoice' : 'Invoice' }}
                </h3>
                <div class="text-gray-600 space-y-1">
                    <p class="text-xs">{{ $invoice->invoice_number }}</p>
                    <p class="text-xs">{{ $invoice->invoice_date->format($dateFormat) }}</p>
                    @if($invoice->due_date)
                    <p class="text-xs">Due: {{ $invoice->due_date->format($dateFormat) }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Client Information --}}
    <div class="mb-4">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Bill To</p>
        <div class="text-gray-800">
            <p class="text-sm font-medium">{{ $invoice->display_client_name }}</p>
            <p class="text-xs text-gray-600">{{ $invoice->client_address }}</p>
            @if($invoice->client_phone)
            <p class="text-xs text-gray-600">{{ $invoice->client_phone }}</p>
            @endif
            @if($invoice->is_gst_invoice && $invoice->client_gstin)
            <p class="text-xs text-gray-600">GSTIN: {{ $invoice->client_gstin }}</p>
            @endif
        </div>
    </div>

    {{-- Items Table --}}
    <div class="mb-4">
        <table class="w-full text-xs">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-2 text-left font-medium text-gray-600">#</th>
                    <th class="py-2 text-left font-medium text-gray-600">Description</th>
                    <th class="py-2 text-center font-medium text-gray-600">Qty</th>
                    <th class="py-2 text-center font-medium text-gray-600">Unit</th>
                    <th class="py-2 text-right font-medium text-gray-600">Rate</th>
                    @if($invoice->is_gst_invoice)
                    <th class="py-2 text-right font-medium text-gray-600">CGST</th>
                    <th class="py-2 text-right font-medium text-gray-600">SGST</th>
                    @endif
                    <th class="py-2 text-right font-medium text-gray-600">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr class="border-b border-gray-100">
                    <td class="py-2">{{ $index + 1 }}</td>
                    <td class="py-2">{{ $item->product_name }}</td>
                    <td class="py-2 text-center">{{ number_format($item->quantity, 0) }}</td>
                    <td class="py-2 text-center">{{ $item->display_unit }}</td>
                    <td class="py-2 text-right">{{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}</td>
                    @if($invoice->is_gst_invoice)
                    <td class="py-2 text-right text-xs">
                        @if($item->cgst_rate > 0)
                        {{ $item->cgst_rate }}%<br>{{ $currencySymbol }}{{ number_format($item->cgst_amount, 2) }}
                        @else
                        -
                        @endif
                    </td>
                    <td class="py-2 text-right text-xs">
                        @if($item->sgst_rate > 0)
                        {{ $item->sgst_rate }}%<br>{{ $currencySymbol }}{{ number_format($item->sgst_amount, 2) }}
                        @else
                        -
                        @endif
                    </td>
                    @endif
                    <td class="py-2 text-right">{{ $currencySymbol }}{{ number_format($item->total_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Totals --}}
    <div class="flex justify-end">
        <div class="w-1/3">
            <div class="space-y-2">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span>{{ $currencySymbol }}{{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                @if($invoice->discount_amount > 0)
                <div class="flex justify-between text-gray-600">
                    <span>Discount</span>
                    <span>-{{ $currencySymbol }}{{ number_format($invoice->discount_amount, 2) }}</span>
                </div>
                @endif
                @if($invoice->is_gst_invoice)
                @if($invoice->cgst_amount > 0)
                <div class="flex justify-between text-gray-600">
                    <span>CGST</span>
                    <span>{{ $currencySymbol }}{{ number_format($invoice->cgst_amount, 2) }}</span>
                </div>
                @endif
                @if($invoice->sgst_amount > 0)
                <div class="flex justify-between text-gray-600">
                    <span>SGST</span>
                    <span>{{ $currencySymbol }}{{ number_format($invoice->sgst_amount, 2) }}</span>
                </div>
                @endif
                @endif
                <div class="flex justify-between text-lg font-medium border-t pt-2">
                    <span>Total</span>
                    <span>{{ $currencySymbol }}{{ number_format($invoice->total_amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="mt-6 text-xs text-gray-600">
        @if($invoice->notes)
        <div class="mb-3">
            <h4 class="font-medium mb-1">Notes</h4>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif
        @if($invoice->terms_conditions)
        <div class="mb-3">
            <h4 class="font-medium mb-1">Terms & Conditions</h4>
            <p>{{ $invoice->terms_conditions }}</p>
        </div>
        @endif
        <div class="text-center mt-4 pt-3 border-t">
            <p>This is a computer generated invoice.</p>
        </div>
    </div>
</div>
