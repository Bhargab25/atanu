<div class="bg-white p-4 text-xs border-2 border-gray-800" style="font-family: Arial, sans-serif;">
    {{-- Header --}}
    <div class="text-center border-b-2 border-gray-800 pb-3 mb-4">
        <h2 class="text-sm font-bold uppercase tracking-wide">{{ $company['name'] }}</h2>
        <p class="text-xs mt-1">{{ $company['address'] }}</p>
        <p class="text-xs">{{ $company['city'] }}, {{ $company['state'] }} - {{ $company['pincode'] }}</p>
        <p class="text-xs">Phone: {{ $company['phone'] }}</p>
        @if($invoice->is_gst_invoice)
        <p class="text-xs"><strong>GSTIN: {{ $company['gstin'] }}</strong></p>
        @endif
        <h3 class="text-lg font-bold mt-3 tracking-widest">{{ $invoice->is_gst_invoice ? 'TAX INVOICE' : 'INVOICE' }}</h3>
    </div>

    {{-- Invoice Details --}}
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="border border-gray-800 p-2">
            <h4 class="font-bold uppercase mb-2 bg-gray-800 text-white p-1 -m-2 mb-2">Invoice To:</h4>
            <p class="font-bold">{{ $invoice->display_client_name }}</p>
            <p class="text-xs">{{ $invoice->client_address }}</p>
            @if($invoice->client_phone)
            <p class="text-xs">Phone: {{ $invoice->client_phone }}</p>
            @endif
            @if($invoice->is_gst_invoice && $invoice->client_gstin)
            <p class="text-xs"><strong>GSTIN: {{ $invoice->client_gstin }}</strong></p>
            @endif
        </div>
        <div class="border border-gray-800 p-2">
            <table class="w-full text-xs">
                <tr>
                    <td class="font-bold py-1">Invoice Number:</td>
                    <td>{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td class="font-bold py-1">Invoice Date:</td>
                    <td>{{ $invoice->invoice_date->format($dateFormat) }}</td>
                </tr>
                @if($invoice->due_date)
                <tr>
                    <td class="font-bold py-1">Due Date:</td>
                    <td>{{ $invoice->due_date->format($dateFormat) }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    {{-- Items Table --}}
    <table class="w-full text-xs border-collapse border-2 border-gray-800 mb-4">
        <thead>
            <tr class="bg-gray-800 text-white">
                <th class="border border-gray-800 p-2 text-left font-bold">#</th>
                <th class="border border-gray-800 p-2 text-left font-bold">DESCRIPTION</th>
                <th class="border border-gray-800 p-2 text-center font-bold">QTY</th>
                <th class="border border-gray-800 p-2 text-center font-bold">UNIT</th>
                <th class="border border-gray-800 p-2 text-right font-bold">UNIT PRICE</th>
                @if($invoice->is_gst_invoice)
                <th class="border border-gray-800 p-2 text-right font-bold">CGST</th>
                <th class="border border-gray-800 p-2 text-right font-bold">SGST</th>
                @endif
                <th class="border border-gray-800 p-2 text-right font-bold">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td class="border border-gray-800 p-2">{{ $index + 1 }}</td>
                <td class="border border-gray-800 p-2">{{ $item->product_name }}</td>
                <td class="border border-gray-800 p-2 text-center">{{ number_format($item->quantity, 0) }}</td>
                <td class="border border-gray-800 p-2 text-center">{{ $item->display_unit }}</td>
                <td class="border border-gray-800 p-2 text-right">{{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}</td>
                @if($invoice->is_gst_invoice)
                <td class="border border-gray-800 p-2 text-right text-xs">
                    @if($item->cgst_rate > 0)
                    {{ $item->cgst_rate }}%<br>{{ $currencySymbol }}{{ number_format($item->cgst_amount, 2) }}
                    @else
                    -
                    @endif
                </td>
                <td class="border border-gray-800 p-2 text-right text-xs">
                    @if($item->sgst_rate > 0)
                    {{ $item->sgst_rate }}%<br>{{ $currencySymbol }}{{ number_format($item->sgst_amount, 2) }}
                    @else
                    -
                    @endif
                </td>
                @endif
                <td class="border border-gray-800 p-2 text-right font-bold">{{ $currencySymbol }}{{ number_format($item->total_amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="flex justify-end mb-4">
        <div class="w-1/3 border-2 border-gray-800">
            <div class="bg-gray-800 text-white p-2">
                <h3 class="font-bold uppercase text-center">Invoice Summary</h3>
            </div>
            <div class="p-2">
                <table class="w-full text-xs">
                    <tr>
                        <td class="py-1 font-bold">Subtotal:</td>
                        <td class="py-1 text-right">{{ $currencySymbol }}{{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                    <tr>
                        <td class="py-1 font-bold">Discount:</td>
                        <td class="py-1 text-right">-{{ $currencySymbol }}{{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    @if($invoice->is_gst_invoice)
                    @if($invoice->cgst_amount > 0)
                    <tr>
                        <td class="py-1 font-bold">CGST:</td>
                        <td class="py-1 text-right">{{ $currencySymbol }}{{ number_format($invoice->cgst_amount, 2) }}</td>
                    </tr>
                    @endif
                    @if($invoice->sgst_amount > 0)
                    <tr>
                        <td class="py-1 font-bold">SGST:</td>
                        <td class="py-1 text-right">{{ $currencySymbol }}{{ number_format($invoice->sgst_amount, 2) }}</td>
                    </tr>
                    @endif
                    @endif
                    <tr class="border-t-2 border-gray-800 bg-gray-100">
                        <td class="py-2 font-bold">TOTAL DUE:</td>
                        <td class="py-2 text-right font-bold">{{ $currencySymbol }}{{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="border-t-2 border-gray-800 pt-3">
        @if($invoice->notes)
        <div class="mb-3">
            <h4 class="font-bold uppercase mb-1">Notes:</h4>
            <p class="text-xs">{{ $invoice->notes }}</p>
        </div>
        @endif
        @if($invoice->terms_conditions)
        <div class="mb-3">
            <h4 class="font-bold uppercase mb-1">Terms & Conditions:</h4>
            <p class="text-xs">{{ $invoice->terms_conditions }}</p>
        </div>
        @endif
        <div class="text-center mt-4">
            <p class="text-xs font-bold">This is a computer generated invoice and does not require signature.</p>
        </div>
    </div>
</div>
