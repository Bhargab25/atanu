<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Employee;
use App\Models\EmployeePayment;
use Mary\Traits\Toast;
use Livewire\WithPagination;
use App\Services\PaymentReceiptService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EmployeeProfile extends Component
{
    use Toast, WithPagination;

    public Employee $employee;
    public $showPaymentModal = false;

    // Payment form
    public $amount;
    public $payment_date;
    public $payment_method = 'cash';
    public $reference_number;
    public $month_year;
    public $payment_notes;
    public $showWhatsAppModal = false;
    public $selectedPaymentForWhatsApp = null;

    public $paymentMethods = [
        ['value' => 'cash', 'label' => 'Cash'],
        ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
        ['value' => 'check', 'label' => 'Check'],
    ];

    protected $listeners = ['refreshProfile' => '$refresh'];

    public function mount($employeeId)
    {
        $this->employee = Employee::findOrFail($employeeId);
        $this->payment_date = now()->format('Y-m-d');
        $this->month_year = now()->format('Y-m');
        $this->amount = $this->employee->salary_amount;
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
            'month_year' => 'required|date_format:Y-m',
            'reference_number' => 'nullable|string|max:100',
            'payment_notes' => 'nullable|string|max:500',
        ]);

        // Check if payment already exists for this month
        if ($this->employee->hasPaymentForMonth($this->month_year)) {
            $this->error('Payment Already Exists!', 'A payment for this month already exists.');
            return;
        }

        EmployeePayment::create([
            'employee_id' => $this->employee->id,
            'payment_id' => EmployeePayment::generatePaymentId(),
            'amount' => $this->amount,
            'payment_date' => $this->payment_date,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'notes' => $this->payment_notes,
            'month_year' => $this->month_year,
            'status' => 'paid',
            'created_by' => auth()->id(),
        ]);

        try {
            $receiptService = new PaymentReceiptService();
            $receiptService->generatePaymentReceipt($payment);
        } catch (\Exception $e) {
            Log::error('Failed to generate payment receipt: ' . $e->getMessage());
        }

        $this->success('Payment Processed!', 'Salary payment has been recorded successfully.');
        $this->showPaymentModal = false;
        $this->resetPaymentForm();
        $this->dispatch('refreshProfile');
    }

    public function resetPaymentForm()
    {
        $this->amount = $this->employee->salary_amount;
        $this->payment_date = now()->format('Y-m-d');
        $this->payment_method = 'cash';
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

        $totalPaid = $this->employee->getTotalPaidAmount();
        $lastPayment = $this->employee->getLastPayment();
        $paymentsThisYear = $this->employee->payments()
            ->whereYear('payment_date', now()->year)
            ->where('status', 'paid')
            ->sum('amount');

        return view('livewire.employee-profile', [
            'payments' => $payments,
            'totalPaid' => $totalPaid,
            'lastPayment' => $lastPayment,
            'paymentsThisYear' => $paymentsThisYear,
        ]);
    }
}
