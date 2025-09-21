<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Client Ledger - {{ $client->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .company-info {
            margin-bottom: 20px;
        }
        
        .client-info {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .client-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .detail-group h4 {
            margin: 0 0 10px 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .detail-item {
            margin-bottom: 5px;
        }
        
        .label {
            font-weight: bold;
            color: #666;
            width: 120px;
            display: inline-block;
        }
        
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .transactions-table th,
        .transactions-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .transactions-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
        
        .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .debit {
            color: #28a745;
        }
        
        .credit {
            color: #dc3545;
        }
        
        .balance {
            font-weight: bold;
        }
        
        .summary {
            margin-top: 30px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>CLIENT LEDGER</h1>
        <h2>{{ $client->name }}</h2>
        <p>Generated on: {{ $generatedAt }}</p>
    </div>

    {{-- Client Information --}}
    <div class="client-info">
        <div class="client-details">
            <div class="detail-group">
                <h4>Basic Information</h4>
                <div class="detail-item">
                    <span class="label">Name:</span> {{ $client->name }}
                </div>
                @if($client->company)
                <div class="detail-item">
                    <span class="label">Company:</span> {{ $client->company }}
                </div>
                @endif
                @if($client->contact_person)
                <div class="detail-item">
                    <span class="label">Contact Person:</span> {{ $client->contact_person }}
                </div>
                @endif
                @if($client->phone)
                <div class="detail-item">
                    <span class="label">Phone:</span> {{ $client->phone }}
                </div>
                @endif
                @if($client->email)
                <div class="detail-item">
                    <span class="label">Email:</span> {{ $client->email }}
                </div>
                @endif
            </div>
            
            <div class="detail-group">
                <h4>Address & Business Info</h4>
                @if($client->address)
                <div class="detail-item">
                    <span class="label">Address:</span> {{ $client->address }}
                </div>
                @endif
                @if($client->city)
                <div class="detail-item">
                    <span class="label">City:</span> {{ $client->city }}, {{ $client->state }}
                </div>
                @endif
                @if($client->gstin)
                <div class="detail-item">
                    <span class="label">GSTIN:</span> {{ $client->gstin }}
                </div>
                @endif
                @if($client->pan)
                <div class="detail-item">
                    <span class="label">PAN:</span> {{ $client->pan }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Account Summary --}}
    <div class="summary">
        <h3>Account Summary</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div>
                <span class="label">Opening Balance:</span><br>
                ₹{{ number_format($client->ledger->opening_balance ?? 0, 2) }}
            </div>
            <div>
                <span class="label">Current Balance:</span><br>
                <strong>₹{{ number_format(abs($client->ledger->current_balance ?? 0), 2) }} {{ ($client->ledger->current_balance ?? 0) >= 0 ? 'Dr' : 'Cr' }}</strong>
            </div>
            <div>
                <span class="label">Total Transactions:</span><br>
                {{ $transactions->count() }}
            </div>
        </div>
    </div>

    {{-- Transaction History --}}
    <h3>Transaction History (Last 6 Months)</h3>
    
    @if($transactions->count() > 0)
    <table class="transactions-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th>Reference</th>
                <th>Debit (Dr)</th>
                <th>Credit (Cr)</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction->date->format('d/m/Y') }}</td>
                <td>
                    <span style="
                        @if($transaction->type === 'sale') background-color: #e3f2fd; color: #1976d2;
                        @elseif($transaction->type === 'payment') background-color: #e8f5e8; color: #2e7d32;
                        @elseif($transaction->type === 'return') background-color: #fff3e0; color: #f57c00;
                        @else background-color: #f3e5f5; color: #7b1fa2;
                        @endif
                        padding: 2px 6px; border-radius: 3px; font-size: 10px;">
                        {{ ucfirst($transaction->type) }}
                    </span>
                </td>
                <td>{{ $transaction->description }}</td>
                <td>{{ $transaction->reference ?? '-' }}</td>
                <td class="amount debit">
                    {{ $transaction->debit_amount > 0 ? '₹' . number_format($transaction->debit_amount, 2) : '' }}
                </td>
                <td class="amount credit">
                    {{ $transaction->credit_amount > 0 ? '₹' . number_format($transaction->credit_amount, 2) : '' }}
                </td>
                <td class="amount balance">
                    ₹{{ number_format(abs($transaction->running_balance), 2) }} {{ $transaction->running_balance >= 0 ? 'Dr' : 'Cr' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="text-align: center; padding: 40px; background-color: #f8f9fa; border-radius: 5px;">
        <p style="color: #666; font-size: 14px;">No transactions found for the selected period.</p>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
        <p>Generated from your Fruit Stall Management System on {{ $generatedAt }}</p>
    </div>
</body>
</html>
