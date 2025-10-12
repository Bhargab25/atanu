<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_profile_id',
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

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }

    public function ledgerable(): MorphTo
    {
        return $this->morphTo();
    }


    public function transactions(): HasMany
    {
        return $this->hasMany(LedgerTransaction::class, 'ledger_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, $companyId = null)
    {
        if ($companyId) {
            return $query->where('company_profile_id', $companyId);
        }
        return $query;
    }

    public function scopeByType($query, $type)
    {
        return $query->where('ledger_type', $type);
    }

    public function scopeEmployees($query)
    {
        return $query->where('ledger_type', 'employee');
    }

    public function scopeExpenses($query)
    {
        return $query->where('ledger_type', 'expenses');
    }

    public function scopeClients($query)
    {
        return $query->where('ledger_type', 'client');
    }

    public function scopeCashAccounts($query)
    {
        return $query->where('ledger_type', 'cash');
    }

    public function scopeBankAccounts($query)
    {
        return $query->where('ledger_type', 'bank');
    }

    // Helper methods
    public function getBalanceAttribute()
    {
        return $this->current_balance;
    }

    public function getFormattedBalanceAttribute()
    {
        return '₹' . number_format($this->current_balance, 2);
    }

    public function getBalanceTypeAttribute()
    {
        return $this->current_balance >= 0 ? 'debit' : 'credit';
    }

    // Update current balance based on transaction
    public function updateBalance()
    {
        $totalDebits = $this->transactions()->sum('debit_amount');
        $totalCredits = $this->transactions()->sum('credit_amount');

        // Calculate current balance based on opening balance
        if ($this->opening_balance_type === 'debit') {
            $this->current_balance = $this->opening_balance + $totalDebits - $totalCredits;
        } else {
            $this->current_balance = $totalCredits - $totalDebits - $this->opening_balance;
        }

        $this->save();
        return $this->current_balance;
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


    // Static methods for company-specific operations
    public static function createForEmployee($companyId, $employee)
    {
        return static::create([
            'company_profile_id' => $companyId,
            'ledger_name' => $employee->name,
            'ledger_type' => 'employee',
            'ledgerable_id' => $employee->id,
            'ledgerable_type' => get_class($employee),
            'opening_balance' => 0,
            'opening_balance_type' => 'credit',
            'current_balance' => 0,
            'is_active' => true,
        ]);
    }
    public static function createForClient($companyId, $client)
    {
        return static::create([
            'company_profile_id' => $companyId,
            'ledger_name' => $client->name,
            'ledger_type' => 'client',
            'ledgerable_id' => $client->id,
            'ledgerable_type' => get_class($client),
            'opening_balance' => 0,
            'opening_balance_type' => 'debit',
            'current_balance' => 0,
            'is_active' => true,
        ]);
    }
    public static function createForExpense($companyId, $expense)
    {
        return static::create([
            'company_profile_id' => $companyId,
            'ledger_name' => $expense->title,
            'ledger_type' => 'expenses',
            'ledgerable_id' => $expense->id,
            'ledgerable_type' => get_class($expense),
            'opening_balance' => 0,
            'opening_balance_type' => 'debit',
            'current_balance' => 0,
            'is_active' => true,
        ]);
    }
    public static function getCashLedger($companyId)
    {
        return static::forCompany($companyId)
            ->where('ledger_type', 'cash')
            ->where('ledger_name', 'Cash in Hand')
            ->first();
    }

    public static function getOrCreateCashLedger($companyId)
    {
        $cashLedger = static::getCashLedger($companyId);

        if (!$cashLedger) {
            $cashLedger = static::create([
                'company_profile_id' => $companyId,
                'ledger_name' => 'Cash in Hand',
                'ledger_type' => 'cash',
                'opening_balance' => 0,
                'opening_balance_type' => 'debit',
                'current_balance' => 0,
                'is_active' => true,
            ]);
        }

        return $cashLedger;
    }

    // Analytics methods
    public static function getTotalBalanceByType($companyId, $type)
    {
        return static::forCompany($companyId)
            ->byType($type)
            ->active()
            ->sum('current_balance');
    }

    public static function getEmployeeBalances($companyId)
    {
        return static::forCompany($companyId)
            ->employees()
            ->active()
            ->with('ledgerable')
            ->get()
            ->map(function ($ledger) {
                return [
                    'name' => $ledger->ledger_name,
                    'balance' => $ledger->current_balance,
                    'employee' => $ledger->ledgerable,
                ];
            });
    }

    public static function getClientBalances($companyId)
    {
        return static::forCompany($companyId)
            ->clients()
            ->active()
            ->with('ledgerable')
            ->get()
            ->map(function ($ledger) {
                return [
                    'name' => $ledger->ledger_name,
                    'balance' => $ledger->current_balance,
                    'client' => $ledger->ledgerable,
                ];
            });
    }

    public static function createForExpenseCategory($companyId, $category)
    {
        return static::create([
            'company_profile_id' => $companyId,
            'ledger_name' => $category->name . ' Expenses',
            'ledger_type' => 'expenses',
            'ledgerable_id' => $category->id,
            'ledgerable_type' => get_class($category),
            'opening_balance' => 0,
            'opening_balance_type' => 'debit',
            'current_balance' => 0,
            'is_active' => true,
        ]);
    }

    public static function getOrCreateExpenseLedger($companyId, $category)
    {
        $expenseLedger = static::forCompany($companyId)
            ->where('ledger_type', 'expenses')
            ->where('ledgerable_type', get_class($category))
            ->where('ledgerable_id', $category->id)
            ->first();

        if (!$expenseLedger) {
            $expenseLedger = static::createForExpenseCategory($companyId, $category);
        }

        return $expenseLedger;
    }

    public static function getOrCreateBankLedger($companyId, $paymentMethod)
    {
        $ledgerName = match ($paymentMethod) {
            'bank' => 'Bank Account',
            'upi' => 'UPI Payments',
            'card' => 'Card Payments',
            'cheque' => 'Cheque Payments',
            default => 'Bank Account'
        };

        $bankLedger = static::forCompany($companyId)
            ->where('ledger_type', 'bank')
            ->where('ledger_name', $ledgerName)
            ->first();

        if (!$bankLedger) {
            $bankLedger = static::create([
                'company_profile_id' => $companyId,
                'ledger_name' => $ledgerName,
                'ledger_type' => 'bank',
                'opening_balance' => 0,
                'opening_balance_type' => 'debit',
                'current_balance' => 0,
                'is_active' => true,
            ]);
        }

        return $bankLedger;
    }

    public static function getExpenseBalances($companyId)
    {
        return static::forCompany($companyId)
            ->expenses()
            ->active()
            ->with('ledgerable')
            ->get()
            ->map(function ($ledger) {
                return [
                    'name' => $ledger->ledger_name,
                    'balance' => $ledger->current_balance,
                    'category' => $ledger->ledgerable,
                ];
            });
    }

    public static function getCashAndBankSummary($companyId)
    {
        return static::forCompany($companyId)
            ->whereIn('ledger_type', ['cash', 'bank'])
            ->active()
            ->get()
            ->map(function ($ledger) {
                return [
                    'name' => $ledger->ledger_name,
                    'type' => $ledger->ledger_type,
                    'balance' => $ledger->current_balance,
                ];
            });
    }

    public static function getOrCreateClientLedger($companyId, $client)
    {
        $clientLedger = static::forCompany($companyId)
            ->where('ledger_type', 'client')
            ->where('ledgerable_type', get_class($client))
            ->where('ledgerable_id', $client->id)
            ->first();

        if (!$clientLedger) {
            $clientLedger = static::createForClient($companyId, $client);
        }

        return $clientLedger;
    }

    public static function getOrCreateIncomeLedger($companyId)
    {
        $incomeLedger = static::forCompany($companyId)
            ->where('ledger_type', 'income')
            ->where('ledger_name', 'Sales Revenue')
            ->first();

        if (!$incomeLedger) {
            $incomeLedger = static::create([
                'company_profile_id' => $companyId,
                'ledger_name' => 'Sales Revenue',
                'ledger_type' => 'income',
                'opening_balance' => 0,
                'opening_balance_type' => 'credit',
                'current_balance' => 0,
                'is_active' => true,
            ]);
        }

        return $incomeLedger;
    }
}
