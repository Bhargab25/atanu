<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerTransaction extends Model
{
protected $fillable = [
        'ledger_id', 'date', 'type', 'description', 
        'debit_amount', 'credit_amount', 'reference'
    ];

    protected $casts = [
        'date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
    ];

    public function ledger()
    {
        return $this->belongsTo(AccountLedger::class, 'ledger_id');
    }

    // Boot method to update ledger balance on create/update/delete
    protected static function boot()
    {
        parent::boot();

        static::created(function ($transaction) {
            $transaction->ledger->updateBalance(
                $transaction->debit_amount, 
                $transaction->credit_amount
            );
        });

        static::updated(function ($transaction) {
            // Recalculate entire ledger balance
            $transaction->ledger->recalculateBalance();
        });

        static::deleted(function ($transaction) {
            $transaction->ledger->recalculateBalance();
        });
    }
}
