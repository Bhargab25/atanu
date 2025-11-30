<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Client;
use App\Models\CompanyProfile;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\AccountLedger;
use App\Models\LedgerTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\InvoicePdfService;
use Illuminate\Support\Facades\Storage;

class InvoiceManagement extends Component
{
    use WithPagination, Toast;

    // Company and client management
    public $selectedCompanyId = null;
    public $companyOptions = [];
    public $selectedClientId = null;
    public $clientOptions = [];

    // Modal states
    public $showInvoiceModal = false;
    public $showPaymentModal = false;
    public $showViewModal = false;
    public $editingInvoice = null;
    public $viewingInvoice = null;
    public $paymentInvoice = null;

    // Invoice form data
    public $invoiceDate;
    public $dueDate;
    public $notes = '';
    public $invoiceItems = [];
    public $subtotal = 0;
    public $taxAmount = 0;
    public $discountAmount = 0;
    public $totalAmount = 0;

    // Payment form data
    public $paymentAmount = 0;
    public $paymentDate;
    public $paymentMethod = 'cash';
    public $paymentReference = '';
    public $paymentNotes = '';

    // Filters
    public $search = '';
    public $statusFilter = '';
    public $paymentStatusFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 15;

    public $paymentMethods = [
        ['value' => 'cash', 'label' => 'Cash'],
        ['value' => 'bank', 'label' => 'Bank Transfer'],
        ['value' => 'upi', 'label' => 'UPI'],
        ['value' => 'card', 'label' => 'Card'],
        ['value' => 'cheque', 'label' => 'Cheque'],
    ];

    protected $listeners = ['refreshInvoices' => '$refresh'];

