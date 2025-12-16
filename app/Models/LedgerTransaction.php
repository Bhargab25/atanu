<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LedgerTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_profile_id',
        'invoice_id',
        'ledger_id',
        'date',
        'type',
        'description',
        'debit_amount',
        'credit_amount',
        'reference',
    ];

    protected $casts = [
        'date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(AccountLedger::class, 'ledger_id');
    }

    // Scopes
    public function scopeForCompany($query, $companyId = null)
    {
        if ($companyId) {
            return $query->where('company_profile_id', $companyId);
        }
        return $query;
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDebits($query)
    {
        return $query->where('debit_amount', '>', 0);
    }

    public function scopeCredits($query)
    {
        return $query->where('credit_amount', '>', 0);
    }

    // Accessors
    public function getAmountAttribute()
    {
        return $this->debit_amount > 0 ? $this->debit_amount : $this->credit_amount;
    }

    public function getTransactionTypeAttribute()
    {
        return $this->debit_amount > 0 ? 'debit' : 'credit';
    }

    public function getFormattedAmountAttribute()
    {
        return '₹' . number_format($this->amount, 2);
    }

    // Boot method to update ledger balance
    protected static function boot()
    {
        parent::boot();

        static::created(function ($transaction) {
            $transaction->ledger->updateBalance();
        });

        static::updated(function ($transaction) {
            $transaction->ledger->updateBalance();
        });

        static::deleted(function ($transaction) {
            $transaction->ledger->updateBalance();
        });
    }

    // Static methods for creating specific transaction types
    public static function createPurchase($companyId, $ledgerId, $amount, $description, $reference = null)
    {
        return static::create([
            'company_profile_id' => $companyId,
            'ledger_id' => $ledgerId,
            'date' => now(),
            'type' => 'purchase',
            'description' => $description,
            'credit_amount' => $amount,
            'debit_amount' => 0,
            'reference' => $reference,
        ]);
    }

    public static function createPayment($companyId, $ledgerId, $amount, $description, $reference = null)
    {
        return static::create([
            'company_profile_id' => $companyId,
            'ledger_id' => $ledgerId,
            'date' => now(),
            'type' => 'payment',
            'description' => $description,
            'debit_amount' => $amount,
            'credit_amount' => 0,
            'reference' => $reference,
        ]);
    }

    public static function createSale($companyId, $ledgerId, $amount, $description, $reference = null)
    {
        return static::create([
            'company_profile_id' => $companyId,
            'ledger_id' => $ledgerId,
            'date' => now(),
            'type' => 'sale',
            'description' => $description,
            'debit_amount' => $amount,
            'credit_amount' => 0,
            'reference' => $reference,
        ]);
    }

    // Analytics methods
    public static function getTotalByType($companyId, $type, $startDate = null, $endDate = null)
    {
        $query = static::forCompany($companyId)->byType($type);

        if ($startDate && $endDate) {
            $query->forDateRange($startDate, $endDate);
        }

        return [
            'total_debit' => $query->sum('debit_amount'),
            'total_credit' => $query->sum('credit_amount'),
        ];
    }

    public static function getMonthlyTransactions($companyId, $month = null, $year = null)
    {
        $month = $month ?? date('m');
        $year = $year ?? date('Y');

        return static::forCompany($companyId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with('ledger')
            ->orderBy('date', 'desc')
            ->get();
    }
}
