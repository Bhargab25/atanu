<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockOverviewSheet implements FromCollection, WithHeadings, WithTitle, WithMapping, ShouldAutoSize
{
    protected $reportData;

    public function __construct($reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        return $this->reportData['topProducts'];
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'Category',
            'Current Stock',
            'Min Stock',
            'Unit',
            'Stock Status',
            'Last Updated'
        ];
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->category->name,
            number_format($product->stock_quantity, 2),
            number_format($product->min_stock_quantity, 2),
            strtoupper($product->unit),
            $product->stock_status,
            $product->updated_at->format('Y-m-d H:i:s')
        ];
    }

    public function title(): string
    {
        return 'Stock Overview';
    }
}