    public function mount()
    {
        $this->loadCompanies();
        $this->invoiceDate = now()->format('Y-m-d');
        $this->paymentDate = now()->format('Y-m-d');
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');

        // Set default company if only one exists
        if (count($this->companyOptions) === 1) {
            $this->selectedCompanyId = $this->companyOptions[0]['id'];
            $this->loadClients();
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
        $this->selectedClientId = null;
        $this->clientOptions = [];
        $this->invoiceItems = [];
        $this->loadClients();
        $this->resetPage();
    }

    private function loadClients()
    {
        if ($this->selectedCompanyId) {
            $this->clientOptions = Client::forCompany($this->selectedCompanyId)
                ->active()
                ->get()
                ->map(fn($client) => ['id' => $client->id, 'name' => $client->name . ' (' . $client->client_id . ')'])
                ->toArray();
        }
    }

    public function updatedSelectedClientId()
    {
        $this->loadClientProducts();
    }

    private function loadClientProducts()
    {
        if ($this->selectedClientId) {
            $client = Client::find($this->selectedClientId);
            if ($client && $client->services_items) {
                $this->invoiceItems = [];

                foreach ($client->services_items as $serviceId => $serviceData) {
                    $service = ProductCategory::find($serviceId);
                    if ($service && isset($serviceData['items'])) {
                        foreach ($serviceData['items'] as $item) {
                            $latestItem = Product::find($item['item_id']);
                            $this->invoiceItems[] = [
                                'service_id' => $serviceId,
                                'service_name' => $service->name,
                                'item_id' => $item['item_id'],
                                'item_name' => $latestItem ? $latestItem->name : ($item['name'] ?? 'Unnamed Item'),
                                'description' => $latestItem ? $latestItem->description : ($item['description'] ?? ''),
                                'quantity' => 1,
                                'unit_price' => 0.00,
                                'total' => 0.00,
                            ];
                        }
                    }
                }
            }
        }
    }

    // FIX: Add these watchers for automatic calculation
    public function updatedInvoiceItems($value, $name)
    {
        // $name = "invoiceItems.0.quantity" or "invoiceItems.1.unit_price"
        $parts = explode('.', $name);

        if (count($parts) >= 3) {
            $index = $parts[1];

            if (isset($this->invoiceItems[$index])) {
                $qty   = (float) ($this->invoiceItems[$index]['quantity'] ?? 0);
                $price = (float) ($this->invoiceItems[$index]['unit_price'] ?? 0);

                $this->invoiceItems[$index]['total'] = $qty * $price;
            }
        }

        $this->calculateTotals();
    }

    public function updatedTaxAmount($value)
    {
        $this->taxAmount = (float) $value;
        $this->calculateTotals();
    }

    public function updatedDiscountAmount($value)
    {
        $this->discountAmount = (float) $value;
        $this->calculateTotals();
    }

    // FIX: Update these methods to be called from the blade template
    public function updateItemQuantity($index, $quantity)
    {
        if (isset($this->invoiceItems[$index])) {
            $this->invoiceItems[$index]['quantity'] = max(0, floatval($quantity));
            $this->invoiceItems[$index]['total'] = $this->invoiceItems[$index]['quantity'] * $this->invoiceItems[$index]['unit_price'];
            $this->calculateTotals();
        }
    }

    public function updateItemPrice($index, $price)
    {
        if (isset($this->invoiceItems[$index])) {
            $this->invoiceItems[$index]['unit_price'] = max(0, floatval($price));
            $this->invoiceItems[$index]['total'] = $this->invoiceItems[$index]['quantity'] * $this->invoiceItems[$index]['unit_price'];
            $this->calculateTotals();
        }
    }

    public function removeItem($index)
    {
        if (isset($this->invoiceItems[$index])) {
            unset($this->invoiceItems[$index]);
            $this->invoiceItems = array_values($this->invoiceItems); // Re-index
            $this->calculateTotals();
        }
    }

    private function calculateTotals()
    {
        $this->subtotal = collect($this->invoiceItems)->sum(function ($item) {
            return (float) ($item['total'] ?? 0);
        });

        $tax      = (float) $this->taxAmount;
        $discount = (float) $this->discountAmount;

        $this->totalAmount = $this->subtotal + $tax - $discount;
    }

    public function openInvoiceModal()
    {
        $this->resetInvoiceForm();
        $this->showInvoiceModal = true;
    }

    public function closeInvoiceModal()
    {
        $this->showInvoiceModal = false;
        $this->editingInvoice = null;
        $this->resetValidation();
        $this->resetInvoiceForm();
    }

    private function resetInvoiceForm()
    {
        $this->selectedClientId = null;
        $this->invoiceDate = now()->format('Y-m-d');
        $this->dueDate = '';
        $this->notes = '';
        $this->invoiceItems = [];
        $this->subtotal = 0;
        $this->taxAmount = 0;
        $this->discountAmount = 0;
        $this->totalAmount = 0;
        $this->clientOptions = [];

        if ($this->selectedCompanyId) {
            $this->loadClients();
        }
    }

    public function saveInvoice()
    {
        $this->validate([
            'selectedCompanyId' => 'required|exists:company_profiles,id',
            'selectedClientId' => 'required|exists:clients,id',
            'invoiceDate' => 'required|date',
            'invoiceItems' => 'required|array|min:1',
            'invoiceItems.*.quantity' => 'required|numeric|min:0.01',
            'invoiceItems.*.unit_price' => 'required|numeric|min:0',
        ], [
            'selectedCompanyId.required' => 'Please select a company',
            'selectedClientId.required' => 'Please select a client',
            'invoiceItems.required' => 'Please add at least one item',
            'invoiceItems.*.quantity.required' => 'Quantity is required',
            'invoiceItems.*.unit_price.required' => 'Unit price is required',
        ]);

        try {
            DB::transaction(function () {
                $data = [
                    'company_profile_id' => $this->selectedCompanyId,
                    'client_id' => $this->selectedClientId,
                    'invoice_date' => $this->invoiceDate,
                    'due_date' => $this->dueDate ?: now()->addDays(30)->format('Y-m-d'),
                    'invoice_items' => $this->invoiceItems,
                    'subtotal' => $this->subtotal,
                    'tax_amount' => $this->taxAmount,
                    'discount_amount' => $this->discountAmount,
                    'total_amount' => $this->totalAmount,
                    'notes' => $this->notes,
                    'created_by' => auth()->id(),
                ];

                if ($this->editingInvoice) {
                    $this->editingInvoice->update($data);
                    $this->success('Invoice updated successfully!');
                } else {
                    $invoice = Invoice::create($data);

                    // FIXED: Create ledger entries when invoice is created (draft state)
                    // Only create accounting entries when invoice is sent/finalized
                    $this->success('Invoice created successfully! Mark as "Sent" to create accounting entries.');
                }

                $this->closeInvoiceModal();
                $this->dispatch('refreshInvoices');
            });
        } catch (\Exception $e) {
            Log::error('Error saving invoice: ' . $e->getMessage());
            $this->error('Error saving invoice: ' . $e->getMessage());
        }
    }

    // Rest of your existing methods remain the same...
    public function editInvoice($invoiceId)
    {
        $this->editingInvoice = Invoice::with(['client', 'company'])->find($invoiceId);

        if ($this->editingInvoice) {
            $this->selectedCompanyId = $this->editingInvoice->company_profile_id;
            $this->selectedClientId = $this->editingInvoice->client_id;
            $this->invoiceDate = $this->editingInvoice->invoice_date->format('Y-m-d');
            $this->dueDate = $this->editingInvoice->due_date?->format('Y-m-d');
            $this->notes = $this->editingInvoice->notes;
            $this->invoiceItems = $this->editingInvoice->invoice_items;
            $this->subtotal = $this->editingInvoice->subtotal;
            $this->taxAmount = $this->editingInvoice->tax_amount;
            $this->discountAmount = $this->editingInvoice->discount_amount;
            $this->totalAmount = $this->editingInvoice->total_amount;

            $this->loadClients();
            $this->showInvoiceModal = true;
        }
    }

    public function viewInvoice($invoiceId)
    {
        $this->viewingInvoice = Invoice::with(['client', 'company', 'payments.creator'])->find($invoiceId);
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingInvoice = null;
    }

    public function markAsSent($invoiceId)
    {
        try {
            DB::transaction(function () use ($invoiceId) {
                $invoice = Invoice::with(['client'])->find($invoiceId);
                if ($invoice) {
                    // Update invoice status
                    $invoice->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);

                    // FIXED: Create proper accounting ledger entries
                    $this->createInvoiceLedgerEntries($invoice);

                    $this->success('Invoice marked as sent!', 'Accounting entries have been created.');
                    $this->dispatch('refreshInvoices');
                }
            });
        } catch (\Exception $e) {
            Log::error('Error marking invoice as sent: ' . $e->getMessage());
            $this->error('Error marking invoice as sent: ' . $e->getMessage());
        }
    }

