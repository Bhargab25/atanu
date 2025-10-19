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

    // ADD: Income scope for profit/loss tracking
    public function scopeIncome($query)
    {
        return $query->where('ledger_type', 'income');
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

    // FIXED: Proper balance calculation based on ledger type
    public function updateBalance()
    {
        $totalDebits = $this->transactions()->sum('debit_amount');
        $totalCredits = $this->transactions()->sum('credit_amount');

        // Calculate balance based on account type and opening balance type
        switch ($this->ledger_type) {
            case 'cash':
            case 'bank':
            case 'client':
            case 'expenses':
                // Asset and Expense accounts: Debit increases, Credit decreases
                if ($this->opening_balance_type === 'debit') {
                    $this->current_balance = $this->opening_balance + $totalDebits - $totalCredits;
                } else {
                    $this->current_balance = $totalDebits - $totalCredits - $this->opening_balance;
                }
                break;

            case 'employee':
            case 'income':
                // Liability and Income accounts: Credit increases, Debit decreases
                if ($this->opening_balance_type === 'credit') {
                    $this->current_balance = $this->opening_balance + $totalCredits - $totalDebits;
                } else {
                    $this->current_balance = $totalCredits - $totalDebits - $this->opening_balance;
                }
                break;

            default:
                // Default calculation
                if ($this->opening_balance_type === 'debit') {
                    $this->current_balance = $this->opening_balance + $totalDebits - $totalCredits;
                } else {
                    $this->current_balance = $this->opening_balance + $totalCredits - $totalDebits;
                }
        }

        $this->save();
        return $this->current_balance;
    }

    // FIXED: Simplified recalculation method
    public function recalculateBalance()
    {
        return $this->updateBalance();
    }

    // Get formatted balance with Dr/Cr based on ledger type
    public function getFormattedBalance()
    {
        $amount = abs($this->current_balance);

        // Determine if balance is normal for this account type
        $isNormalBalance = match ($this->ledger_type) {
            'cash', 'bank', 'client', 'expenses' => $this->current_balance >= 0, // Asset/Expense: Debit normal
            'employee', 'income' => $this->current_balance <= 0, // Liability/Income: Credit normal
            default => $this->current_balance >= 0
        };

        $type = $isNormalBalance ?
            (in_array($this->ledger_type, ['cash', 'bank', 'client', 'expenses']) ? 'Dr' : 'Cr') : (in_array($this->ledger_type, ['cash', 'bank', 'client', 'expenses']) ? 'Cr' : 'Dr');

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
            'opening_balance_type' => 'credit', // Liability account
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
            'opening_balance_type' => 'debit', // Asset account (Accounts Receivable)
            'current_balance' => 0,
            'is_active' => true,
        ]);
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
            'opening_balance_type' => 'debit', // Expense account
            'current_balance' => 0,
            'is_active' => true,
        ]);
    }

    public static function getOrCreateCashLedger($companyId)
    {
        $cashLedger = static::forCompany($companyId)
            ->where('ledger_type', 'cash')
            ->where('ledger_name', 'Cash in Hand')
            ->first();

        if (!$cashLedger) {
            $cashLedger = static::create([
                'company_profile_id' => $companyId,
                'ledger_name' => 'Cash in Hand',
                'ledger_type' => 'cash',
                'opening_balance' => 0,
                'opening_balance_type' => 'debit', // Asset account
                'current_balance' => 0,
                'is_active' => true,
            ]);
        }

        return $cashLedger;
    }

    public static function getOrCreateBankLedger($companyId, $paymentMethod)
    {
        $ledgerName = match ($paymentMethod) {
            'bank_transfer' => 'Bank Account',
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
                'opening_balance_type' => 'debit', // Asset account
                'current_balance' => 0,
                'is_active' => true,
            ]);
        }

        return $bankLedger;
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
                'opening_balance_type' => 'credit', // Income account
                'current_balance' => 0,
                'is_active' => true,
            ]);
        }

        return $incomeLedger;
    }

    // Analytics methods for Profit/Loss tracking
    public static function getTotalIncome($companyId, $startDate = null, $endDate = null)
    {
        $query = static::forCompany($companyId)->income()->active();

        if ($startDate && $endDate) {
            $query->whereHas('transactions', function ($transactionQuery) use ($startDate, $endDate) {
                $transactionQuery->whereBetween('date', [$startDate, $endDate]);
            });
        }

        return abs($query->sum('current_balance')); // Income should be positive for profit calculation
    }

    public static function getTotalExpenses($companyId, $startDate = null, $endDate = null)
    {
        $query = static::forCompany($companyId)->expenses()->active();

        if ($startDate && $endDate) {
            $query->whereHas('transactions', function ($transactionQuery) use ($startDate, $endDate) {
                $transactionQuery->whereBetween('date', [$startDate, $endDate]);
            });
        }

        return abs($query->sum('current_balance')); // Expenses should be positive for profit calculation
    }

    public static function getNetProfit($companyId, $startDate = null, $endDate = null)
    {
        $income = static::getTotalIncome($companyId, $startDate, $endDate);
        $expenses = static::getTotalExpenses($companyId, $startDate, $endDate);

        return $income - $expenses;
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

    // Additional analytics methods
    public static function getTotalBalanceByType($companyId, $type)
    {
        return static::forCompany($companyId)
            ->byType($type)
            ->active()
            ->sum('current_balance');
    }
}
