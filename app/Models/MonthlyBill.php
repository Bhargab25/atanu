<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyBill extends Model
{
    protected $fillable = [
        'bill_number', 'client_id', 'bill_date', 'period_from', 'period_to',
        'total_amount', 'invoice_count', 'status', 'notes'
    ];

    protected $casts = [
        'bill_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bill) {
            if (empty($bill->bill_number)) {
                $bill->bill_number = static::generateBillNumber();
            }
        });
    }

    public static function generateBillNumber()
    {
        $year = date('Y');
        $month = date('m');
        
        $lastBill = static::where('bill_number', 'like', "MB-$year$month-%")
                         ->orderBy('bill_number', 'desc')
                         ->first();

        if ($lastBill) {
            $lastNumber = intval(substr($lastBill->bill_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return "MB-$year$month-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
