{{-- resources/views/pdfs/invoice-preview.blade.php --}}
<div class="bg-white p-4 text-xs" style="font-family: Arial, sans-serif;">
    {{-- Header --}}
    <div class="border-b-2 border-gray-800 pb-2 mb-3">
        <div class="flex justify-between">
            <div class="w-3/5">
                <h2 class="text-sm font-bold text-gray-800">{{ $company['name'] }}</h2>
                <p class="text-xs">{{ $company['address'] }}</p>
                <p class="text-xs">{{ $company['city'] }}, {{ $company['state'] }} - {{ $company['pincode'] }}</p>
                <p class="text-xs">Phone: {{ $company['phone'] }}</p>
                @if($invoice->is_gst_invoice)
                <p class="text-xs"><strong>GSTIN: {{ $company['gstin'] }}</strong></p>
                @endif
            </div>
            <div class="w-2/5 text-right">
                <h3 class="text-sm font-bold text-gray-800">
                    {{ $invoice->is_gst_invoice ? 'TAX INVOICE' : 'INVOICE' }}
                </h3>
                <p class="text-xs"><strong>Invoice No:</strong> {{ $invoice->invoice_number }}</p>
                <p class="text-xs"><strong>Date:</strong> {{ $invoice->invoice_date->format($dateFormat) }}</p>
                @if($invoice->due_date)
                <p class="text-xs"><strong>Due Date:</strong> {{ $invoice->due_date->format($dateFormat) }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Client Information --}}
    <div class="mb-3">
        <h4 class="text-xs font-bold text-gray-800 mb-1">Bill To:</h4>
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
    <table class="w-full text-xs border-collapse border border-gray-300 mb-3">
        <thead>
            <tr class="bg-gray-100">
                <th class="border border-gray-300 p-1 w-1/12">#</th>
                <th class="border border-gray-300 p-1 text-left">Product/Service</th>
                <th class="border border-gray-300 p-1 text-center w-1/12">Qty</th>
                <th class="border border-gray-300 p-1 text-center w-1/12">Unit</th>
                <th class="border border-gray-300 p-1 text-right w-1/8">Rate</th>
                @if($invoice->is_gst_invoice)
                <th class="border border-gray-300 p-1 text-right w-1/8">CGST</th>
                <th class="border border-gray-300 p-1 text-right w-1/8">SGST</th>
                @endif
                <th class="border border-gray-300 p-1 text-right w-1/6">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td class="border border-gray-300 p-1 text-center">{{ $index + 1 }}</td>
                <td class="border border-gray-300 p-1">{{ $item->product_name }}</td>
                <td class="border border-gray-300 p-1 text-center">{{ number_format($item->quantity, 0) }}</td>
                <td class="border border-gray-300 p-1 text-center">{{ $item->display_unit }}</td>
                <td class="border border-gray-300 p-1 text-right">{{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}</td>
                @if($invoice->is_gst_invoice)
                <td class="border border-gray-300 p-1 text-right text-xs">
                    @if($item->cgst_rate > 0)
                    {{ $item->cgst_rate }}%<br>{{ $currencySymbol }}{{ number_format($item->cgst_amount, 2) }}
                    @else
                    -
                    @endif
                </td>
                <td class="border border-gray-300 p-1 text-right text-xs">
                    @if($item->sgst_rate > 0)
                    {{ $item->sgst_rate }}%<br>{{ $currencySymbol }}{{ number_format($item->sgst_amount, 2) }}
                    @else
                    -
                    @endif
                </td>
                @endif
                <td class="border border-gray-300 p-1 text-right font-bold">{{ $currencySymbol }}{{ number_format($item->total_amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="flex justify-end mb-3">
        <table class="w-2/5 text-xs">
            <tr>
                <td class="p-1 border-b"><strong>Subtotal:</strong></td>
                <td class="p-1 text-right border-b">{{ $currencySymbol }}{{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            @if($invoice->discount_amount > 0)
            <tr>
                <td class="p-1 border-b">Discount:</td>
                <td class="p-1 text-right border-b">-{{ $currencySymbol }}{{ number_format($invoice->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if($invoice->is_gst_invoice)
            @if($invoice->cgst_amount > 0)
            <tr>
                <td class="p-1 border-b">CGST:</td>
                <td class="p-1 text-right border-b">{{ $currencySymbol }}{{ number_format($invoice->cgst_amount, 2) }}</td>
            </tr>
            @endif
            @if($invoice->sgst_amount > 0)
            <tr>
                <td class="p-1 border-b">SGST:</td>
                <td class="p-1 text-right border-b">{{ $currencySymbol }}{{ number_format($invoice->sgst_amount, 2) }}</td>
            </tr>
            @endif
            @endif
            <tr class="bg-gray-100">
                <td class="p-1 border-2 border-gray-800 font-bold">Total Amount:</td>
                <td class="p-1 text-right border-2 border-gray-800 font-bold">{{ $currencySymbol }}{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    {{-- Footer --}}
    <div class="text-xs">
        @if($invoice->notes)
        <div class="mb-2">
            <strong>Notes:</strong> {{ $invoice->notes }}
        </div>
        @endif

        @if($invoice->terms_conditions)
        <div class="mb-2">
            <strong>Terms & Conditions:</strong> {{ $invoice->terms_conditions }}
        </div>
        @endif

        <div class="text-center mt-3 pt-2 border-t">
            <p class="text-xs text-gray-600">This is a computer generated invoice.</p>
        </div>
    </div>
</div>
