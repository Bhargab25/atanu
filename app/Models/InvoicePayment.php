<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class InvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'payment_reference',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Generate unique payment reference
    public static function generatePaymentReference()
    {
        $prefix = 'PAY';
        $year = date('Y');
        $month = date('m');
        $date = date('d');

        return DB::transaction(function () use ($prefix, $year, $month, $date) {
            $lastPayment = static::lockForUpdate()
                ->where('payment_reference', 'like', "$prefix-$year$month$date-%")
                ->orderBy('payment_reference', 'desc')
                ->first();

            if ($lastPayment) {
                $lastNumber = intval(substr($lastPayment->payment_reference, -4));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            return "$prefix-$year$month$date-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    // Accessors
    public function getPaymentMethodLabelAttribute()
    {
        return match ($this->payment_method) {
            'upi' => 'UPI',
            default => ucfirst($this->payment_method)
        };
    }

    public function getFormattedAmountAttribute()
    {
        return '₹' . number_format($this->amount, 2);
    }
}
