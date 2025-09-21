<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountLedger extends Model
{
    protected $fillable = [
        'ledger_name',
        'ledger_type',
        'ledgerable_id',
        'ledgerable_type',
        'opening_balance',
        'opening_balance_type',
        'current_balance',
        'is_active'
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function ledgerable()
    {
        return $this->morphTo();
    }

    public function transactions()
    {
        return $this->hasMany(LedgerTransaction::class, 'ledger_id');
    }

    // Update current balance based on transaction
    public function updateBalance($debitAmount = 0, $creditAmount = 0)
    {
        // For supplier ledgers: debit increases balance, credit decreases
        if ($this->ledger_type === 'supplier') {
            $this->current_balance += ($debitAmount - $creditAmount);
        } else {
            // For other types, adjust logic as needed
            $this->current_balance += ($debitAmount - $creditAmount);
        }
        $this->save();
    }

    public function recalculateBalance()
    {
        $totalDebits = $this->transactions()->sum('debit_amount');
        $totalCredits = $this->transactions()->sum('credit_amount');

        $this->current_balance = $this->opening_balance + ($totalDebits - $totalCredits);
        $this->save();
    }

    // Get formatted balance with Dr/Cr
    public function getFormattedBalance()
    {
        $amount = abs($this->current_balance);
        $type = $this->current_balance >= 0 ? 'Dr' : 'Cr';
        return '₹' . number_format($amount, 2) . ' ' . $type;
    }
}
