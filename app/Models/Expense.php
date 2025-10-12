<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Models\CompanyProfile;
use App\Models\ExpenseCategory;
use App\Models\User;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_profile_id',
        'expense_ref',
        'expense_title',
        'category_id',
        'amount',
        'description',
        'expense_date',
        'payment_method',
        'reference_number',
        'is_business_expense',
        'is_reimbursable',
        'reimbursed_to',
        'is_reimbursed',
        'reimbursed_date',
        'receipt_path',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'created_by'
    ];

    protected $casts = [
        'expense_date' => 'date',
        'reimbursed_date' => 'date',
        'approved_at' => 'timestamp',
        'is_business_expense' => 'boolean',
        'is_reimbursable' => 'boolean',
        'is_reimbursed' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Accessors
    public function getStatusBadgeClassAttribute()
    {
        return match ($this->approval_status) {
            'approved' => 'badge-success',
            'rejected' => 'badge-error',
            default => 'badge-warning'
        };
    }

    public function getPaymentMethodLabelAttribute()
    {
        return match ($this->payment_method) {
            'upi' => 'UPI',
            default => ucfirst($this->payment_method)
        };
    }

    public function getReceiptUrlAttribute()
    {
        if ($this->receipt_path && Storage::disk('public')->exists($this->receipt_path)) {
            return Storage::disk('public')->url($this->receipt_path);
        }
        return null;
    }

    public function getFormattedAmountAttribute()
    {
        return '₹' . number_format($this->amount, 2);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }

    public function scopeBusinessExpenses($query)
    {
        return $query->where('is_business_expense', true);
    }

    public function scopeReimbursable($query)
    {
        return $query->where('is_reimbursable', true);
    }

    public function scopeReimbursed($query)
    {
        return $query->where('is_reimbursed', true);
    }

    public function scopeForCompany($query, $companyId = null)
    {
        if ($companyId) {
            return $query->where('company_profile_id', $companyId);
        }
        return $query;
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    public function scopeForMonth($query, $month, $year = null)
    {
        $year = $year ?? date('Y');
        return $query->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    // Boot method for automatic reference generation - FIXED
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            if (empty($expense->expense_ref)) {
                // Pass the company_profile_id to the generateUniqueExpenseRef method
                $expense->expense_ref = static::generateUniqueExpenseRef($expense->company_profile_id);
            }
        });
    }

    public static function generateUniqueExpenseRef($companyId)
    {
        // Validate that companyId is provided
        if (!$companyId) {
            throw new \InvalidArgumentException('Company ID is required to generate expense reference');
        }

        $prefix = 'EXP';
        $year = date('Y');
        $month = date('m');
        $date = date('d');

        // Use database transaction to prevent race conditions
        return DB::transaction(function () use ($prefix, $year, $month, $date, $companyId) {
            // Lock the table to prevent concurrent inserts for this company
            $lastExpense = static::lockForUpdate()
                ->where('company_profile_id', $companyId)
                ->where('expense_ref', 'like', "$prefix-$year$month$date-%")
                ->orderBy('expense_ref', 'desc')
                ->first();

            if ($lastExpense) {
                // Extract the sequence number from the last reference
                $lastNumber = intval(substr($lastExpense->expense_ref, -4));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $expenseRef = "$prefix-$year$month$date-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

            // Double-check uniqueness within the company
            $attempts = 0;
            while (
                static::where('company_profile_id', $companyId)
                ->where('expense_ref', $expenseRef)
                ->exists() && $attempts < 10
            ) {
                $newNumber++;
                $expenseRef = "$prefix-$year$month$date-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
                $attempts++;
            }

            if ($attempts >= 10) {
                throw new \Exception('Unable to generate unique expense reference after 10 attempts');
            }

            return $expenseRef;
        });
    }

    // Helper methods for calculations
    public static function getTotalForCompany($companyId, $startDate = null, $endDate = null)
    {
        $query = static::forCompany($companyId)->approved();

        if ($startDate && $endDate) {
            $query->forDateRange($startDate, $endDate);
        }

        return $query->sum('amount');
    }

    public static function getTotalByCategory($companyId, $categoryId, $startDate = null, $endDate = null)
    {
        $query = static::forCompany($companyId)
            ->where('category_id', $categoryId)
            ->approved();

        if ($startDate && $endDate) {
            $query->forDateRange($startDate, $endDate);
        }

        return $query->sum('amount');
    }

    public static function getAverageMonthlyExpense($companyId, $months = 12)
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $total = static::getTotalForCompany($companyId, $startDate, $endDate);

        return $total / $months;
    }

    // Status management methods
    public function approve($approverId = null, $notes = null)
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approverId ?? auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    public function reject($approverId = null, $notes = null)
    {
        $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $approverId ?? auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    public function markAsReimbursed($reimbursedDate = null)
    {
        if (!$this->is_reimbursable) {
            throw new \Exception('This expense is not marked as reimbursable');
        }

        $this->update([
            'is_reimbursed' => true,
            'reimbursed_date' => $reimbursedDate ?? now()->toDateString(),
        ]);
    }
}
