<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class InvoicePdfService
{
    public function generateInvoicePdf(Invoice $invoice)
    {
        // Load the invoice with relationships
        $invoice->load(['client', 'company']);

        // Generate PDF
        $pdf = Pdf::loadView('pdf.invoice', compact('invoice'));

        // Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');

        // Generate filename
        $filename = 'invoices/' . $invoice->invoice_number . '.pdf';

        // Save PDF
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    public function getInvoicePdfPath(Invoice $invoice)
    {
        return 'invoices/' . $invoice->invoice_number . '.pdf';
    }

    public function invoicePdfExists(Invoice $invoice)
    {
        return Storage::disk('public')->exists($this->getInvoicePdfPath($invoice));
    }
}
