<div class="bg-gray-900 text-white p-4 text-xs rounded-lg" style="font-family: Arial, sans-serif;">
    {{-- Header with gold accent --}}
    <div class="bg-black p-3 mb-3 rounded border-b-2 border-yellow-400">
        <div class="flex justify-between">
            <div class="w-3/5">
                <h2 class="text-sm font-bold text-white">{{ $company['name'] }}</h2>
                <p class="text-xs text-gray-300">{{ $company['address'] }}</p>
                <p class="text-xs text-gray-300">{{ $company['city'] }}, {{ $company['state'] }} - {{ $company['pincode'] }}</p>
                <p class="text-xs text-gray-300">Phone: {{ $company['phone'] }}</p>
                @if($invoice->is_gst_invoice)
                <p class="text-xs text-yellow-400"><strong>GSTIN: {{ $company['gstin'] }}</strong></p>
                @endif
            </div>
            <div class="w-2/5 text-right">
                <h3 class="text-lg font-bold text-yellow-400">
                    {{ $invoice->is_gst_invoice ? 'TAX INVOICE' : 'INVOICE' }}
                </h3>
                <div class="bg-gray-800 p-2 rounded mt-2">
                    <p class="text-xs"><strong>Invoice No:</strong> {{ $invoice->invoice_number }}</p>
                    <p class="text-xs"><strong>Date:</strong> {{ $invoice->invoice_date->format($dateFormat) }}</p>
                    @if($invoice->due_date)
                    <p class="text-xs"><strong>Due Date:</strong> {{ $invoice->due_date->format($dateFormat) }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Client Information --}}
    <div class="bg-gray-800 p-3 mb-3 rounded border-l-4 border-yellow-400">
        <h4 class="text-xs font-bold text-yellow-400 mb-1">BILL TO:</h4>
        <p class="text-xs"><strong>{{ $invoice->display_client_name }}</strong></p>
        <p class="text-xs text-gray-300">{{ $invoice->client_address }}</p>
        @if($invoice->client_phone)
        <p class="text-xs text-gray-300">Phone: {{ $invoice->client_phone }}</p>
        @endif
        @if($invoice->is_gst_invoice && $invoice->client_gstin)
        <p class="text-xs text-yellow-400"><strong>GSTIN: {{ $invoice->client_gstin }}</strong></p>
        @endif
    </div>

    {{-- Items Table --}}
    <table class="w-full text-xs mb-3 bg-gray-800 rounded overflow-hidden">
        <thead>
            <tr class="bg-yellow-400 text-black">
                <th class="p-2 w-1/12">#</th>
                <th class="p-2 text-left font-bold">PRODUCT/SERVICE</th>
                <th class="p-2 text-center w-1/12 font-bold">QTY</th>
                <th class="p-2 text-center w-1/12 font-bold">UNIT</th>
                <th class="p-2 text-right w-1/8 font-bold">RATE</th>
                @if($invoice->is_gst_invoice)
                <th class="p-2 text-right w-1/8 font-bold">CGST</th>
                <th class="p-2 text-right w-1/8 font-bold">SGST</th>
                @endif
                <th class="p-2 text-right w-1/6 font-bold">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr class="border-b border-gray-700">
                <td class="p-2 text-center">{{ $index + 1 }}</td>
                <td class="p-2">{{ $item->product_name }}</td>
                <td class="p-2 text-center">{{ number_format($item->quantity, 0) }}</td>
                <td class="p-2 text-center">{{ $item->display_unit }}</td>
                <td class="p-2 text-right">{{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}</td>
                @if($invoice->is_gst_invoice)
                <td class="p-2 text-right text-xs">
                    @if($item->cgst_rate > 0)
                    {{ $item->cgst_rate }}%<br>{{ $currencySymbol }}{{ number_format($item->cgst_amount, 2) }}
                    @else
                    -
                    @endif
                </td>
                <td class="p-2 text-right text-xs">
                    @if($item->sgst_rate > 0)
                    {{ $item->sgst_rate }}%<br>{{ $currencySymbol }}{{ number_format($item->sgst_amount, 2) }}
                    @else
                    -
                    @endif
                </td>
                @endif
                <td class="p-2 text-right font-bold text-yellow-400">{{ $currencySymbol }}{{ number_format($item->total_amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="flex justify-end mb-3">
        <div class="w-2/5 bg-gray-800 rounded p-3">
            <table class="w-full text-xs">
                <tr>
                    <td class="p-1 text-gray-300">Subtotal:</td>
                    <td class="p-1 text-right">{{ $currencySymbol }}{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                <tr>
                    <td class="p-1 text-gray-300">Discount:</td>
                    <td class="p-1 text-right">-{{ $currencySymbol }}{{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->is_gst_invoice)
                @if($invoice->cgst_amount > 0)
                <tr>
                    <td class="p-1 text-gray-300">CGST:</td>
                    <td class="p-1 text-right">{{ $currencySymbol }}{{ number_format($invoice->cgst_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->sgst_amount > 0)
                <tr>
                    <td class="p-1 text-gray-300">SGST:</td>
                    <td class="p-1 text-right">{{ $currencySymbol }}{{ number_format($invoice->sgst_amount, 2) }}</td>
                </tr>
                @endif
                @endif
                <tr class="bg-yellow-400 text-black">
                    <td class="p-2 font-bold rounded">TOTAL AMOUNT:</td>
                    <td class="p-2 text-right font-bold rounded">{{ $currencySymbol }}{{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Footer --}}
    <div class="text-xs border-t border-gray-700 pt-3">
        @if($invoice->notes)
        <div class="mb-2">
            <strong class="text-yellow-400">NOTES:</strong> <span class="text-gray-300">{{ $invoice->notes }}</span>
        </div>
        @endif
        @if($invoice->terms_conditions)
        <div class="mb-2">
            <strong class="text-yellow-400">TERMS & CONDITIONS:</strong> <span class="text-gray-300">{{ $invoice->terms_conditions }}</span>
        </div>
        @endif
        <div class="text-center mt-3 pt-2 border-t border-gray-700">
            <p class="text-xs text-gray-400">This is a computer generated invoice.</p>
        </div>
    </div>
</div>
