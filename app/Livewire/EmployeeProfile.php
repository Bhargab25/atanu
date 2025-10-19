<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Models\AccountLedger;
use App\Models\LedgerTransaction;
use Mary\Traits\Toast;
use Livewire\WithPagination;
use App\Services\PaymentReceiptService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EmployeeProfile extends Component
{
    use Toast, WithPagination;

    public Employee $employee;
    public $showPaymentModal = false;
    public $employeeLedger = null;

    // Payment form
    public $amount;
    public $payment_date;
    public $payment_method = 'cash';
    public $reference_number;
    public $month_year;
    public $payment_notes;
    public $showWhatsAppModal = false;
    public $selectedPaymentForWhatsApp = null;

    // Add payment type property
    public $payment_type = 'salary';

    // Add payment type options
    public $paymentTypes = [
        ['value' => 'salary', 'label' => 'Regular Salary'],
        ['value' => 'bonus', 'label' => 'Bonus'],
        ['value' => 'advance', 'label' => 'Advance'],
        ['value' => 'overtime', 'label' => 'Overtime'],
        ['value' => 'allowance', 'label' => 'Allowance'],
        ['value' => 'adjustment', 'label' => 'Adjustment'],
    ];

    public $paymentMethods = [
        ['value' => 'cash', 'label' => 'Cash'],
        ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
        ['value' => 'check', 'label' => 'Check'],
    ];

    protected $listeners = ['refreshProfile' => '$refresh'];

    public function mount($employeeId)
    {
        $this->employee = Employee::with('company')->findOrFail($employeeId);
        $this->payment_date = now()->format('Y-m-d');
        $this->month_year = now()->format('Y-m');
        $this->amount = $this->employee->salary_amount;

        // Load or create employee ledger
        $this->loadEmployeeLedger();
    }

    private function loadEmployeeLedger()
    {
        // Check if employee ledger exists
        $this->employeeLedger = AccountLedger::forCompany($this->employee->company_profile_id)
            ->where('ledgerable_type', Employee::class)
            ->where('ledgerable_id', $this->employee->id)
            ->first();

        // Create ledger if it doesn't exist
        if (!$this->employeeLedger) {
            $this->employeeLedger = AccountLedger::createForEmployee(
                $this->employee->company_profile_id,
                $this->employee
            );
        }
    }

    public function openPaymentModal()
    {
        $this->resetPaymentForm();
        $this->showPaymentModal = true;
    }

    public function processPayment()
    {
        $this->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,check',
            'payment_type' => 'required|in:salary,bonus,advance,overtime,allowance,adjustment',
            'month_year' => 'required|date_format:Y-m',
            'reference_number' => 'nullable|string|max:100',
            'payment_notes' => 'nullable|string|max:500',
        ]);

        // Check if payment already exists for this month
        if ($this->payment_type === 'salary' && $this->employee->hasPaymentForMonth($this->month_year)) {
            $this->error('Salary Already Paid!', 'Regular salary for this month already exists. Use a different payment type for additional payments.');
            return;
        }

        try {
            DB::transaction(function () {
                // Create employee payment record
                $payment = EmployeePayment::create([
                    'employee_id' => $this->employee->id,
                    'payment_id' => EmployeePayment::generatePaymentId(),
                    'amount' => $this->amount,
                    'payment_date' => $this->payment_date,
                    'payment_method' => $this->payment_method,
                    'payment_type' => $this->payment_type,
                    'reference_number' => $this->reference_number,
                    'notes' => $this->payment_notes,
                    'month_year' => $this->month_year,
                    'status' => 'paid',
                    'created_by' => auth()->id(),
                ]);

                // Create proper accounting entries for profit/loss tracking

                // 1. DEBIT: Salary/Employee Expense Account (Business Expense)
                $expenseLedger = $this->getOrCreateSalaryExpenseLedger();
                LedgerTransaction::create([
                    'company_profile_id' => $this->employee->company_profile_id,
                    'ledger_id' => $expenseLedger->id,
                    'date' => $this->payment_date,
                    'type' => 'expense',
                    'description' => ucfirst($this->payment_type) . " expense for {$this->month_year} - {$this->employee->name}",
                    'debit_amount' => $this->amount, // Increases expense (reduces profit)
                    'credit_amount' => 0,
                    'reference' => $payment->payment_id,
                ]);

                // 2. CREDIT: Cash/Bank Account (Asset Decrease)
                $paymentLedger = $this->payment_method === 'cash'
                    ? AccountLedger::getOrCreateCashLedger($this->employee->company_profile_id)
                    : AccountLedger::getOrCreateBankLedger($this->employee->company_profile_id, $this->payment_method);

                LedgerTransaction::create([
                    'company_profile_id' => $this->employee->company_profile_id,
                    'ledger_id' => $paymentLedger->id,
                    'date' => $this->payment_date,
                    'type' => 'payment',
                    'description' => ucfirst($this->payment_type) . " payment to {$this->employee->name}",
                    'debit_amount' => 0,
                    'credit_amount' => $this->amount, // Decreases cash/bank balance
                    'reference' => $payment->payment_id,
                ]);

                // Generate payment receipt
                try {
                    $receiptService = new PaymentReceiptService();
                    $receiptService->generatePaymentReceipt($payment);
                } catch (\Exception $e) {
                    Log::error('Failed to generate payment receipt: ' . $e->getMessage());
                }

                $this->success(
                    'Payment Processed!',
                    ucfirst($this->payment_type) . ' payment recorded successfully. Expense: +₹' . number_format($this->amount, 2)
                );
            });

            $this->showPaymentModal = false;
            $this->resetPaymentForm();
            $this->dispatch('refreshProfile');
            $this->employeeLedger->refresh();
        } catch (\Exception $e) {
            Log::error('Error processing employee payment: ' . $e->getMessage());
            $this->error('Error!', 'Failed to process payment: ' . $e->getMessage());
        }
    }

    // NEW: Method to get or create salary expense ledger
    private function getOrCreateSalaryExpenseLedger()
    {
        $ledgerName = match ($this->payment_type) {
            'salary' => 'Salary Expenses',
            'bonus' => 'Bonus Expenses',
            'advance' => 'Employee Advances',
            'overtime' => 'Overtime Expenses',
            'allowance' => 'Allowance Expenses',
            'adjustment' => 'Payroll Adjustments',
            default => 'Employee Expenses'
        };

        $expenseLedger = AccountLedger::forCompany($this->employee->company_profile_id)
            ->where('ledger_type', 'expenses')
            ->where('ledger_name', $ledgerName)
            ->first();

        if (!$expenseLedger) {
            $expenseLedger = AccountLedger::create([
                'company_profile_id' => $this->employee->company_profile_id,
                'ledger_name' => $ledgerName,
                'ledger_type' => 'expenses',
                'opening_balance' => 0,
                'opening_balance_type' => 'debit', // Expense account
                'current_balance' => 0,
                'is_active' => true,
            ]);
        }

        return $expenseLedger;
    }

    public function resetPaymentForm()
    {
        $this->amount = $this->employee->salary_amount;
        $this->payment_date = now()->format('Y-m-d');
        $this->payment_method = 'cash';
        $this->payment_type = 'salary';
        $this->month_year = now()->format('Y-m');
        $this->reference_number = '';
        $this->payment_notes = '';
    }

    public function downloadReceipt($paymentId)
    {
        $payment = EmployeePayment::findOrFail($paymentId);
        $receiptService = new PaymentReceiptService();

        // Generate receipt if it doesn't exist
        if (!$receiptService->receiptExists($payment)) {
            $receiptService->generatePaymentReceipt($payment);
        }

        $filePath = $receiptService->getReceiptPath($payment);
        $fullPath = Storage::disk('public')->path($filePath);

        if (file_exists($fullPath)) {
            return response()->download($fullPath, "payment-receipt-{$payment->payment_id}.pdf");
        }

        $this->error('Receipt not found', 'Unable to generate or find the payment receipt.');
    }

    public function regenerateReceipt($paymentId)
    {
        try {
            $payment = EmployeePayment::findOrFail($paymentId);
            $receiptService = new PaymentReceiptService();

            // Delete existing receipt if it exists
            $existingPath = $receiptService->getReceiptPath($payment);
            if (Storage::disk('public')->exists($existingPath)) {
                Storage::disk('public')->delete($existingPath);
            }

            // Generate new receipt
            $receiptService->generatePaymentReceipt($payment);

            $this->success('Receipt Regenerated!', 'Payment receipt has been regenerated successfully.');
        } catch (\Exception $e) {
            $this->error('Error!', 'Failed to regenerate receipt: ' . $e->getMessage());
        }
    }

    public function openWhatsAppModal($paymentId)
    {
        $this->selectedPaymentForWhatsApp = EmployeePayment::findOrFail($paymentId);
        $this->showWhatsAppModal = true;
    }

    public function generateWhatsAppUrl()
    {
        if (!$this->selectedPaymentForWhatsApp) {
            $this->error('Error!', 'No payment selected.');
            return null;
        }

        $receiptService = new PaymentReceiptService();

        // Ensure receipt exists
        if (!$receiptService->receiptExists($this->selectedPaymentForWhatsApp)) {
            $receiptService->generatePaymentReceipt($this->selectedPaymentForWhatsApp);
        }

        // Generate WhatsApp message
        $message = $receiptService->generateWhatsAppMessage($this->selectedPaymentForWhatsApp);
        $phone = $this->employee->phone;

        // Clean phone number (remove any non-digits except +)
        $cleanPhone = preg_replace('/[^+\d]/', '', $phone);
        if (substr($cleanPhone, 0, 1) !== '+') {
            $cleanPhone = '+91' . ltrim($cleanPhone, '0');
        }

        // Generate WhatsApp URL
        $encodedMessage = urlencode($message);
        $whatsappUrl = "https://wa.me/{$cleanPhone}?text={$encodedMessage}";

        return $whatsappUrl;
    }

    public function showWhatsAppSuccess()
    {
        $this->success('WhatsApp Ready!', 'WhatsApp will open with the payment receipt message.');
    }

    // public function sendWhatsAppMessage()
    // {
    //     if (!$this->selectedPaymentForWhatsApp) {
    //         $this->error('Error!', 'No payment selected.');
    //         return;
    //     }

    //     $receiptService = new PaymentReceiptService();

    //     // Ensure receipt exists
    //     if (!$receiptService->receiptExists($this->selectedPaymentForWhatsApp)) {
    //         $receiptService->generatePaymentReceipt($this->selectedPaymentForWhatsApp);
    //     }

    //     // Generate WhatsApp message
    //     $message = $receiptService->generateWhatsAppMessage($this->selectedPaymentForWhatsApp);
    //     $phone = $this->employee->phone;

    //     // Clean phone number (remove any non-digits except +)
    //     $cleanPhone = preg_replace('/[^+\d]/', '', $phone);
    //     if (substr($cleanPhone, 0, 1) !== '+') {
    //         $cleanPhone = '+91' . ltrim($cleanPhone, '0');
    //     }

    //     // Generate WhatsApp URL
    //     $encodedMessage = urlencode($message);
    //     $whatsappUrl = "https://wa.me/{$cleanPhone}?text={$encodedMessage}";

    //     $this->dispatch('open-whatsapp', ['url' => $whatsappUrl]);
    //     $this->showWhatsAppModal = false;

    //     $this->success('WhatsApp Ready!', 'WhatsApp will open with the payment receipt message.');
    // }

    public function render()
    {
        $payments = $this->employee->payments()
            ->with('creator')
            ->orderBy('payment_date', 'desc')
            ->paginate(10);

        // Get ledger transactions for this employee
        $ledgerTransactions = [];
        if ($this->employeeLedger) {
            $ledgerTransactions = $this->employeeLedger->transactions()
                ->orderBy('date', 'desc')
                ->limit(10)
                ->get();
        }

        $totalPaid = $this->employee->getTotalPaidAmount();
        $lastPayment = $this->employee->getLastPayment();
        $paymentsThisYear = $this->employee->payments()
            ->whereYear('payment_date', now()->year)
            ->where('status', 'paid')
            ->sum('amount');

        // Calculate ledger balance
        $ledgerBalance = $this->employeeLedger ? $this->employeeLedger->current_balance : 0;
        $outstandingSalary = abs($ledgerBalance); // If negative, it's what we owe

        return view('livewire.employee-profile', [
            'payments' => $payments,
            'totalPaid' => $totalPaid,
            'lastPayment' => $lastPayment,
            'paymentsThisYear' => $paymentsThisYear,
            'ledgerTransactions' => $ledgerTransactions,
            'ledgerBalance' => $ledgerBalance,
            'outstandingSalary' => $outstandingSalary,
        ]);
    }
}
