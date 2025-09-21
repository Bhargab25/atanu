<?php
// app/Livewire/InvoiceManagement.php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Client;
use App\Models\StockMovement;
use App\Models\LedgerTransaction;
use App\Models\MonthlyBill;
use App\Services\InvoicePdfService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\InvoicePayment;

class InvoiceManagement extends Component
{
    use WithPagination, Toast;

    // Tab management
    public $activeTab = 'invoices';

    // Invoice form properties
    public $showInvoiceModal = false;
    public $editingInvoice = null;
    public $invoiceType = 'cash';
    public $invoiceDate;
    public $dueDate;
    public $clientId = '';
    public $clientName = '';
    public $clientPhone = '';
    public $clientAddress = '';
    public $isGstInvoice = false;
    public $clientGstin = '';
    public $placeOfSupply = '';
    public $gstType = 'cgst_sgst';
    public $notes = '';
    public $termsConditions = '';

    // Invoice items
    public $invoiceItems = [];

    // Monthly billing
    public $showMonthlyBillModal = false;
    public $selectedClient = null;
    public $monthlyBillPeriodFrom;
    public $monthlyBillPeriodTo;
    public $unbilledInvoices = [];
    public $selectedInvoicesForBilling = [];

    // Payment modal
    public $showPaymentModal = false;
    public $paymentInvoice = null;
    public $paymentAmount = '';
    public $paymentMethod = 'cash';
    public $paymentReference = '';
    public $paymentNotes = '';

    // Filters and search
    public $search = '';
    public $statusFilter = '';
    public $typeFilter = '';
    public $clientFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 15;

    // Statistics
    public $totalInvoices = 0;
    public $totalAmount = 0;
    public $paidAmount = 0;
    public $pendingAmount = 0;

    // Add these properties to the class
    public $showViewModal = false;
    public $viewingInvoice = null;

    // Also add method to view monthly bills
    // public $showMonthlyBillsModal = false;
    // public $monthlyBills = [];

    protected $rules = [
        'invoiceType' => 'required|in:cash,client',
        'invoiceDate' => 'required|date',
        'dueDate' => 'nullable|date|after_or_equal:invoiceDate',
        'clientId' => 'required_if:invoiceType,client|nullable|exists:clients,id',
        'clientName' => 'required_if:invoiceType,cash|nullable|string|max:255',
        'clientPhone' => 'nullable|string|max:20',
        'clientAddress' => 'nullable|string',
        'isGstInvoice' => 'boolean',
        'clientGstin' => 'nullable|string|size:15',
        'placeOfSupply' => 'nullable|string|max:100',
        'gstType' => 'nullable|in:cgst_sgst,igst',
        'invoiceItems.*.product_id' => 'required|exists:products,id',
        'invoiceItems.*.quantity' => 'required|numeric|min:0.01',
        'invoiceItems.*.unit_price' => 'required|numeric|min:0',
        'invoiceItems.*.invoice_unit' => 'nullable|string',
        'invoiceItems.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        'invoiceItems.*.cgst_rate' => 'nullable|numeric|min:0|max:50',
        'invoiceItems.*.sgst_rate' => 'nullable|numeric|min:0|max:50',
        'invoiceItems.*.igst_rate' => 'nullable|numeric|min:0|max:50',
    ];

