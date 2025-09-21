<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - {{ $payment->payment_id }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2563eb;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }

        .company-details {
            font-size: 11px;
            color: #666;
            margin-bottom: 15px;
        }

        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            background: #f3f4f6;
            padding: 10px;
            border-radius: 5px;
        }

        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }

        .receipt-left,
        .receipt-right {
            width: 48%;
        }

        .info-group {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9fafb;
            border-left: 4px solid #2563eb;
        }

        .info-title {
            font-size: 14px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 5px 0;
            border-bottom: 1px dotted #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: 600;
            color: #4b5563;
        }

        .value {
            color: #1f2937;
        }

        .amount-highlight {
            font-size: 18px;
            font-weight: bold;
            color: #059669;
            background: #d1fae5;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin: 40px 0;
        }

        .signature {
            text-align: center;
            width: 200px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 11px;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(37, 99, 235, 0.05);
            font-weight: bold;
            z-index: -1;
        }

        .payment-method-badge {
            background: #3b82f6;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-paid {
            background: #10b981;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <div class="watermark">PAID</div>

    {{-- Header --}}
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'Company Name' }}</div>
        <div class="company-details">
            {{ $company->address ?? 'Company Address' }}<br>
            {{ $company->city ?? 'City' }}, {{ $company->state ?? 'State' }} - {{ $company->postal_code ?? 'Postal Code' }}<br>
            Phone: {{ $company->phone ?? 'N/A' }} | Email: {{ $company->email ?? 'N/A' }}
            @if($company->gstin)
            <br>GSTIN: {{ $company->gstin }}
            @endif
        </div>
        <div class="receipt-title">SALARY PAYMENT RECEIPT</div>
    </div>

    {{-- Receipt Information --}}
    <div style="display: table; width: 100%; margin: 20px 0;">
        <div style="display: table-cell; width: 48%; vertical-align: top;">
            <div class="info-group">
                <div class="info-title">Employee Details</div>
                <div class="info-row">
                    <span class="label">Employee ID:</span>
                    <span class="value">{{ $employee->employee_id }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Name:</span>
                    <span class="value">{{ $employee->name }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Position:</span>
                    <span class="value">{{ $employee->position ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Department:</span>
                    <span class="value">{{ $employee->department ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Phone:</span>
                    <span class="value">{{ $employee->phone }}</span>
                </div>
            </div>
        </div>

        <div style="display: table-cell; width: 4%;"></div>

        <div style="display: table-cell; width: 48%; vertical-align: top;">
            <div class="info-group">
                <div class="info-title">Payment Details</div>
                <div class="info-row">
                    <span class="label">Receipt ID:</span>
                    <span class="value">{{ $payment->payment_id }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Payment Date:</span>
                    <span class="value">{{ $payment->payment_date->format('d F, Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Month/Year:</span>
                    <span class="value">{{ \Carbon\Carbon::createFromFormat('Y-m', $payment->month_year)->format('F Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Payment Method:</span>
                    <span class="value">
                        <span class="payment-method-badge">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                    </span>
                </div>
                @if($payment->reference_number)
                <div class="info-row">
                    <span class="label">Reference:</span>
                    <span class="value">{{ $payment->reference_number }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="label">Status:</span>
                    <span class="value">
                        <span class="status-paid">{{ ucfirst($payment->status) }}</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Amount Section --}}
    <div class="amount-highlight">
        <div style="font-size: 14px; margin-bottom: 5px;">TOTAL AMOUNT PAID</div>
        <div style="font-size: 24px;">₹{{ number_format($payment->amount, 2) }}</div>
        <div style="font-size: 10px; margin-top: 5px; opacity: 0.8;">
            ({{ ucwords(\App\Helpers\NumberToWords::convert($payment->amount)) }} Rupees Only)
        </div>
    </div>

    {{-- Notes Section --}}
    @if($payment->notes)
    <div class="info-group">
        <div class="info-title">Payment Notes</div>
        <div style="margin-top: 10px;">{{ $payment->notes }}</div>
    </div>
    @endif

    {{-- Signature Section --}}
    <div class="signature-section">
        <div class="signature">
            <div class="signature-line">Employee Signature</div>
        </div>
        <div class="signature">
            <div class="signature-line">Authorized Signature</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p style="font-size: 10px; color: #666; margin: 5px 0;">
            This is a computer-generated payment receipt and does not require a physical signature.
        </p>
        <p style="font-size: 10px; color: #666; margin: 5px 0;">
            Generated on: {{ $generatedAt->format('d F, Y \a\t H:i:s') }}
        </p>
        <p style="font-size: 10px; color: #666; margin: 5px 0;">
            For any queries, please contact HR department.
        </p>
    </div>
</body>

</html>