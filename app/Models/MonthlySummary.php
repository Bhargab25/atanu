<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlySummary extends Model
{
    protected $fillable = [
        'summary_number',
        'client_id',
        'period_start',
        'period_end',
        'total_amount',
        'invoice_count',
        'status',
        'pdf_path'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'monthly_summary_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($summary) {
            if (!$summary->summary_number) {
                $summary->summary_number = 'MS-' . now()->format('YmdHis');
            }
        });
    }
}
