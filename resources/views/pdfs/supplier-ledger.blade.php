<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Supplier Ledger - {{ $supplier->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .supplier-info { margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Supplier Ledger Report</h2>
        <p>Generated on: {{ $generatedAt }}</p>
    </div>

    <div class="supplier-info">
        <h3>{{ $supplier->name }}</h3>
        <p><strong>Contact:</strong> {{ $supplier->contact_person ?? '-' }}</p>
        <p><strong>Phone:</strong> {{ $supplier->phone ?? '-' }}</p>
        <p><strong>Email:</strong> {{ $supplier->email ?? '-' }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th>Debit (₹)</th>
                <th>Credit (₹)</th>
                <th>Balance (₹)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
            <tr>
                <td>{{ $transaction->date->format('d/m/Y') }}</td>
                <td>{{ ucfirst($transaction->type) }}</td>
                <td>{{ $transaction->description }}</td>
                <td class="text-right font-mono">
                    {{ $transaction->debit_amount > 0 ? number_format($transaction->debit_amount, 2) : '' }}
                </td>
                <td class="text-right font-mono">
                    {{ $transaction->credit_amount > 0 ? number_format($transaction->credit_amount, 2) : '' }}
                </td>
                <td class="text-right font-mono">
                    {{ number_format(abs($transaction->running_balance), 2) }} {{ $transaction->running_balance >= 0 ? 'Dr' : 'Cr' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">No transactions found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
