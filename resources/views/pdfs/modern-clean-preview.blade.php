{{-- resources/views/pdfs/modern-clean-preview.blade.php --}}
<div class="bg-white p-4 text-xs overflow-hidden" style="font-family: Arial, sans-serif;">
    {{-- Header --}}
    <div class="bg-blue-500 text-white p-3 -m-4 mb-3 rounded-t-lg">
        <div class="flex justify-between">
            <div class="w-3/5">
                <h2 class="text-sm font-bold">{{ $company['name'] }}</h2>
                <p class="text-xs opacity-90">{{ $company['address'] }}</p>
                <p class="text-xs opacity-90">{{ $company['city'] }}, {{ $company['state'] }} - {{ $company['pincode'] }}</p>
                <p class="text-xs opacity-90">Phone: {{ $company['phone'] }}</p>
                @if($invoice->is_gst_invoice)
                <p class="text-xs opacity-90"><strong>GSTIN: {{ $company['gstin'] }}</strong></p>
                @endif
            </div>
            <div class="w-2/5 text-right">
                <div class="bg-white text-blue-600 px-3 py-1 rounded-lg inline-block">
                    <h3 class="text-sm font-bold">
                        {{ $invoice->is_gst_invoice ? 'TAX INVOICE' : 'INVOICE' }}
                    </h3>
                </div>
                <p class="text-xs mt-2"><strong>Invoice No:</strong> {{ $invoice->invoice_number }}</p>
                <p class="text-xs"><strong>Date:</strong> {{ $invoice->invoice_date->format($dateFormat) }}</p>
                @if($invoice->due_date)
                <p class="text-xs"><strong>Due Date:</strong> {{ $invoice->due_date->format($dateFormat) }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Client Information --}}
    <div class="bg-blue-50 p-3 mb-3 rounded-lg border-l-4 border-blue-500">
        <h4 class="text-xs font-bold text-blue-800 mb-1">Bill To:</h4>
        <p class="text-xs"><strong>{{ $invoice->display_client_name }}</strong></p>
        <p class="text-xs">{{ $invoice->client_address }}</p>
        @if($invoice->client_phone)
        <p class="text-xs">Phone: {{ $invoice->client_phone }}</p>
        @endif
        @if($invoice->is_gst_invoice && $invoice->client_gstin)
        <p class="text-xs"><strong>GSTIN: {{ $invoice->client_gstin }}</strong></p>
        @endif
    </div>

    {{-- Items Table --}}
    <table class="w-full text-xs border-collapse mb-3 overflow-hidden rounded-lg">
        <thead>
            <tr class="bg-blue-500 text-white">
                <th class="p-1 w-1/12">#</th>
                <th class="p-1 text-left">Product/Service</th>
                <th class="p-1 text-center w-1/12">Qty</th>
                <th class="p-1 text-center w-1/12">Unit</th>
                <th class="p-1 text-right w-1/8">Rate</th>
                @if($invoice->is_gst_invoice)
                <th class="p-1 text-right w-1/8">CGST</th>
                <th class="p-1 text-right w-1/8">SGST</th>
                @endif
                <th class="p-1 text-right w-1/6">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr class="border-b border-blue-100 hover:bg-blue-50">
                <td class="p-1 text-center">{{ $index + 1 }}</td>
                <td class="p-1">{{ $item->product_name }}</td>
                <td class="p-1 text-center">{{ number_format($item->quantity, 0) }}</td>
                <td class="p-1 text-center">{{ $item->display_unit }}</td>
                <td class="p-1 text-right">{{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}</td>
                @if($invoice->is_gst_invoice)
                <td class="p-1 text-right text-xs">
                    @if($item->cgst_rate > 0)
                    {{ $item->cgst_rate }}%<br>{{ $currencySymbol }}{{ number_format($item->cgst_amount, 2) }}
                    @else
                    -
                    @endif
                </td>
                <td class="p-1 text-right text-xs">
                    @if($item->sgst_rate > 0)
                    {{ $item->sgst_rate }}%<br>{{ $currencySymbol }}{{ number_format($item->sgst_amount, 2) }}
                    @else
                    -
                    @endif
                </td>
                @endif
                <td class="p-1 text-right font-bold text-blue-700">{{ $currencySymbol }}{{ number_format($item->total_amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="flex justify-end mb-3">
        <div class="w-2/5 bg-blue-50 p-2 rounded-lg">
            <table class="w-full text-xs">
                <tr>
                    <td class="p-1 border-b border-blue-200"><strong>Subtotal:</strong></td>
                    <td class="p-1 text-right border-b border-blue-200">{{ $currencySymbol }}{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                <tr>
                    <td class="p-1 border-b border-blue-200">Discount:</td>
                    <td class="p-1 text-right border-b border-blue-200">-{{ $currencySymbol }}{{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->is_gst_invoice)
                @if($invoice->cgst_amount > 0)
                <tr>
                    <td class="p-1 border-b border-blue-200">CGST:</td>
                    <td class="p-1 text-right border-b border-blue-200">{{ $currencySymbol }}{{ number_format($invoice->cgst_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->sgst_amount > 0)
                <tr>
                    <td class="p-1 border-b border-blue-200">SGST:</td>
                    <td class="p-1 text-right border-b border-blue-200">{{ $currencySymbol }}{{ number_format($invoice->sgst_amount, 2) }}</td>
                </tr>
                @endif
                @endif
                <tr class="bg-blue-600 text-white">
                    <td class="p-2 font-bold rounded-bl-lg">Total Amount:</td>
                    <td class="p-2 text-right font-bold rounded-br-lg">{{ $currencySymbol }}{{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Footer --}}
    <div class="text-xs border-t border-blue-200 pt-2">
        @if($invoice->notes)
        <div class="mb-2">
            <strong class="text-blue-700">Notes:</strong> {{ $invoice->notes }}
        </div>
        @endif
        @if($invoice->terms_conditions)
        <div class="mb-2">
            <strong class="text-blue-700">Terms & Conditions:</strong> {{ $invoice->terms_conditions }}
        </div>
        @endif
        <div class="text-center mt-2 pt-2 border-t border-blue-100">
            <p class="text-xs text-blue-600">This is a computer generated invoice.</p>
        </div>
    </div>
</div>