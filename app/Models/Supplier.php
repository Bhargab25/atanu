<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'contact_person', 'phone', 'email', 'address', 'city', 'state', 
        'country', 'pincode', 'gstin', 'pan', 'tin', 'bank_name', 
        'account_number', 'ifsc_code', 'branch', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

   public function ledger()
    {
        return $this->morphOne(AccountLedger::class, 'ledgerable');
    }

    // Get or create ledger for this supplier
    public function getOrCreateLedger()
    {
        return $this->ledger ?: $this->ledger()->create([
            'ledger_name' => $this->name,
            'ledger_type' => 'supplier',
            'opening_balance' => 0,
            'opening_balance_type' => 'debit',
            'current_balance' => 0,
            'is_active' => true,
        ]);
    }
}
