<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CategoryReportExport implements FromCollection, WithHeadings, WithTitle, WithMapping, ShouldAutoSize
{
    protected $categoryData;

    public function __construct($categoryData)
    {
        $this->categoryData = is_array($categoryData) ? collect($categoryData) : $categoryData;
    }

    public function collection()
    {
        return $this->categoryData;
    }

    public function headings(): array
    {
        return [
            'Category Name',
            'Total Products',
            'Total Stock Quantity',
            'Low Stock Count',
            'Out of Stock Count',
            'Stock Health Status',
            'Recommended Action'
        ];
    }

    public function map($category): array
    {
        $stockHealthPercentage = $category['total_products'] > 0
            ? (($category['total_products'] - $category['low_stock_count']) / $category['total_products']) * 100
            : 0;

        $healthStatus = $this->getHealthStatus($stockHealthPercentage);
        $recommendedAction = $this->getRecommendedAction($category);

        return [
            $category['name'],
            $category['total_products'],
            number_format($category['total_stock'], 2),
            $category['low_stock_count'],
            $category['out_of_stock_count'] ?? 0,
            $healthStatus,
            $recommendedAction
        ];
    }

    private function getHealthStatus($percentage)
    {
        if ($percentage >= 90) return 'Excellent';
        if ($percentage >= 75) return 'Good';
        if ($percentage >= 50) return 'Fair';
        if ($percentage >= 25) return 'Poor';
        return 'Critical';
    }

    private function getRecommendedAction($category)
    {
        if ($category['low_stock_count'] == 0) {
            return 'No action required';
        } elseif ($category['low_stock_count'] <= 2) {
            return 'Monitor closely';
        } elseif ($category['low_stock_count'] <= 5) {
            return 'Reorder soon';
        } else {
            return 'Urgent reorder required';
        }
    }

    public function title(): string
    {
        return 'Category Analysis Report';
    }
}
