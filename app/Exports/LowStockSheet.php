<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LowStockSheet implements FromCollection, WithHeadings, WithTitle, WithMapping, ShouldAutoSize
{
    protected $lowStockProducts;

    public function __construct($lowStockProducts)
    {
        $this->lowStockProducts = $lowStockProducts;
    }

    public function collection()
    {
        return $this->lowStockProducts;
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'Category',
            'Current Stock',
            'Min Stock Quantity',
            'Stock Deficit',
            'Unit',
            'Stock Status',
            'Priority Level',
            'Last Updated'
        ];
    }

    public function map($product): array
    {
        $stockDeficit = $product->min_stock_quantity - $product->stock_quantity;
        $priorityLevel = $this->getPriorityLevel($product);

        return [
            $product->name,
            $product->category->name ?? 'N/A',
            number_format($product->stock_quantity, 2),
            number_format($product->min_stock_quantity, 2),
            number_format($stockDeficit, 2),
            strtoupper($product->unit),
            $product->stock_status,
            $priorityLevel,
            $product->updated_at->format('Y-m-d H:i:s')
        ];
    }

    private function getPriorityLevel($product)
    {
        if ($product->stock_quantity == 0) {
            return 'Critical';
        }
        
        $stockRatio = $product->stock_quantity / $product->min_stock_quantity;
        
        if ($stockRatio <= 0.25) {
            return 'High';
        } elseif ($stockRatio <= 0.50) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }

    public function title(): string
    {
        return 'Low Stock Products';
    }
}
