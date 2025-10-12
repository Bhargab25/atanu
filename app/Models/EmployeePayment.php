<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayment extends Model
{
    protected $fillable = [
        'employee_id',
        'payment_id',
        'payment_type',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'month_year',
        'status',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function getPaymentTypeLabelAttribute()
    {
        return match ($this->payment_type) {
            'salary' => 'Regular Salary',
            'bonus' => 'Bonus',
            'advance' => 'Advance',
            'overtime' => 'Overtime',
            'allowance' => 'Allowance',
            'adjustment' => 'Adjustment',
            default => ucfirst($this->payment_type)
        };
    }

    public function hasRegularSalaryForMonth($monthYear)
    {
        return $this->payments()
            ->where('month_year', $monthYear)
            ->where('payment_type', 'salary')
            ->where('status', 'paid')
            ->exists();
    }

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Generate unique payment ID
    public static function generatePaymentId()
    {
        $lastPayment = self::orderBy('id', 'desc')->first();
        $nextNumber = $lastPayment ? (int) substr($lastPayment->payment_id, 3) + 1 : 1;
        return 'PAY' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
