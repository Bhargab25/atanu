<?php

namespace App\Services;

use App\Models\EmployeePayment;
use App\Models\CompanyProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PaymentReceiptService
{
    public function generatePaymentReceipt(EmployeePayment $payment)
    {
        $company = CompanyProfile::current();

        $data = [
            'payment' => $payment,
            'employee' => $payment->employee,
            'company' => $company,
            'generatedAt' => now(),
        ];

        $pdf = PDF::loadView('pdfs.payment-receipt', $data);
        $pdf->setPaper('A4', 'portrait');

        // Create directory if it doesn't exist
        $directory = 'payment-receipts/' . $payment->employee->employee_id;
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // Generate filename
        $filename = "payment-receipt-{$payment->payment_id}.pdf";
        $filePath = $directory . '/' . $filename;

        // Save PDF
        Storage::disk('public')->put($filePath, $pdf->output());

        return $filePath;
    }

    public function getReceiptPath(EmployeePayment $payment)
    {
        $directory = 'payment-receipts/' . $payment->employee->employee_id;
        $filename = "payment-receipt-{$payment->payment_id}.pdf";
        return $directory . '/' . $filename;
    }

    public function receiptExists(EmployeePayment $payment)
    {
        $path = $this->getReceiptPath($payment);
        return Storage::disk('public')->exists($path);
    }

    public function generateWhatsAppMessage(EmployeePayment $payment)
    {
        $employee = $payment->employee;
        $monthYear = \Carbon\Carbon::createFromFormat('Y-m', $payment->month_year)->format('F Y');

        $message = "Hello {$employee->name}!\n\n";
        $message .= "Your salary payment receipt for {$monthYear} is ready.\n\n";
        $message .= "📋 Payment Details:\n";
        $message .= "• Payment ID: {$payment->payment_id}\n";
        $message .= "• Amount: ₹" . number_format($payment->amount, 2) . "\n";
        $message .= "• Date: " . $payment->payment_date->format('d M, Y') . "\n";
        $message .= "• Method: " . ucfirst(str_replace('_', ' ', $payment->payment_method)) . "\n\n";
        $message .= "Thank you for your valuable service!\n\n";
        $message .= "Best regards,\n";
        $message .= $payment->employee->company->name ?? 'Management';

        return $message;
    }
}