    public function downloadInvoice($invoiceId)
    {
        try {
            $invoice = Invoice::with(['client', 'company'])->find($invoiceId);
            if ($invoice) {
                $pdfService = new InvoicePdfService();
                $pdfPath = $pdfService->generateInvoicePdf($invoice);

                // Update invoice with PDF path
                $invoice->update(['pdf_path' => $pdfPath]);

                return response()->download(
                    storage_path('app/public/' . $pdfPath),
                    "invoice-{$invoice->invoice_number}.pdf"
                );
            }
        } catch (\Exception $e) {
            Log::error('Error generating invoice PDF: ' . $e->getMessage());
            $this->error('Error generating PDF');
        }
    }

    public function openPaymentModal($invoiceId)
    {
        $this->paymentInvoice = Invoice::find($invoiceId);
        if ($this->paymentInvoice) {
            $this->paymentAmount = $this->paymentInvoice->outstanding_amount;
            $this->resetPaymentForm();
            $this->showPaymentModal = true;
        }
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->paymentInvoice = null;
        $this->resetValidation();
        $this->resetPaymentForm();
    }

    private function resetPaymentForm()
    {
        $this->paymentDate = now()->format('Y-m-d');
        $this->paymentMethod = 'cash';
        $this->paymentReference = '';
        $this->paymentNotes = '';
    }


