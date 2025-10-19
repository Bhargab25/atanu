<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\InvoicePayment;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\AccountLedger;
use App\Models\LedgerTransaction;
use App\Models\CompanyProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoicePaymentManagement extends Component
{
    use WithPagination, Toast;

    // Company management
    public $selectedCompanyId = null;
    public $companyOptions = [];

    // Modal states
    public $showPaymentModal = false;
    public $showViewModal = false;
    public $showReverseModal = false;
    public $editingPayment = null;
    public $viewingPayment = null;
    public $reversingPayment = null;

    // Payment form data
    public $selectedInvoiceId = null;
    public $invoiceOptions = [];
    public $paymentAmount = 0;
    public $paymentDate;
    public $paymentMethod = 'cash';
    public $paymentReference = '';
    public $paymentNotes = '';

    // Reverse payment form
    public $reverseReason = '';

    // Filters
    public $search = '';
    public $clientFilter = '';
    public $paymentMethodFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 15;

    // Statistics
    public $totalPayments = 0;
    public $totalAmount = 0;
    public $thisMonthAmount = 0;
    public $avgPaymentAmount = 0;

    public $paymentMethods = [
        ['value' => 'cash', 'label' => 'Cash'],
        ['value' => 'bank', 'label' => 'Bank Transfer'],
        ['value' => 'upi', 'label' => 'UPI'],
        ['value' => 'card', 'label' => 'Card'],
        ['value' => 'cheque', 'label' => 'Cheque'],
    ];

    protected $listeners = ['refreshPayments' => '$refresh'];

    public function mount()
    {
        $this->loadCompanies();
        $this->paymentDate = now()->format('Y-m-d');
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');

        // Set default company if only one exists
        if (count($this->companyOptions) === 1) {
            $this->selectedCompanyId = $this->companyOptions[0]['id'];
            $this->loadUnpaidInvoices();
            $this->calculateStats();
        }
    }

    private function loadCompanies()
    {
        $this->companyOptions = CompanyProfile::active()
            ->get()
            ->map(fn($company) => ['id' => $company->id, 'name' => $company->name])
            ->toArray();
    }

    public function updatedSelectedCompanyId()
    {
        $this->selectedInvoiceId = null;
        $this->invoiceOptions = [];
        $this->loadUnpaidInvoices();
        $this->calculateStats();
        $this->resetPage();
    }

    private function loadUnpaidInvoices()
    {
        if ($this->selectedCompanyId) {
            $this->invoiceOptions = Invoice::forCompany($this->selectedCompanyId)
                ->with('client')
                ->where('payment_status', '!=', 'paid')
                ->where('status', '!=', 'cancelled')
                ->orderBy('invoice_date', 'desc')
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'label' => $invoice->invoice_number . ' - ' . $invoice->client->name . ' (₹' . number_format($invoice->outstanding_amount, 2) . ')'
                    ];
                })
                ->toArray();
        }
    }

    public function updatedSelectedInvoiceId()
    {
        if ($this->selectedInvoiceId) {
            $invoice = Invoice::find($this->selectedInvoiceId);
            if ($invoice) {
                $this->paymentAmount = $invoice->outstanding_amount;
            }
        }
    }

    private function calculateStats()
    {
        if (!$this->selectedCompanyId) {
            $this->totalPayments = 0;
            $this->totalAmount = 0;
            $this->thisMonthAmount = 0;
            $this->avgPaymentAmount = 0;
            return;
        }

        $baseQuery = InvoicePayment::whereHas('invoice', function ($query) {
            $query->where('company_profile_id', $this->selectedCompanyId);
        });

        $this->totalPayments = $baseQuery->count();
        $this->totalAmount = $baseQuery->sum('amount');

        $this->thisMonthAmount = InvoicePayment::whereHas('invoice', function ($query) {
            $query->where('company_profile_id', $this->selectedCompanyId);
        })
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $this->avgPaymentAmount = $this->totalPayments > 0 ? $this->totalAmount / $this->totalPayments : 0;
    }

    public function openPaymentModal()
    {
        $this->resetPaymentForm();
        $this->loadUnpaidInvoices();
        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->editingPayment = null;
        $this->resetValidation();
        $this->resetPaymentForm();
    }

    private function resetPaymentForm()
    {
        $this->selectedInvoiceId = null;
        $this->paymentAmount = 0;
        $this->paymentDate = now()->format('Y-m-d');
        $this->paymentMethod = 'cash';
        $this->paymentReference = '';
        $this->paymentNotes = '';
    }

    public function recordPayment()
    {
        $this->validate([
            'selectedCompanyId' => 'required|exists:company_profiles,id',
            'selectedInvoiceId' => 'required|exists:invoices,id',
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date',
            'paymentMethod' => 'required|in:cash,bank,upi,card,cheque',
        ]);

        try {
            DB::transaction(function () {
                $invoice = Invoice::with(['client'])->find($this->selectedInvoiceId);

                if ($this->paymentAmount > $invoice->outstanding_amount) {
                    $this->addError('paymentAmount', 'Payment amount cannot exceed outstanding amount of ₹' . number_format($invoice->outstanding_amount, 2));
                    return;
                }

                // FIXED: Create payment record manually
                $payment = InvoicePayment::create([
                    'invoice_id' => $invoice->id,
                    'payment_reference' => InvoicePayment::generatePaymentReference(),
                    'amount' => $this->paymentAmount,
                    'payment_date' => $this->paymentDate,
                    'payment_method' => $this->paymentMethod,
                    'reference_number' => $this->paymentReference,
                    'notes' => $this->paymentNotes,
                    'created_by' => auth()->id(),
                ]);

                // Update invoice
                $invoice->increment('paid_amount', $this->paymentAmount);
                $this->updateInvoicePaymentStatus($invoice);

                // Create ledger entries
                $this->createPaymentLedgerEntries($payment);

                $this->success('Payment recorded successfully!', 'Invoice payment has been processed and ledger updated.');
            });

            $this->closePaymentModal();
            $this->calculateStats();
            $this->dispatch('refreshPayments');
        } catch (\Exception $e) {
            Log::error('Error recording payment: ' . $e->getMessage());
            $this->error('Error recording payment: ' . $e->getMessage());
        }
    }

    // Add the helper methods from InvoiceManagement to this component too
    private function createPaymentLedgerEntries(InvoicePayment $payment)
    {
        $invoice = $payment->invoice;

        // 1. DEBIT: Cash/Bank Account (Asset increases)
        if ($payment->payment_method === 'cash') {
            $paymentLedger = AccountLedger::getOrCreateCashLedger($invoice->company_profile_id);
        } else {
            $paymentLedger = AccountLedger::getOrCreateBankLedger($invoice->company_profile_id, $payment->payment_method);
        }

        LedgerTransaction::create([
            'company_profile_id' => $invoice->company_profile_id,
            'ledger_id' => $paymentLedger->id,
            'date' => $payment->payment_date,
            'type' => 'receipt', // Use existing enum value
            'description' => "Payment received from {$invoice->client->name} via {$payment->payment_method}",
            'debit_amount' => $payment->amount, // Cash/Bank increases
            'credit_amount' => 0,
            'reference' => $payment->payment_reference,
        ]);

        // 2. CREDIT: Client Account (Accounts Receivable) - Asset decreases
        $clientLedger = AccountLedger::getOrCreateClientLedger($invoice->company_profile_id, $invoice->client);

        LedgerTransaction::create([
            'company_profile_id' => $invoice->company_profile_id,
            'ledger_id' => $clientLedger->id,
            'date' => $payment->payment_date,
            'type' => 'receipt', // Use existing enum value
            'description' => "Payment received for Invoice {$invoice->invoice_number}",
            'debit_amount' => 0,
            'credit_amount' => $payment->amount, // Client owes us less
            'reference' => $payment->payment_reference,
        ]);
    }

    private function updateInvoicePaymentStatus(Invoice $invoice)
    {
        if ($invoice->paid_amount >= $invoice->total_amount) {
            $invoice->update([
                'payment_status' => 'paid',
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        } elseif ($invoice->paid_amount > 0) {
            $invoice->update(['payment_status' => 'partially_paid']);
        } else {
            $invoice->update(['payment_status' => 'unpaid']);
        }
    }


    public function viewPayment($paymentId)
    {
        $this->viewingPayment = InvoicePayment::with(['invoice.client', 'invoice.company', 'creator'])->find($paymentId);
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingPayment = null;
    }

    public function openReverseModal($paymentId)
    {
        $this->reversingPayment = InvoicePayment::with(['invoice.client'])->find($paymentId);
        $this->reverseReason = '';
        $this->showReverseModal = true;
    }

    public function closeReverseModal()
    {
        $this->showReverseModal = false;
        $this->reversingPayment = null;
        $this->reverseReason = '';
        $this->resetValidation();
    }

    public function reversePayment()
    {
        $this->validate([
            'reverseReason' => 'required|string|min:10|max:500',
        ], [
            'reverseReason.required' => 'Please provide a reason for reversing this payment',
            'reverseReason.min' => 'Reason must be at least 10 characters',
        ]);

        try {
            DB::transaction(function () {
                $payment = $this->reversingPayment;
                $invoice = $payment->invoice;

                // Reduce invoice paid amount
                $invoice->decrement('paid_amount', $payment->amount);
                $invoice->updatePaymentStatus();

                // Create reverse ledger transactions
                $this->createReverseLedgerTransactions($payment);

                // Update payment record (soft delete with reason)
                $payment->update([
                    'notes' => ($payment->notes ? $payment->notes . "\n\n" : '') .
                        "REVERSED: " . $this->reverseReason . " (Reversed by: " . auth()->user()->name . " on " . now()->format('d/m/Y H:i') . ")"
                ]);
                $payment->delete();

                $this->success('Payment reversed successfully!', 'All related ledger entries have been reversed.');
            });

            $this->closeReverseModal();
            $this->calculateStats();
            $this->dispatch('refreshPayments');
        } catch (\Exception $e) {
            Log::error('Error reversing payment: ' . $e->getMessage());
            $this->error('Error reversing payment: ' . $e->getMessage());
        }
    }

    private function createReverseLedgerTransactions($payment)
    {
        // Find original transactions and create reverse entries
        $originalTransactions = \App\Models\LedgerTransaction::where('reference', $payment->payment_reference)
            ->where('company_profile_id', $payment->invoice->company_profile_id)
            ->get();

        foreach ($originalTransactions as $transaction) {
            \App\Models\LedgerTransaction::create([
                'company_profile_id' => $transaction->company_profile_id,
                'ledger_id' => $transaction->ledger_id,
                'date' => now()->toDateString(),
                'type' => 'adjustment',
                'description' => "Payment Reversal: {$transaction->description}",
                'debit_amount' => $transaction->credit_amount, // Reverse amounts
                'credit_amount' => $transaction->debit_amount,
                'reference' => $payment->payment_reference . '-REV',
            ]);
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $payments = InvoicePayment::query()
            ->with(['invoice.client', 'invoice.company', 'creator'])
            ->when($this->selectedCompanyId, function ($query) {
                return $query->whereHas('invoice', function ($invoiceQuery) {
                    $invoiceQuery->where('company_profile_id', $this->selectedCompanyId);
                });
            })
            ->when($this->search, function ($query) {
                return $query->where(function ($subQuery) {
                    $subQuery->where('payment_reference', 'like', '%' . $this->search . '%')
                        ->orWhere('reference_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('invoice', function ($invoiceQuery) {
                            $invoiceQuery->where('invoice_number', 'like', '%' . $this->search . '%')
                                ->orWhereHas('client', function ($clientQuery) {
                                    $clientQuery->where('name', 'like', '%' . $this->search . '%')
                                        ->orWhere('client_id', 'like', '%' . $this->search . '%');
                                });
                        });
                });
            })
            ->when($this->clientFilter, function ($query) {
                return $query->whereHas('invoice.client', function ($clientQuery) {
                    $clientQuery->where('id', $this->clientFilter);
                });
            })
            ->when($this->paymentMethodFilter, function ($query) {
                return $query->where('payment_method', $this->paymentMethodFilter);
            })
            ->when($this->dateFrom && $this->dateTo, function ($query) {
                return $query->whereBetween('payment_date', [$this->dateFrom, $this->dateTo]);
            })
            ->orderBy('payment_date', 'desc')
            ->paginate($this->perPage);

        // Get clients for filter
        $clients = [];
        if ($this->selectedCompanyId) {
            $clients = Client::forCompany($this->selectedCompanyId)
                ->active()
                ->orderBy('name')
                ->get()
                ->map(fn($client) => ['id' => $client->id, 'name' => $client->name . ' (' . $client->client_id . ')'])
                ->toArray();
        }

        return view('livewire.invoice-payment-management', [
            'payments' => $payments,
            'clients' => $clients,
        ]);
    }
}
