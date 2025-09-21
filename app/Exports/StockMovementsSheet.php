<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockMovementsSheet implements FromCollection, WithHeadings, WithTitle, WithMapping, ShouldAutoSize
{
    protected $stockMovements;

    public function __construct($stockMovements)
    {
        $this->stockMovements = $stockMovements;
    }

    public function collection()
    {
        return $this->stockMovements;
    }

    public function headings(): array
    {
        return [
            'Date & Time',
            'Product Name',
            'Category',
            'Movement Type',
            'Quantity',
            'Unit',
            'Reason',
            'Reference Type',
            'Reference ID',
            'Current Stock After Movement'
        ];
    }

    public function map($movement): array
    {
        $referenceType = $this->getReadableReferenceType($movement->reference_type);

        return [
            $movement->created_at->format('Y-m-d H:i:s'),
            $movement->product->name ?? 'N/A',
            $movement->product->category->name ?? 'N/A',
            strtoupper($movement->type) . ' (' . ($movement->type === 'in' ? 'Stock In' : 'Stock Out') . ')',
            number_format($movement->quantity, 2),
            strtoupper($movement->product->unit ?? ''),
            ucfirst($movement->reason ?? 'N/A'),
            $referenceType,
            $movement->reference_id ?? 'N/A',
            number_format($movement->product->stock_quantity ?? 0, 2)
        ];
    }

    private function getReadableReferenceType($referenceType)
    {
        if (!$referenceType) {
            return 'N/A';
        }

        // Extract class name from full namespace
        $className = class_basename($referenceType);

        switch ($className) {
            case 'ChallanItem':
                return 'Purchase/Challan';
            case 'StockAdjustment':
                return 'Stock Adjustment';
            case 'SalesItem':
                return 'Sales';
            default:
                return $className;
        }
    }

    public function title(): string
    {
        return 'Stock Movements';
    }
}
