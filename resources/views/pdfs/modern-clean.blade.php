{{-- resources/views/pdf/invoice.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 10mm 15mm 15mm 15mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.2;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
            margin-bottom: 15px;
            flex-shrink: 0;
        }

        .company-info {
            float: left;
            width: 60%;
        }

        .invoice-info {
            float: right;
            width: 35%;
            text-align: right;
        }

        .client-info {
            clear: both;
            margin: 15px 0;
            flex-shrink: 0;
        }

        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .table-container {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            flex: 1;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
            font-size: 10px;
        }

        .table th {
            background-color: #f5f5f5;
            font-weight: bold;
            height: 25px;
        }

        .table tbody tr {
            height: 25px;
            /* Fixed row height */
        }

        .table tbody tr.empty-row {
            height: 25px;
            border: 1px solid #ddd;
        }

        .table tbody tr.empty-row td {
            border-right: 1px solid #ddd;
            padding: 4px 6px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-section {
            margin: 15px 0;
            flex-shrink: 0;
        }

        .gst-section {
            background-color: #f9f9f9;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        .footer-section {
            margin-top: auto;
            flex-shrink: 0;
            clear: both;
            padding-top: 20px;
        }

        .signature-section {
            margin: 20px 0;
        }

        .final-footer {
            text-align: center;
            font-size: 9px;
            color: #666;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
    </style>
</head>

<body>
    {{-- Header --}}
    <div class="header clearfix">
        <div class="company-info">
            <h2 style="margin: 0; color: #333; font-size: 16px;">{{ $company['name'] }}</h2>
            <p style="margin: 3px 0;">{{ $company['address'] }}</p>
            <p style="margin: 3px 0;">{{ $company['city'] }}, {{ $company['state'] }} - {{ $company['pincode'] }}</p>
            <p style="margin: 3px 0;">Phone: {{ $company['phone'] }}</p>
            @if($invoice->is_gst_invoice)
            <p style="margin: 3px 0;"><strong>FSSAI No: {{ $company['gstin'] }}</strong></p>
            @endif
        </div>
        <div class="invoice-info">
            <h3 style="margin: 0; color: #333; font-size: 14px;">
                {{ $invoice->is_gst_invoice ? 'TAX INVOICE' : 'INVOICE' }}
            </h3>
            <p style="margin: 3px 0;"><strong>Invoice No:</strong> {{ $invoice->invoice_number }}</p>
            <p style="margin: 3px 0;"><strong>Date:</strong> {{ $invoice->invoice_date->format('d/m/Y') }}</p>
            @if($invoice->due_date)
            <p style="margin: 3px 0;"><strong>Due Date:</strong> {{ $invoice->due_date->format('d/m/Y') }}</p>
            @endif
            @if($invoice->is_gst_invoice)
            <p style="margin: 3px 0;"><strong>Place of Supply:</strong> {{ $invoice->place_of_supply }}</p>
            @endif
        </div>
    </div>

    {{-- Client Information --}}
    <div class="client-info">
        <h4 style="margin-bottom: 8px; color: #333; font-size: 12px;">Bill To:</h4>
        <p style="margin: 2px 0;"><strong>{{ $invoice->display_client_name }}</strong></p>
        @if($invoice->client_address)
        <p style="margin: 2px 0;">{{ $invoice->client_address }}</p>
        @endif
        @if($invoice->client_phone)
        <p style="margin: 2px 0;">Phone: {{ $invoice->client_phone }}</p>
        @endif
        @if($invoice->is_gst_invoice && $invoice->client_gstin)
        <p style="margin: 2px 0;"><strong>GSTIN: {{ $invoice->client_gstin }}</strong></p>
        @endif
    </div>

    {{-- Content Area --}}
    <div class="content">
        <div class="table-container">
            {{-- Invoice Items Table with Fixed 17 Rows --}}
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 4%;">#</th>
                        <th style="width: {{ $invoice->is_gst_invoice ? '28%' : '40%' }};">Product/Service</th>
                        <th style="width: 8%;" class="text-center">Qty</th>
                        <th style="width: 6%;" class="text-center">Unit</th>
                        <th style="width: 10%;" class="text-right">Rate</th>
                        @if($invoice->is_gst_invoice)
                        <th style="width: 12%;" class="text-right">Taxable Amt</th>
                        @if($invoice->gst_type === 'cgst_sgst')
                        <th style="width: 8%;" class="text-right">CGST</th>
                        <th style="width: 8%;" class="text-right">SGST</th>
                        @else
                        <th style="width: 12%;" class="text-right">IGST</th>
                        @endif
                        @endif
                        <th style="width: 12%;" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Display actual items --}}
                    @foreach($invoice->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td style="font-size: 9px;">{{ $item->product_name }}</td>
                        <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-center">{{ strtoupper($item->display_unit) }}</td>
                        <td class="text-right">₹{{ number_format($item->unit_price, 2) }}</td>
                        @if($invoice->is_gst_invoice)
                        <td class="text-right">₹{{ number_format($item->taxable_amount, 2) }}</td>
                        @if($invoice->gst_type === 'cgst_sgst')
                        <td class="text-right" style="font-size: 9px;">
                            @if($item->cgst_rate > 0)
                            {{ $item->cgst_rate }}%<br>
                            ₹{{ number_format($item->cgst_amount, 2) }}
                            @else
                            -
                            @endif
                        </td>
                        <td class="text-right" style="font-size: 9px;">
                            @if($item->sgst_rate > 0)
                            {{ $item->sgst_rate }}%<br>
                            ₹{{ number_format($item->sgst_amount, 2) }}
                            @else
                            -
                            @endif
                        </td>
                        @else
                        <td class="text-right" style="font-size: 9px;">
                            @if($item->igst_rate > 0)
                            {{ $item->igst_rate }}%<br>
                            ₹{{ number_format($item->igst_amount, 2) }}
                            @else
                            -
                            @endif
                        </td>
                        @endif
                        @endif
                        <td class="text-right"><strong>₹{{ number_format($item->total_amount, 2) }}</strong></td>
                    </tr>
                    @endforeach

                    {{-- Fill remaining rows to make exactly 17 rows total --}}
                    @for($i = count($invoice->items); $i < 17; $i++)
                        <tr class="empty-row">
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        @if($invoice->is_gst_invoice)
                        <td>&nbsp;</td>
                        @if($invoice->gst_type === 'cgst_sgst')
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        @else
                        <td>&nbsp;</td>
                        @endif
                        @endif
                        <td>&nbsp;</td>
                        </tr>
                        @endfor
                </tbody>
            </table>
        </div>

        {{-- Totals Section --}}
        <div class="total-section">
            <table style="width: 40%; float: right; margin-left: auto; font-size: 11px;">
                <tr>
                    <td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Subtotal:</strong></td>
                    <td style="padding: 4px; text-align: right; border-bottom: 1px solid #ddd;">₹{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                <tr>
                    <td style="padding: 4px; border-bottom: 1px solid #ddd;">Discount:</td>
                    <td style="padding: 4px; text-align: right; border-bottom: 1px solid #ddd;">-₹{{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->is_gst_invoice)
                @if($invoice->cgst_amount > 0)
                <tr>
                    <td style="padding: 4px; border-bottom: 1px solid #ddd;">CGST:</td>
                    <td style="padding: 4px; text-align: right; border-bottom: 1px solid #ddd;">₹{{ number_format($invoice->cgst_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->sgst_amount > 0)
                <tr>
                    <td style="padding: 4px; border-bottom: 1px solid #ddd;">SGST:</td>
                    <td style="padding: 4px; text-align: right; border-bottom: 1px solid #ddd;">₹{{ number_format($invoice->sgst_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->igst_amount > 0)
                <tr>
                    <td style="padding: 4px; border-bottom: 1px solid #ddd;">IGST:</td>
                    <td style="padding: 4px; text-align: right; border-bottom: 1px solid #ddd;">₹{{ number_format($invoice->igst_amount, 2) }}</td>
                </tr>
                @endif
                @endif
                <tr style="background-color: #f5f5f5;">
                    <td style="padding: 6px; border: 2px solid #333;"><strong>Total Amount:</strong></td>
                    <td style="padding: 6px; text-align: right; border: 2px solid #333;"><strong>₹{{ number_format($invoice->total_amount, 2) }}</strong></td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Footer Section - Always at Bottom --}}
    <div class="footer-section">
        @if($invoice->notes)
        <div style="margin-bottom: 15px;">
            <h4 style="font-size: 11px; margin-bottom: 5px;">Notes:</h4>
            <p style="font-size: 10px; margin: 0;">{{ $invoice->notes }}</p>
        </div>
        @endif

        @if($invoice->terms_conditions)
        <div style="margin-bottom: 15px;">
            <h4 style="font-size: 11px; margin-bottom: 5px;">Terms & Conditions:</h4>
            <p style="font-size: 10px; margin: 0;">{{ $invoice->terms_conditions }}</p>
        </div>
        @endif

        <div class="signature-section clearfix">
            <div style="float: right; text-align: center; border-top: 1px solid #333; padding-top: 5px; width: 180px;">
                <p style="margin: 0; font-size: 11px;"><strong>Authorized Signature</strong></p>
            </div>
        </div>

        {{-- Final Footer --}}
        <div class="final-footer">
            <p style="margin: 0;">This is a computer generated invoice and does not require physical signature.</p>
        </div>
    </div>
</body>

</html>