    private function createInvoiceLedgerEntries(Invoice $invoice)
    {
        // 1. DEBIT: Client Account (Accounts Receivable) - Asset increases
        $clientLedger = AccountLedger::getOrCreateClientLedger($invoice->company_profile_id, $invoice->client);

        LedgerTransaction::create([
            'company_profile_id' => $invoice->company_profile_id,
            'ledger_id' => $clientLedger->id,
            'date' => $invoice->invoice_date,
            'type' => 'sale', // Use existing enum value
            'description' => "Invoice {$invoice->invoice_number} - {$invoice->client->name}",
            'debit_amount' => $invoice->total_amount, // Client owes us money
            'credit_amount' => 0,
            'reference' => $invoice->invoice_number,
        ]);

        // 2. CREDIT: Sales Revenue Account (Income) - Income increases
        $incomeLedger = AccountLedger::getOrCreateIncomeLedger($invoice->company_profile_id);

        LedgerTransaction::create([
            'company_profile_id' => $invoice->company_profile_id,
            'ledger_id' => $incomeLedger->id,
            'date' => $invoice->invoice_date,
            'type' => 'sale', // Use existing enum value
            'description' => "Sale to {$invoice->client->name} - Invoice {$invoice->invoice_number}",
            'debit_amount' => 0,
            'credit_amount' => $invoice->total_amount, // Income increases
            'reference' => $invoice->invoice_number,
        ]);

        Log::info('Invoice ledger entries created', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $invoice->total_amount
        ]);
    }

    public function processPayment()
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01|max:' . $this->paymentInvoice->outstanding_amount,
            'paymentDate' => 'required|date',
            'paymentMethod' => 'required|in:cash,bank,upi,card,cheque',
        ], [
            'paymentAmount.max' => 'Payment amount cannot exceed the outstanding amount of ₹' . number_format($this->paymentInvoice->outstanding_amount, 2),
        ]);

        try {
            DB::transaction(function () {
                // FIXED: Create payment record and ledger entries manually
                $payment = InvoicePayment::create([
                    'invoice_id' => $this->paymentInvoice->id,
                    'payment_reference' => InvoicePayment::generatePaymentReference(),
                    'amount' => $this->paymentAmount,
                    'payment_date' => $this->paymentDate,
                    'payment_method' => $this->paymentMethod,
                    'reference_number' => $this->paymentReference,
                    'notes' => $this->paymentNotes,
                    'created_by' => auth()->id(),
                ]);

                // Update invoice paid amount
                $this->paymentInvoice->increment('paid_amount', $this->paymentAmount);
                $this->updateInvoicePaymentStatus($this->paymentInvoice);

                // Create ledger entries
                $this->createPaymentLedgerEntries($payment);

                $this->success('Payment recorded successfully!', 'Ledger entries have been updated.');
            });

            $this->closePaymentModal();
            $this->dispatch('refreshInvoices');
        } catch (\Exception $e) {
            Log::error('Error processing payment: ' . $e->getMessage());
            $this->error('Error processing payment: ' . $e->getMessage());
        }
    }

    // NEW: Create payment ledger entries
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

        Log::info('Payment ledger entries created', [
            'payment_id' => $payment->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $payment->amount
        ]);
    }

    // NEW: Update invoice payment status
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

    public function deleteInvoice($invoiceId)
    {
        try {
            $invoice = Invoice::find($invoiceId);
            if ($invoice) {
                // Delete PDF file if exists
                if ($invoice->pdf_path) {
                    Storage::disk('public')->delete($invoice->pdf_path);
                }

                $invoice->delete();
                $this->success('Invoice deleted successfully!');
                $this->dispatch('refreshInvoices');
            }
        } catch (\Exception $e) {
            Log::error('Error deleting invoice: ' . $e->getMessage());
            $this->error('Error deleting invoice');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $invoices = Invoice::query()
            ->with(['client', 'company', 'creator'])
            ->when($this->selectedCompanyId, function ($query) {
                return $query->where('company_profile_id', $this->selectedCompanyId);
            })
            ->when($this->search, function ($query) {
                return $query->where(function ($subQuery) {
                    $subQuery->where('invoice_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('client', function ($clientQuery) {
                            $clientQuery->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('client_id', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter, function ($query) {
                return $query->where('status', $this->statusFilter);
            })
            ->when($this->paymentStatusFilter, function ($query) {
                return $query->where('payment_status', $this->paymentStatusFilter);
            })
            ->when($this->dateFrom && $this->dateTo, function ($query) {
                return $query->forDateRange($this->dateFrom, $this->dateTo);
            })
            ->orderBy('invoice_date', 'desc')
            ->paginate($this->perPage);

        return view('livewire.invoice-management', [
            'invoices' => $invoices,
        ]);
    }
}
