<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #3B82F6;
            padding-bottom: 20px;
        }

        .company-info {
            float: left;
            width: 48%;
        }

        .company-header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .company-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            display: block;
        }

        .company-logo-cell {
            width: 70px;
            vertical-align: top;
            padding-right: 15px;
        }

        .company-details-cell {
            vertical-align: top;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #3B82F6;
            margin: 0 0 5px 0;
        }

        .company-details-cell div {
            margin: 2px 0;
        }

        .invoice-info {
            float: right;
            width: 48%;
            text-align: right;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 10px;
        }

        .billing-section {
            margin: 30px 0;
        }

        .billing-info {
            float: left;
            width: 48%;
        }

        .billing-info h3 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #374151;
            background-color: #F3F4F6;
            padding: 8px;
            margin: 0 0 10px 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }

        .items-table th {
            background-color: #3B82F6;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
        }

        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #E5E7EB;
        }

        .items-table tr:nth-child(even) {
            background-color: #F9FAFB;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-section {
            float: right;
            width: 300px;
            margin-top: 20px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #E5E7EB;
        }

        .total-row {
            font-weight: bold;
            font-size: 14px;
            background-color: #EBF8FF;
            border-top: 2px solid #3B82F6;
        }

        .payment-info {
            margin-top: 40px;
            clear: both;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-paid {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .status-unpaid {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        .status-partial {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
            font-size: 10px;
            color: #6B7280;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    {{-- Header --}}
    <div class="header clearfix">
        <div class="company-info">
            <table class="company-header-table">
                <tr>
                    {{-- Company Logo --}}
                    @if($invoice->company->logo_path)
                    @php
                    $logoPath = storage_path('app/public/' . $invoice->company->logo_path);

                    if (!file_exists($logoPath)) {
                    $logoPath = public_path('storage/' . $invoice->company->logo_path);
                    }

                    // Convert to base64 for better PDF compatibility
                    if (file_exists($logoPath)) {
                    $logoData = base64_encode(file_get_contents($logoPath));
                    $mimeType = mime_content_type($logoPath);
                    $logoSrc = "data:$mimeType;base64,$logoData";
                    }
                    @endphp

                    @if(isset($logoSrc))
                    <td class="company-logo-cell">
                        <img src="{{ $logoSrc }}" alt="{{ $invoice->company->name }}" class="company-logo">
                    </td>
                    @endif
                    @endif

                    <td class="company-details-cell">
                        <div class="company-name">{{ $invoice->company->name }}</div>
                        @if($invoice->company->legal_name && $invoice->company->legal_name !== $invoice->company->name)
                        <div>{{ $invoice->company->legal_name }}</div>
                        @endif
                        @if($invoice->company->address)
                        <div>{{ $invoice->company->address }}</div>
                        @endif
                        @if($invoice->company->city || $invoice->company->state || $invoice->company->postal_code)
                        <div>
                            {{ $invoice->company->city }}
                            @if($invoice->company->city && $invoice->company->state), @endif
                            {{ $invoice->company->state }}
                            {{ $invoice->company->postal_code }}
                        </div>
                        @endif
                        @if($invoice->company->phone)
                        <div>Phone: {{ $invoice->company->phone }}</div>
                        @endif
                        @if($invoice->company->email)
                        <div>Email: {{ $invoice->company->email }}</div>
                        @endif
                        @if($invoice->company->gstin)
                        <div>GSTIN: {{ $invoice->company->gstin }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>


        <div class="invoice-info">
            <div class="invoice-title">INVOICE</div>
            <div><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</div>
            <div><strong>Date:</strong> {{ $invoice->invoice_date->format('d/m/Y') }}</div>
            @if($invoice->due_date)
            <div><strong>Due Date:</strong> {{ $invoice->due_date->format('d/m/Y') }}</div>
            @endif
        </div>
    </div>

    {{-- Billing Information --}}
    <div class="billing-section clearfix">
        <div class="billing-info">
            <h3>Bill To:</h3>
            <div><strong>{{ $invoice->client->name }}</strong></div>
            @if($invoice->client->company_name)
            <div>{{ $invoice->client->company_name }}</div>
            @endif
            <div>{{ $invoice->client->phone }}</div>
            @if($invoice->client->email)
            <div>{{ $invoice->client->email }}</div>
            @endif
            @if($invoice->client->full_address)
            <div>{{ $invoice->client->full_address }}</div>
            @endif
            @if($invoice->client->gstin)
            <div>GSTIN: {{ $invoice->client->gstin }}</div>
            @endif
        </div>
    </div>

    {{-- Invoice Items --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 20%">Service</th>
                <th style="width: 25%">Item</th>
                <th style="width: 30%">Description</th>
                <th style="width: 8%" class="text-center">Qty</th>
                <th style="width: 12%" class="text-right">Unit Price</th>
                <th style="width: 12%" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->invoice_items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item['service_name'] }}</td>
                <td><strong>{{ $item['item_name'] }}</strong></td>
                <td>{{ $item['description'] ?: '-' }}</td>
                <td class="text-center">{{ number_format($item['quantity'], 2) }}</td>
                <td class="text-right">₹{{ number_format($item['unit_price'], 2) }}</td>
                <td class="text-right">₹{{ number_format($item['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">₹{{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            @if($invoice->tax_amount > 0)
            <tr>
                <td>Tax Amount:</td>
                <td class="text-right">₹{{ number_format($invoice->tax_amount, 2) }}</td>
            </tr>
            @endif
            @if($invoice->discount_amount > 0)
            <tr>
                <td>Discount:</td>
                <td class="text-right">-₹{{ number_format($invoice->discount_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td><strong>Total Amount:</strong></td>
                <td class="text-right"><strong>₹{{ number_format($invoice->total_amount, 2) }}</strong></td>
            </tr>
            @if($invoice->paid_amount > 0)
            <tr>
                <td>Amount Paid:</td>
                <td class="text-right" style="color: #059669;">₹{{ number_format($invoice->paid_amount, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Amount Due:</strong></td>
                <td class="text-right" style="color: #DC2626;"><strong>₹{{ number_format($invoice->outstanding_amount, 2) }}</strong></td>
            </tr>
            @endif
        </table>
    </div>

    {{-- Payment Information --}}
    @if($invoice->payments->count() > 0)
    <div class="payment-info">
        <h3>Payment History</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Payment Ref</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->payments as $payment)
                <tr>
                    <td>{{ $payment->payment_reference }}</td>
                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                    <td>{{ $payment->payment_method_label }}</td>
                    <td class="text-right">₹{{ number_format($payment->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Notes --}}
    @if($invoice->notes)
    <div style="margin-top: 30px;">
        <h3>Notes:</h3>
        <p>{{ $invoice->notes }}</p>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div class="text-center">
            <p>Thank you for your business!</p>
            <p>Generated on {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>

</html>