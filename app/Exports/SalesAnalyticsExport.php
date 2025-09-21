<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SalesAnalyticsExport implements WithMultipleSheets
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        $sheets = [];

        if (isset($this->data['performance'])) {
            $sheets[] = new ProductPerformanceSheet($this->data['performance']);
        }

        if (isset($this->data['clients'])) {
            $sheets[] = new ClientPerformanceSheet($this->data['clients']);
        }

        if (isset($this->data['categories'])) {
            $sheets[] = new CategoryPerformanceSheet($this->data['categories']);
        }

        return $sheets;
    }
}

class ProductPerformanceSheet implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data);
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'Unit',
            'Total Revenue',
            'Total Quantity',
            'Total Orders',
            'Average Price',
            'Revenue per Order'
        ];
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->unit,
            number_format($product->total_revenue, 2),
            number_format($product->total_quantity, 2),
            $product->total_orders,
            number_format($product->avg_price, 2),
            number_format($product->revenue_per_order, 2)
        ];
    }
}
