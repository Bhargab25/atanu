<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
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
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
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

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeBusinessExpenses($query)
    {
        return $query->where('is_business_expense', true);
    }

    public function scopeReimbursable($query)
    {
        return $query->where('is_reimbursable', true);
    }

    // Boot method for automatic reference generation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            if (empty($expense->expense_ref)) {
                $expense->expense_ref = static::generateUniqueExpenseRef();
            }
        });
    }

    public static function generateUniqueExpenseRef()
    {
        $prefix = 'EXP';
        $year = date('Y');
        $month = date('m');
        $date = date('d');

        // Use database transaction to prevent race conditions
        return DB::transaction(function () use ($prefix, $year, $month, $date) {
            // Lock the table to prevent concurrent inserts
            $lastExpense = static::lockForUpdate()
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

            // Double-check uniqueness
            $attempts = 0;
            while (static::where('expense_ref', $expenseRef)->exists() && $attempts < 10) {
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
}
