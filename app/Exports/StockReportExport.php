<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StockReportExport implements WithMultipleSheets
{
    protected $reportData;

    public function __construct($reportData)
    {
        $this->reportData = $reportData;
    }

    public function sheets(): array
    {
        return [
            new StockOverviewSheet($this->reportData),
            new CategoryStockSheet($this->reportData['categoryStock']),
            new LowStockSheet($this->reportData['lowStockProducts']),
            new StockMovementsSheet($this->reportData['stockMovements']),
        ];
    }
}
