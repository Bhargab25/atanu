<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_profile_id',
        'client_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'invoice_items',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'status',
        'payment_status',
        'paid_amount',
        'notes',
        'pdf_path',
        'sent_at',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'sent_at' => 'timestamp',
        'paid_at' => 'timestamp',
        'invoice_items' => 'array',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    // Scopes
    public function scopeForCompany($query, $companyId = null)
    {
        if ($companyId) {
            return $query->where('company_profile_id', $companyId);
        }
        return $query;
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'unpaid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('payment_status', '!=', 'paid');
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('invoice_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            'draft' => 'badge-warning',
            'sent' => 'badge-info',
            'paid' => 'badge-success',
            'overdue' => 'badge-error',
            'cancelled' => 'badge-neutral',
            default => 'badge-warning'
        };
    }

    public function getPaymentStatusBadgeClassAttribute()
    {
        return match ($this->payment_status) {
            'paid' => 'badge-success',
            'partially_paid' => 'badge-warning',
            'unpaid' => 'badge-error',
            default => 'badge-error'
        };
    }

    public function getFormattedTotalAttribute()
    {
        return '₹' . number_format($this->total_amount, 2);
    }

    public function getOutstandingAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_date && $this->due_date->isPast() && $this->payment_status !== 'paid';
    }

    public function getPdfUrlAttribute()
    {
        if ($this->pdf_path && Storage::disk('public')->exists($this->pdf_path)) {
            return Storage::disk('public')->url($this->pdf_path);
        }
        return null;
    }

    // Boot method for automatic invoice number generation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber($invoice->company_profile_id);
            }

            // Set due date if not provided (default: 30 days)
            if (empty($invoice->due_date)) {
                $invoice->due_date = $invoice->invoice_date->addDays(30);
            }
        });
    }

    public static function generateInvoiceNumber($companyId)
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        $date = date('d');

        return DB::transaction(function () use ($prefix, $year, $month, $date, $companyId) {
            $lastInvoice = static::lockForUpdate()
                ->where('company_profile_id', $companyId)
                ->where('invoice_number', 'like', "$prefix-$year$month$date-%")
                ->orderBy('invoice_number', 'desc')
                ->first();

            if ($lastInvoice) {
                $lastNumber = intval(substr($lastInvoice->invoice_number, -4));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $invoiceNumber = "$prefix-$year$month$date-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

            $attempts = 0;
            while (static::where('company_profile_id', $companyId)
                        ->where('invoice_number', $invoiceNumber)
                        ->exists() && $attempts < 10) {
                $newNumber++;
                $invoiceNumber = "$prefix-$year$month$date-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
                $attempts++;
            }

            if ($attempts >= 10) {
                throw new \Exception('Unable to generate unique invoice number after 10 attempts');
            }

            return $invoiceNumber;
        });
    }

    // Payment methods
    public function addPayment($amount, $paymentDate, $paymentMethod = 'cash', $referenceNumber = null, $notes = null)
    {
        return DB::transaction(function () use ($amount, $paymentDate, $paymentMethod, $referenceNumber, $notes) {
            // Create payment record
            $payment = $this->payments()->create([
                'payment_reference' => InvoicePayment::generatePaymentReference(),
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod,
                'reference_number' => $referenceNumber,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            // Update invoice paid amount
            $this->increment('paid_amount', $amount);

            // Update payment status
            $this->updatePaymentStatus();

            // Create ledger transactions
            $this->createPaymentLedgerTransactions($payment);

            return $payment;
        });
    }

    public function updatePaymentStatus()
    {
        if ($this->paid_amount >= $this->total_amount) {
            $this->update([
                'payment_status' => 'paid',
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        } elseif ($this->paid_amount > 0) {
            $this->update(['payment_status' => 'partially_paid']);
        } else {
            $this->update(['payment_status' => 'unpaid']);
        }
    }

    private function createPaymentLedgerTransactions($payment)
    {
        // Get or create client ledger
        $clientLedger = AccountLedger::getOrCreateClientLedger($this->company_profile_id, $this->client);

        // Credit client ledger (payment received reduces what they owe)
        LedgerTransaction::create([
            'company_profile_id' => $this->company_profile_id,
            'ledger_id' => $clientLedger->id,
            'date' => $payment->payment_date,
            'type' => 'payment',
            'description' => "Payment received for Invoice {$this->invoice_number}",
            'debit_amount' => 0,
            'credit_amount' => $payment->amount,
            'reference' => $payment->payment_reference,
        ]);

        // Debit cash/bank ledger (cash/bank increases)
        if ($payment->payment_method === 'cash') {
            $paymentLedger = AccountLedger::getOrCreateCashLedger($this->company_profile_id);
        } else {
            $paymentLedger = AccountLedger::getOrCreateBankLedger($this->company_profile_id, $payment->payment_method);
        }

        LedgerTransaction::create([
            'company_profile_id' => $this->company_profile_id,
            'ledger_id' => $paymentLedger->id,
            'date' => $payment->payment_date,
            'type' => 'payment',
            'description' => "Payment received from {$this->client->name} via {$payment->payment_method}",
            'debit_amount' => $payment->amount,
            'credit_amount' => 0,
            'reference' => $payment->payment_reference,
        ]);
    }

    // Create invoice sale ledger entry (when invoice is created/sent)
    public function createSaleLedgerTransactions()
    {
        // Get or create client ledger
        $clientLedger = AccountLedger::getOrCreateClientLedger($this->company_profile_id, $this->client);

        // Debit client ledger (they owe us money)
        LedgerTransaction::create([
            'company_profile_id' => $this->company_profile_id,
            'ledger_id' => $clientLedger->id,
            'date' => $this->invoice_date,
            'type' => 'sale',
            'description' => "Invoice {$this->invoice_number} - {$this->client->name}",
            'debit_amount' => $this->total_amount,
            'credit_amount' => 0,
            'reference' => $this->invoice_number,
        ]);

        // Credit income/revenue ledger (income increases)
        $incomeLedger = AccountLedger::getOrCreateIncomeLedger($this->company_profile_id);

        LedgerTransaction::create([
            'company_profile_id' => $this->company_profile_id,
            'ledger_id' => $incomeLedger->id,
            'date' => $this->invoice_date,
            'type' => 'sale',
            'description' => "Sale to {$this->client->name} - Invoice {$this->invoice_number}",
            'debit_amount' => 0,
            'credit_amount' => $this->total_amount,
            'reference' => $this->invoice_number,
        ]);
    }

    // Mark invoice as sent
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Create ledger transactions when invoice is sent
        $this->createSaleLedgerTransactions();
    }

    // Cancel invoice
    public function cancel($reason = null)
    {
        return DB::transaction(function () use ($reason) {
            // Update status
            $this->update([
                'status' => 'cancelled',
                'notes' => $this->notes . "\n\nCancelled: " . ($reason ?? 'No reason provided'),
            ]);

            // Reverse ledger transactions
            $this->reverseLedgerTransactions();
        });
    }

    private function reverseLedgerTransactions()
    {
        // Find and reverse all related transactions
        $transactions = LedgerTransaction::where('reference', $this->invoice_number)
            ->where('company_profile_id', $this->company_profile_id)
            ->get();

        foreach ($transactions as $transaction) {
            // Create reverse transaction
            LedgerTransaction::create([
                'company_profile_id' => $transaction->company_profile_id,
                'ledger_id' => $transaction->ledger_id,
                'date' => now()->toDateString(),
                'type' => 'adjustment',
                'description' => "Reversal: {$transaction->description}",
                'debit_amount' => $transaction->credit_amount, // Reverse amounts
                'credit_amount' => $transaction->debit_amount,
                'reference' => $this->invoice_number . '-REV',
            ]);
        }
    }
}