    public function mount()
    {
        $this->invoiceDate = now()->format('Y-m-d');
        $this->dueDate = now()->addDays(30)->format('Y-m-d');
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->calculateStats();
        $this->resetInvoiceItems();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function openInvoiceModal($type = 'cash')
    {
        $this->invoiceType = $type;
        $this->showInvoiceModal = true;
        $this->resetInvoiceForm();
    }

    public function closeInvoiceModal()
    {
        $this->showInvoiceModal = false;
        $this->editingInvoice = null;
        $this->resetValidation();
        $this->resetInvoiceForm();
    }

    public function resetInvoiceForm()
    {
        $this->invoiceDate = now()->format('Y-m-d');
        $this->dueDate = $this->invoiceType === 'client' ? now()->addDays(30)->format('Y-m-d') : null;
        $this->clientId = '';
        $this->clientName = '';
        $this->clientPhone = '';
        $this->clientAddress = '';
        $this->isGstInvoice = false;
        $this->clientGstin = '';
        $this->placeOfSupply = '';
        $this->gstType = 'cgst_sgst';
        $this->notes = '';
        $this->termsConditions = 'Payment due within 30 days. Late payments subject to 1.5% monthly service charge.';
        $this->resetInvoiceItems();
    }

    public function resetInvoiceItems()
    {
        $this->invoiceItems = [
            [
                'product_id' => '',
                'quantity' => '',
                'unit_price' => '',
                'invoice_unit' => '',
                'discount_percentage' => 0,
                'cgst_rate' => 0,
                'sgst_rate' => 0,
                'igst_rate' => 0,
            ]
        ];
    }

    public function addInvoiceItem()
    {
        $this->invoiceItems[] = [
            'product_id' => '',
            'quantity' => '',
            'unit_price' => '',
            'invoice_unit' => '',
            'discount_percentage' => 0,
            'cgst_rate' => 0,
            'sgst_rate' => 0,
            'igst_rate' => 0,
        ];
    }

    public function removeInvoiceItem($index)
    {
        if (count($this->invoiceItems) > 1) {
            unset($this->invoiceItems[$index]);
            $this->invoiceItems = array_values($this->invoiceItems);
        }
    }

    public function updatedClientId()
    {
        if ($this->clientId) {
            $client = Client::find($this->clientId);
            if ($client) {
                $this->clientGstin = $client->gstin;
                $this->clientPhone = $client->phone;
                $this->clientAddress = $client->address . ', ' . $client->city . ', ' . $client->state;
            }
        }
    }

    public function updatedInvoiceItems($value, $name)
    {
        // Auto-populate product details when product is selected
        $pathParts = explode('.', $name);
        if (count($pathParts) === 3 && $pathParts[2] === 'product_id') {
            $index = $pathParts[1];
            $productId = $value;

            if ($productId) {
                $product = Product::find($productId);
                if ($product) {
                    $this->invoiceItems[$index]['unit_price'] = $product->unit_price ?? 0;
                    // Set default GST rates based on product category or settings
                    if ($this->isGstInvoice) {
                        $this->invoiceItems[$index]['cgst_rate'] = 9; // Default 9%
                        $this->invoiceItems[$index]['sgst_rate'] = 9; // Default 9%
                        $this->invoiceItems[$index]['igst_rate'] = 0;
                    }
                }
            }
        }
    }

    public function saveInvoice()
    {
        $this->validate();

        // Additional validation
        if (empty($this->invoiceItems) || !$this->invoiceItems[0]['product_id']) {
            $this->error('At least one product is required');
            return;
        }

        try {
            DB::transaction(function () {
                // Create invoice
                $invoice = Invoice::create([
                    'invoice_type' => $this->invoiceType,
                    'invoice_date' => $this->invoiceDate,
                    'due_date' => $this->dueDate,
                    'client_id' => $this->invoiceType === 'client' ? $this->clientId : null,
                    'client_name' => $this->invoiceType === 'cash' ? $this->clientName : null,
                    'client_phone' => $this->clientPhone,
                    'client_address' => $this->clientAddress,
                    'is_gst_invoice' => $this->isGstInvoice,
                    'client_gstin' => $this->clientGstin,
                    'place_of_supply' => $this->placeOfSupply,
                    'gst_type' => $this->gstType,
                    'notes' => $this->notes,
                    'terms_conditions' => $this->termsConditions,
                    'created_by' => auth()->user()->name,
                ]);

                $subtotal = 0;
                $totalTax = 0;

                // Create invoice items
                foreach ($this->invoiceItems as $itemData) {
                    if (empty($itemData['product_id']) || empty($itemData['quantity'])) continue;

                    $product = Product::find($itemData['product_id']);

                    // Check stock availability
                    if ($product->stock_quantity < $itemData['quantity']) {
                        throw new \Exception("Insufficient stock for product: {$product->name}");
                    }

                    // Calculate amounts
                    $unitPrice = $itemData['unit_price'];
                    $quantity = $itemData['quantity'];
                    $discountPercent = $itemData['discount_percentage'] ?? 0;

                    $lineTotal = $unitPrice * $quantity;
                    $discountAmount = ($lineTotal * $discountPercent) / 100;
                    $taxableAmount = $lineTotal - $discountAmount;

                    // Calculate GST
                    $cgstAmount = ($taxableAmount * ($itemData['cgst_rate'] ?? 0)) / 100;
                    $sgstAmount = ($taxableAmount * ($itemData['sgst_rate'] ?? 0)) / 100;
                    $igstAmount = ($taxableAmount * ($itemData['igst_rate'] ?? 0)) / 100;
                    $totalItemAmount = $taxableAmount + $cgstAmount + $sgstAmount + $igstAmount;

                    // Create invoice item
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_unit' => $product->unit,
                        'invoice_unit' => $itemData['invoice_unit'],
                        'unit_conversion_factor' => 1, // Can be extended for unit conversions
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'discount_percentage' => $discountPercent,
                        'discount_amount' => $discountAmount,
                        'taxable_amount' => $taxableAmount,
                        'cgst_rate' => $itemData['cgst_rate'] ?? 0,
                        'sgst_rate' => $itemData['sgst_rate'] ?? 0,
                        'igst_rate' => $itemData['igst_rate'] ?? 0,
                        'cgst_amount' => $cgstAmount,
                        'sgst_amount' => $sgstAmount,
                        'igst_amount' => $igstAmount,
                        'total_amount' => $totalItemAmount,
                    ]);

                    // Update product stock
                    $product->stock_quantity -= $quantity;
                    $product->save();

                    // Create stock movement
                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'out',
                        'quantity' => $quantity,
                        'reason' => 'sale',
                        'reference_type' => Invoice::class,
                        'reference_id' => $invoice->id,
                    ]);

                    $subtotal += $taxableAmount;
                    $totalTax += ($cgstAmount + $sgstAmount + $igstAmount);
                }

                // Update invoice totals
                $invoice->update([
                    'subtotal' => $subtotal,
                    'cgst_amount' => $invoice->items->sum('cgst_amount'),
                    'sgst_amount' => $invoice->items->sum('sgst_amount'),
                    'igst_amount' => $invoice->items->sum('igst_amount'),
                    'total_tax' => $totalTax,
                    'total_amount' => $subtotal + $totalTax,
                    'balance_amount' => $subtotal + $totalTax,
                ]);

                // Create ledger entries for client invoices
                if ($this->invoiceType === 'client' && $this->clientId) {
                    $client = Client::find($this->clientId);
                    if ($client->ledger) {
                        LedgerTransaction::create([
                            'ledger_id' => $client->ledger->id,
                            'date' => $this->invoiceDate,
                            'type' => 'sale',
                            'description' => "Sales invoice - {$invoice->invoice_number}",
                            'debit_amount' => $invoice->total_amount,
                            'credit_amount' => 0,
                            'reference' => $invoice->invoice_number,
                        ]);
                    }
                }

                $this->success('Invoice created successfully!');
                $this->closeInvoiceModal();
                $this->calculateStats();

                // Generate PDF for client invoices
                if ($this->invoiceType === 'client') {
                    $this->dispatch('invoice-created', ['invoiceId' => $invoice->id]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Error creating invoice: ' . $e->getMessage());
            $this->error('Error creating invoice: ' . $e->getMessage());
        }
    }

    public function downloadInvoicePdf($invoiceId)
    {
        try {
            $invoice = Invoice::with(['client', 'items.product'])->findOrFail($invoiceId);

            $pdfService = new InvoicePdfService();
            $pdfPath = $pdfService->generateInvoicePdf($invoice);

            return response()->download($pdfPath)->deleteFileAfterSend();
        } catch (\Exception $e) {
            Log::error('Error generating PDF: ' . $e->getMessage());
            $this->error('Error generating PDF');
        }
    }

    public function downloadMonthlyBillPdf($billId)
    {
        try {
            $monthlyBill = MonthlyBill::with(['client', 'invoices'])->findOrFail($billId);

            $pdfService = new InvoicePdfService();
            $pdfPath = $pdfService->generateMonthlyBillPdf($monthlyBill);

            return response()->download($pdfPath)->deleteFileAfterSend();
        } catch (\Exception $e) {
            Log::error('Error generating Monthly Bill PDF: ' . $e->getMessage());
            $this->error('Error generating Monthly Bill PDF');
        }
    }

    public function openMonthlyBillModal($clientId)
    {
        $this->selectedClient = Client::find($clientId);
        $this->showMonthlyBillModal = true;
        $this->monthlyBillPeriodFrom = now()->startOfMonth()->format('Y-m-d');
        $this->monthlyBillPeriodTo = now()->endOfMonth()->format('Y-m-d');
        $this->loadUnbilledInvoices();
    }

    public function closeMonthlyBillModal()
    {
        $this->showMonthlyBillModal = false;
        $this->selectedClient = null;
        $this->unbilledInvoices = [];
        $this->selectedInvoicesForBilling = [];
    }

    public function loadUnbilledInvoices()
    {
        if (!$this->selectedClient) return;

        $this->unbilledInvoices = Invoice::where('client_id', $this->selectedClient->id)
            ->where('is_monthly_billed', false)
            ->whereBetween('invoice_date', [$this->monthlyBillPeriodFrom, $this->monthlyBillPeriodTo])
            ->get();
    }

    public function generateMonthlyBill()
    {
        if (empty($this->selectedInvoicesForBilling)) {
            $this->error('Please select at least one invoice');
            return;
        }

        try {
            DB::transaction(function () {
                $invoices = Invoice::whereIn('id', $this->selectedInvoicesForBilling)->get();

                $monthlyBill = MonthlyBill::create([
                    'client_id' => $this->selectedClient->id,
                    'bill_date' => now(),
                    'period_from' => $this->monthlyBillPeriodFrom,
                    'period_to' => $this->monthlyBillPeriodTo,
                    'total_amount' => $invoices->sum('total_amount'),
                    'invoice_count' => $invoices->count(),
                ]);

                // Update invoices
                Invoice::whereIn('id', $this->selectedInvoicesForBilling)
                    ->update([
                        'is_monthly_billed' => true,
                        'monthly_bill_id' => $monthlyBill->id,
                    ]);

                $this->success('Monthly bill generated successfully!');
                $this->closeMonthlyBillModal();

                // Generate PDF
                $this->dispatch('monthly-bill-generated', ['billId' => $monthlyBill->id]);
            });
        } catch (\Exception $e) {
            Log::error('Error generating monthly bill: ' . $e->getMessage());
            $this->error('Error generating monthly bill');
        }
    }

    // public function openMonthlyBillsModal()
    // {
    //     $this->showMonthlyBillsModal = true;
    //     $this->loadMonthlyBills();
    // }

    // public function closeMonthlyBillsModal()
    // {
    //     $this->showMonthlyBillsModal = false;
    //     $this->monthlyBills = [];
    // }

    // private function loadMonthlyBills()
    // {
    //     $this->monthlyBills = MonthlyBill::with(['client'])
    //         ->when($this->search, function ($q) {
    //             return $q->where('bill_number', 'like', '%' . $this->search . '%')
    //                 ->orWhereHas('client', function ($query) {
    //                     $query->where('name', 'like', '%' . $this->search . '%');
    //                 });
    //         })
    //         ->when($this->dateFrom, function ($q) {
    //             return $q->where('bill_date', '>=', $this->dateFrom);
    //         })
    //         ->when($this->dateTo, function ($q) {
    //             return $q->where('bill_date', '<=', $this->dateTo);
    //         })
    //         ->orderBy('bill_date', 'desc')
    //         ->paginate(10);
    // }

    public function calculateStats()
    {
        $invoices = Invoice::query();

        if ($this->dateFrom) {
            $invoices->where('invoice_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $invoices->where('invoice_date', '<=', $this->dateTo);
        }

        $stats = $invoices->selectRaw('
            COUNT(*) as total_invoices,
            SUM(total_amount) as total_amount,
            SUM(paid_amount) as paid_amount,
            SUM(balance_amount) as pending_amount
        ')->first();

        $this->totalInvoices = $stats->total_invoices ?? 0;
        $this->totalAmount = $stats->total_amount ?? 0;
        $this->paidAmount = $stats->paid_amount ?? 0;
        $this->pendingAmount = $stats->pending_amount ?? 0;
    }

    public function viewInvoice($invoiceId)
    {
        $this->viewingInvoice = Invoice::with(['client', 'items.product', 'payments'])->find($invoiceId);
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingInvoice = null;
    }

    public function openPaymentModal($invoiceId)
    {
        $this->paymentInvoice = Invoice::find($invoiceId);
        $this->showPaymentModal = true;
        $this->paymentAmount = '';
        $this->paymentMethod = 'cash';
        $this->paymentReference = '';
        $this->paymentNotes = '';
        $this->resetValidation();
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->paymentInvoice = null;
        $this->paymentAmount = '';
        $this->paymentMethod = 'cash';
        $this->paymentReference = '';
        $this->paymentNotes = '';
        $this->resetValidation();
    }

    public function savePayment()
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01|max:' . $this->paymentInvoice->balance_amount,
            'paymentMethod' => 'required|in:cash,bank,upi,card,cheque',
            'paymentReference' => 'nullable|string|max:255',
            'paymentNotes' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () {
                // Create payment record
                $payment = InvoicePayment::create([
                    'invoice_id' => $this->paymentInvoice->id,
                    'payment_date' => now(),
                    'amount' => $this->paymentAmount,
                    'payment_method' => $this->paymentMethod,
                    'reference_number' => $this->paymentReference,
                    'notes' => $this->paymentNotes,
                ]);

                // Update invoice payment status
                $this->paymentInvoice->paid_amount += $this->paymentAmount;
                $this->paymentInvoice->balance_amount -= $this->paymentAmount;

                // Update payment status
                if ($this->paymentInvoice->balance_amount <= 0) {
                    $this->paymentInvoice->payment_status = 'paid';
                } elseif ($this->paymentInvoice->paid_amount > 0) {
                    $this->paymentInvoice->payment_status = 'partial';
                }

                $this->paymentInvoice->save();

                // Create ledger entry if client invoice
                if ($this->paymentInvoice->client_id && $this->paymentInvoice->client->ledger) {
                    LedgerTransaction::create([
                        'ledger_id' => $this->paymentInvoice->client->ledger->id,
                        'date' => now(),
                        'type' => 'payment',
                        'description' => "Payment received for invoice {$this->paymentInvoice->invoice_number}",
                        'debit_amount' => 0,
                        'credit_amount' => $this->paymentAmount,
                        'reference' => $this->paymentReference ?: "PAY-{$payment->id}",
                    ]);
                }
            });

            $this->success('Payment recorded successfully!');
            $this->closePaymentModal();
            $this->calculateStats();
        } catch (\Exception $e) {
            Log::error('Error recording payment: ' . $e->getMessage());
            $this->error('Error recording payment: ' . $e->getMessage());
        }
    }

