<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayment extends Model
{
    protected $fillable = [
        'employee_id',
        'payment_id',
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
