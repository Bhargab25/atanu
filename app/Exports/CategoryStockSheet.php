<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CategoryStockSheet implements FromCollection, WithHeadings, WithTitle, WithMapping, ShouldAutoSize
{
    protected $categoryData;

    public function __construct($categoryData)
    {
        $this->categoryData = $categoryData;
    }

    public function collection()
    {
        return collect($this->categoryData);
    }

    public function headings(): array
    {
        return ['Category', 'Total Products', 'Total Stock', 'Low Stock Items'];
    }

    public function map($category): array
    {
        return [
            $category['name'],
            $category['total_products'],
            number_format($category['total_stock'], 2),
            $category['low_stock_count']
        ];
    }

    public function title(): string
    {
        return 'Category Stock';
    }
}
