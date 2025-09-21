<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number', 'invoice_type', 'invoice_date', 'due_date',
        'client_id', 'client_name', 'client_phone', 'client_address',
        'is_gst_invoice', 'client_gstin', 'place_of_supply', 'gst_type',
        'subtotal', 'discount_amount', 'discount_percentage',
        'cgst_amount', 'sgst_amount', 'igst_amount', 'total_tax', 'total_amount',
        'paid_amount', 'balance_amount', 'payment_status',
        'is_monthly_billed', 'monthly_bill_id', 'is_cancelled', 'cancelled_at',
        'cancellation_reason', 'notes', 'terms_conditions', 'created_by'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'is_gst_invoice' => 'boolean',
        'is_monthly_billed' => 'boolean',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'timestamp',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function monthlyBill()
    {
        return $this->belongsTo(MonthlyBill::class);
    }

    public function ledgerTransactions()
    {
        return $this->morphMany(LedgerTransaction::class, 'reference');
    }

    // Accessors & Mutators
    public function getDisplayClientNameAttribute()
    {
        return $this->client ? $this->client->name : $this->client_name;
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->payment_status) {
            'paid' => 'badge-success',
            'partial' => 'badge-warning',
            'overdue' => 'badge-error',
            default => 'badge-info'
        };
    }

    // Scopes
    public function scopeCashInvoices($query)
    {
        return $query->where('invoice_type', 'cash');
    }

    public function scopeClientInvoices($query)
    {
        return $query->where('invoice_type', 'client');
    }

    public function scopeUnbilled($query)
    {
        return $query->where('is_monthly_billed', false)
                    ->where('invoice_type', 'client');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('payment_status', '!=', 'paid');
    }

    // Boot method for automatic invoice number generation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber($invoice->invoice_type);
            }
        });

        static::deleting(function ($invoice) {
            // Restore stock when invoice is deleted
            $invoice->restoreStock();
            
            // Create ledger adjustment entries
            $invoice->createCancellationLedgerEntries();
        });
    }

    public static function generateInvoiceNumber($type = 'cash')
    {
        $prefix = $type === 'cash' ? 'CASH' : 'INV';
        $year = date('Y');
        $month = date('m');
        
        $lastInvoice = static::where('invoice_number', 'like', "$prefix-$year$month-%")
                           ->orderBy('invoice_number', 'desc')
                           ->first();

        if ($lastInvoice) {
            $lastNumber = intval(substr($lastInvoice->invoice_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return "$prefix-$year$month-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function restoreStock()
    {
        foreach ($this->items as $item) {
            // Restore stock
            $product = $item->product;
            $product->stock_quantity += $item->quantity;
            $product->save();

            // Create stock movement record
            StockMovement::create([
                'product_id' => $item->product_id,
                'type' => 'in',
                'quantity' => $item->quantity,
                'reason' => 'invoice_cancellation',
                'reference_type' => Invoice::class,
                'reference_id' => $this->id,
            ]);
        }
    }

    public function createCancellationLedgerEntries()
    {
        if ($this->client_id && $this->client->ledger) {
            // Create reversal entry in client ledger
            LedgerTransaction::create([
                'ledger_id' => $this->client->ledger->id,
                'date' => now(),
                'type' => 'adjustment',
                'description' => "Invoice cancellation - {$this->invoice_number}",
                'credit_amount' => $this->total_amount, // Reverse the debit
                'reference' => "CANCEL-{$this->invoice_number}",
            ]);
        }
    }

    public function calculateTotals()
    {
        $this->subtotal = $this->items->sum('taxable_amount');
        $this->cgst_amount = $this->items->sum('cgst_amount');
        $this->sgst_amount = $this->items->sum('sgst_amount');
        $this->igst_amount = $this->items->sum('igst_amount');
        $this->total_tax = $this->cgst_amount + $this->sgst_amount + $this->igst_amount;
        $this->total_amount = $this->subtotal + $this->total_tax - $this->discount_amount;
        $this->balance_amount = $this->total_amount - $this->paid_amount;
        
        $this->save();
    }
}
