{{-- resources/views/pdfs/monthly-bill.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Bill {{ $monthlyBill->bill_number }}</title>
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
        .bill-info {
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
            margin-bottom: 15px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10px;
        }
        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }
        .table th {
            background-color: #f5f5f5;
            font-weight: bold;
            height: 25px;
        }
        .table tbody tr {
            height: 22px;
        }
        .table tbody tr.empty-row {
            height: 22px;
            border: 1px solid #ddd;
        }
        .table tbody tr.empty-row td {
            border-right: 1px solid #ddd;
            padding: 4px 6px;
        }
        .table tfoot tr {
            background-color: #f5f5f5;
            font-weight: bold;
            height: 28px;
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
            <p style="margin: 3px 0;"><strong>FSSAI No: {{ $company['gstin'] }}</strong></p>
        </div>
        
        <div class="bill-info">
            <h3 style="margin: 0; color: #333; font-size: 14px;">MONTHLY STATEMENT</h3>
            <p style="margin: 3px 0;"><strong>Bill No:</strong> {{ $monthlyBill->bill_number }}</p>
            <p style="margin: 3px 0;"><strong>Date:</strong> {{ $monthlyBill->bill_date->format('d/m/Y') }}</p>
            <p style="margin: 3px 0;"><strong>Period:</strong> {{ $monthlyBill->period_from->format('d/m/Y') }} to {{ $monthlyBill->period_to->format('d/m/Y') }}</p>
        </div>
    </div>

    {{-- Client Information --}}
    <div class="client-info">
        <h4 style="margin-bottom: 8px; color: #333; font-size: 12px;">Statement For:</h4>
        <p style="margin: 2px 0;"><strong>{{ $monthlyBill->client->name }}</strong></p>
        @if($monthlyBill->client->company)
            <p style="margin: 2px 0;">{{ $monthlyBill->client->company }}</p>
        @endif
        @if($monthlyBill->client->address)
            <p style="margin: 2px 0;">{{ $monthlyBill->client->address }}, {{ $monthlyBill->client->city }}, {{ $monthlyBill->client->state }}</p>
        @endif
        @if($monthlyBill->client->phone)
            <p style="margin: 2px 0;">Phone: {{ $monthlyBill->client->phone }}</p>
        @endif
        @if($monthlyBill->client->gstin)
            <p style="margin: 2px 0;"><strong>GSTIN: {{ $monthlyBill->client->gstin }}</strong></p>
        @endif
    </div>

    {{-- Content Area --}}
    <div class="content">
        <div class="table-container">
            {{-- Invoice Summary Table with Fixed Rows (up to 25 invoices max) --}}
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 25%;">Invoice No.</th>
                        <th style="width: 15%;">Date</th>
                        <th style="width: 15%;" class="text-right">Amount</th>
                        <th style="width: 15%;" class="text-right">Paid</th>
                        <th style="width: 15%;" class="text-right">Balance</th>
                        <th style="width: 10%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Display actual invoices --}}
                    @foreach($monthlyBill->invoices as $index => $invoice)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                            <td class="text-right">₹{{ number_format($invoice->total_amount, 2) }}</td>
                            <td class="text-right">₹{{ number_format($invoice->paid_amount, 2) }}</td>
                            <td class="text-right">₹{{ number_format($invoice->balance_amount, 2) }}</td>
                            <td class="text-center">{{ ucfirst($invoice->payment_status) }}</td>
                        </tr>
                    @endforeach

                    {{-- Fill remaining rows to ensure consistent table height (up to 25 rows max) --}}
                    @php
                        $maxRows = min(25, max(15, count($monthlyBill->invoices) + 3));
                    @endphp
                    @for($i = count($monthlyBill->invoices); $i < $maxRows; $i++)
                    <tr class="empty-row">
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    @endfor
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Total:</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($monthlyBill->invoices->sum('total_amount'), 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($monthlyBill->invoices->sum('paid_amount'), 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($monthlyBill->invoices->sum('balance_amount'), 2) }}</strong></td>
                        <td>&nbsp;</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Summary Section --}}
        <div class="total-section">
            <table style="width: 45%; float: right; margin-left: auto; font-size: 11px;">
                <tr>
                    <td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Total Invoices:</strong></td>
                    <td style="padding: 4px; text-align: right; border-bottom: 1px solid #ddd;">{{ $monthlyBill->invoice_count }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px; border-bottom: 1px solid #ddd;"><strong>Total Amount:</strong></td>
                    <td style="padding: 4px; text-align: right; border-bottom: 1px solid #ddd;">₹{{ number_format($monthlyBill->total_amount, 2) }}</td>
                </tr>
                <tr style="background-color: #f5f5f5;">
                    <td style="padding: 6px; border: 2px solid #333;"><strong>Outstanding Balance:</strong></td>
                    <td style="padding: 6px; text-align: right; border: 2px solid #333;"><strong>₹{{ number_format($monthlyBill->invoices->sum('balance_amount'), 2) }}</strong></td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Footer Section - Always at Bottom --}}
    <div class="footer-section">
        @if($monthlyBill->notes)
            <div style="margin-bottom: 15px;">
                <h4 style="font-size: 11px; margin-bottom: 5px;">Notes:</h4>
                <p style="font-size: 10px; margin: 0;">{{ $monthlyBill->notes }}</p>
            </div>
        @endif

        <div class="signature-section clearfix">
            <div style="float: left; width: 50%;">
                <h4 style="font-size: 11px; margin-bottom: 5px;">Payment Instructions:</h4>
                <p style="font-size: 9px; margin: 0; line-height: 1.3;">
                    Please remit payment within 15 days from statement date.<br>
                    All payments should reference this statement number.
                </p>
            </div>
            <div style="float: right; text-align: center; border-top: 1px solid #333; padding-top: 5px; width: 180px;">
                <p style="margin: 0; font-size: 11px;"><strong>Authorized Signature</strong></p>
            </div>
        </div>
        
        {{-- Final Footer --}}
        <div class="final-footer">
            <p style="margin: 0;">This is a computer generated monthly statement.</p>
            <!-- <p style="margin: 2px 0 0 0;">Thank you for your continued business!</p> -->
        </div>
    </div>
</body>
</html>
