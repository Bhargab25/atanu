<?php
// app/Services/InvoicePdfService.php

namespace App\Services;

use App\Models\Invoice;
use App\Models\MonthlyBill;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function generateInvoicePdf(Invoice $invoice)
    {
        $data = [
            'invoice' => $invoice->load(['client', 'items.product']),
            'company' => $this->getCompanyDetails(),
        ];

        // Change from 'pdf.invoice' to 'pdfs.invoice'
        $pdf = Pdf::loadView('pdfs.invoice', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = "invoice_{$invoice->invoice_number}.pdf";
        $path = storage_path("app/public/invoices/{$filename}");

        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);

        return $path;
    }

    public function generateMonthlyBillPdf(MonthlyBill $monthlyBill)
    {
        $data = [
            'monthlyBill' => $monthlyBill->load(['client', 'invoices']),
            'company' => $this->getCompanyDetails(),
        ];

        // Change from 'pdf.monthly-bill' to 'pdfs.monthly-bill'
        $pdf = Pdf::loadView('pdfs.monthly-bill', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = "monthly_bill_{$monthlyBill->bill_number}.pdf";
        $path = storage_path("app/public/bills/{$filename}");

        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);

        return $path;
    }

    private function getCompanyDetails()
    {
        return [
            'name' => config('app.company_name', 'NEW ANNAPURNA FRUIT SHOP'),
            'address' => config('app.company_address', '9 M Block, New Market'),
            'city' => config('app.company_city', 'Kolkata'),
            'state' => config('app.company_state', 'West Bengal'),
            'pincode' => config('app.company_pincode', '700087'),
            'phone' => config('app.company_phone', '+91 8436833830 / +91 9732615108'),
            'email' => config('app.company_email', 'info@company.com'),
            'gstin' => config('app.company_gstin', '12823019000867'),
            'pan' => config('app.company_pan', 'XXXXXXXXXX'),
        ];
    }
}
