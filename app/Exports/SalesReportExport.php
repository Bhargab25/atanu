<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data['invoices'];
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Date',
            'Client Name',
            'Type',
            'Total Items',
            'Subtotal',
            'Tax Amount',
            'Total Amount',
            'Paid Amount',
            'Balance Amount',
            'Status'
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->invoice_date->format('d/m/Y'),
            $invoice->display_client_name,
            ucfirst($invoice->invoice_type),
            $invoice->items->count(),
            number_format($invoice->subtotal, 2),
            number_format($invoice->total_tax, 2),
            number_format($invoice->total_amount, 2),
            number_format($invoice->paid_amount, 2),
            number_format($invoice->balance_amount, 2),
            ucfirst($invoice->payment_status)
        ];
    }

    public function title(): string
    {
        return 'Sales Report';
    }
}