    public function deleteInvoice($invoiceId)
    {
        try {
            $invoice = Invoice::find($invoiceId);

            if ($invoice->paid_amount > 0) {
                $this->error('Cannot delete invoice with payments. Please refund payments first.');
                return;
            }

            DB::transaction(function () use ($invoice) {
                // Mark as cancelled
                $invoice->update([
                    'is_cancelled' => true,
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Invoice deleted by user',
                ]);

                // The boot method will handle stock restoration and ledger adjustments
                $invoice->delete();
            });

            $this->success('Invoice deleted and stock restored successfully!');
            $this->calculateStats();
        } catch (\Exception $e) {
            Log::error('Error deleting invoice: ' . $e->getMessage());
            $this->error('Error deleting invoice: ' . $e->getMessage());
        }
    }

    public function duplicateInvoice($invoiceId)
    {
        try {
            $originalInvoice = Invoice::with('items')->find($invoiceId);

            $this->invoiceType = $originalInvoice->invoice_type;
            $this->clientId = $originalInvoice->client_id;
            $this->clientName = $originalInvoice->client_name;
            $this->clientPhone = $originalInvoice->client_phone;
            $this->clientAddress = $originalInvoice->client_address;
            $this->isGstInvoice = $originalInvoice->is_gst_invoice;
            $this->clientGstin = $originalInvoice->client_gstin;
            $this->placeOfSupply = $originalInvoice->place_of_supply;
            $this->gstType = $originalInvoice->gst_type;
            $this->notes = $originalInvoice->notes;
            $this->termsConditions = $originalInvoice->terms_conditions;

            // Copy items
            $this->invoiceItems = [];
            foreach ($originalInvoice->items as $item) {
                $this->invoiceItems[] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'invoice_unit' => $item->invoice_unit,
                    'discount_percentage' => $item->discount_percentage,
                    'cgst_rate' => $item->cgst_rate,
                    'sgst_rate' => $item->sgst_rate,
                    'igst_rate' => $item->igst_rate,
                ];
            }

            $this->showInvoiceModal = true;
            $this->success('Invoice duplicated. You can now modify and save.');
        } catch (\Exception $e) {
            Log::error('Error duplicating invoice: ' . $e->getMessage());
            $this->error('Error duplicating invoice');
        }
    }

    public function render()
    {
        if ($this->activeTab === 'monthly_bills') {
            $monthlyBills = MonthlyBill::with(['client'])
                ->when($this->search, function ($q) {
                    return $q->where('bill_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('client', function ($query) {
                            $query->where('name', 'like', '%' . $this->search . '%');
                        });
                })
                ->when($this->dateFrom, function ($q) {
                    return $q->where('bill_date', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    return $q->where('bill_date', '<=', $this->dateTo);
                })
                ->orderBy('bill_date', 'desc')
                ->paginate($this->perPage);

            return view('livewire.invoice-management', [
                'invoices' => collect(), // Empty collection for invoices tab
                'monthlyBills' => $monthlyBills, // Properly paginated
                'clients' => Client::where('is_active', true)->get(),
                'products' => Product::where('is_active', true)->get(),
            ]);
        } else {
            // Original invoice query
            $invoices = Invoice::with(['client', 'items'])
                ->when($this->search, function ($q) {
                    return $q->where(function ($query) {
                        $query->where('invoice_number', 'like', '%' . $this->search . '%')
                            ->orWhere('client_name', 'like', '%' . $this->search . '%')
                            ->orWhereHas('client', function ($q) {
                                $q->where('name', 'like', '%' . $this->search . '%');
                            });
                    });
                })
                ->when($this->statusFilter, function ($q) {
                    return $q->where('payment_status', $this->statusFilter);
                })
                ->when($this->typeFilter, function ($q) {
                    return $q->where('invoice_type', $this->typeFilter);
                })
                ->when($this->clientFilter, function ($q) {
                    return $q->where('client_id', $this->clientFilter);
                })
                ->when($this->dateFrom, function ($q) {
                    return $q->where('invoice_date', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    return $q->where('invoice_date', '<=', $this->dateTo);
                })
                ->orderBy('invoice_date', 'desc')
                ->paginate($this->perPage);

            return view('livewire.invoice-management', [
                'invoices' => $invoices,
                'monthlyBills' => collect(), // Empty collection for monthly bills tab
                'clients' => Client::where('is_active', true)->get(),
                'products' => Product::where('is_active', true)->get(),
            ]);
        }
    }
}
