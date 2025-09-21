<?php
// app/Livewire/SalesReports.php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesReportExport;
use Illuminate\Support\Facades\Log;

class SalesReports extends Component
{
    use WithPagination, Toast;

    // Tab management
    public $activeTab = 'summary';

    // Date filters
    public $dateFrom;
    public $dateTo;
    public $reportPeriod = 'this_month';

    // Filters
    public $search = '';
    public $clientFilter = '';
    public $productFilter = '';
    public $categoryFilter = '';
    public $statusFilter = '';
    public $invoiceTypeFilter = '';
    public $perPage = 15;

    // Statistics
    public $totalSales = 0;
    public $totalInvoices = 0;
    public $avgOrderValue = 0;
    public $totalTax = 0;
    public $cashSales = 0;
    public $clientSales = 0;
    public $paidAmount = 0;
    public $pendingAmount = 0;

    // Charts data
    public $dailySalesChart = [];
    public $topClientsChart = [];
    public $productSalesChart = [];
    public $categorySalesChart = [];

    public function mount()
    {
        $this->setDateRange();
        $this->calculateStats();
        $this->loadChartData();
    }

    public function updatedReportPeriod()
    {
        $this->setDateRange();
        $this->calculateStats();
        $this->loadChartData();
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->calculateStats();
        $this->loadChartData();
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->calculateStats();
        $this->loadChartData();
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    private function setDateRange()
    {
        switch ($this->reportPeriod) {
            case 'today':
                $this->dateFrom = now()->toDateString();
                $this->dateTo = now()->toDateString();
                break;
            case 'yesterday':
                $this->dateFrom = now()->subDay()->toDateString();
                $this->dateTo = now()->subDay()->toDateString();
                break;
            case 'this_week':
                $this->dateFrom = now()->startOfWeek()->toDateString();
                $this->dateTo = now()->endOfWeek()->toDateString();
                break;
            case 'last_week':
                $this->dateFrom = now()->subWeek()->startOfWeek()->toDateString();
                $this->dateTo = now()->subWeek()->endOfWeek()->toDateString();
                break;
            case 'this_month':
                $this->dateFrom = now()->startOfMonth()->toDateString();
                $this->dateTo = now()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $this->dateFrom = now()->subMonth()->startOfMonth()->toDateString();
                $this->dateTo = now()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'this_quarter':
                $this->dateFrom = now()->startOfQuarter()->toDateString();
                $this->dateTo = now()->endOfQuarter()->toDateString();
                break;
            case 'this_year':
                $this->dateFrom = now()->startOfYear()->toDateString();
                $this->dateTo = now()->endOfYear()->toDateString();
                break;
            case 'custom':
                // Keep existing dates
                break;
            default:
                $this->dateFrom = now()->startOfMonth()->toDateString();
                $this->dateTo = now()->endOfMonth()->toDateString();
        }
    }

    public function calculateStats()
    {
        $query = Invoice::whereBetween('invoice_date', [$this->dateFrom, $this->dateTo]);

        $stats = $query->selectRaw('
            COUNT(*) as total_invoices,
            SUM(total_amount) as total_sales,
            SUM(total_tax) as total_tax,
            AVG(total_amount) as avg_order_value,
            SUM(paid_amount) as paid_amount,
            SUM(balance_amount) as pending_amount,
            SUM(CASE WHEN invoice_type = "cash" THEN total_amount ELSE 0 END) as cash_sales,
            SUM(CASE WHEN invoice_type = "client" THEN total_amount ELSE 0 END) as client_sales
        ')->first();

        $this->totalInvoices = $stats->total_invoices ?? 0;
        $this->totalSales = $stats->total_sales ?? 0;
        $this->totalTax = $stats->total_tax ?? 0;
        $this->avgOrderValue = $stats->avg_order_value ?? 0;
        $this->paidAmount = $stats->paid_amount ?? 0;
        $this->pendingAmount = $stats->pending_amount ?? 0;
        $this->cashSales = $stats->cash_sales ?? 0;
        $this->clientSales = $stats->client_sales ?? 0;
    }

    public function loadChartData()
    {
        // Daily Sales Chart
        $this->dailySalesChart = $this->getDailySalesData();

        // Top Clients Chart
        $this->topClientsChart = $this->getTopClientsData();

        Log::info('Top Clients Chart Data: ' . json_encode($this->topClientsChart));

        // Product Sales Chart
        $this->productSalesChart = $this->getProductSalesData();

        // Category Sales Chart
        $this->categorySalesChart = $this->getCategorySalesData();
    }

    private function getDailySalesData()
    {
        $data = Invoice::whereBetween('invoice_date', [$this->dateFrom, $this->dateTo])
            ->selectRaw('DATE(invoice_date) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(function ($date) {
                return \Carbon\Carbon::parse($date)->format('M d');
            })->toArray(),
            'data' => $data->pluck('total')->map(function ($value) {
                return (float) $value; // Convert to float
            })->toArray(),
        ];
    }

    private function getTopClientsData()
    {
        $data = Invoice::whereBetween('invoice_date', [$this->dateFrom, $this->dateTo])
            ->where('invoice_type', 'client')
            ->with('client')
            ->selectRaw('client_id, SUM(total_amount) as total')
            ->groupBy('client_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->map(function ($item) {
                return $item->client ? $item->client->name : 'Unknown Client';
            })->toArray(),
            'data' => $data->pluck('total')->map(function ($value) {
                return (float) $value; // Convert to float
            })->toArray(),
        ];
    }

    private function getProductSalesData()
    {
        $data = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->whereBetween('invoices.invoice_date', [$this->dateFrom, $this->dateTo])
            ->select('products.name', DB::raw('SUM(invoice_items.total_amount) as total'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->pluck('name')->toArray(),
            'data' => $data->pluck('total')->map(function ($value) {
                return (float) $value; // Convert to float
            })->toArray(),
        ];
    }

    private function getCategorySalesData()
    {
        $data = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->whereBetween('invoices.invoice_date', [$this->dateFrom, $this->dateTo])
            ->select('product_categories.name', DB::raw('SUM(invoice_items.total_amount) as total'))
            ->groupBy('product_categories.id', 'product_categories.name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $data->pluck('name')->toArray(),
            'data' => $data->pluck('total')->map(function ($value) {
                return (float) $value; // Convert to float
            })->toArray(),
        ];
    }

    public function exportSalesReport($format = 'excel')
    {
        $query = $this->getFilteredQuery();

        $data = [
            'invoices' => $query->get(),
            'stats' => [
                'period' => $this->dateFrom . ' to ' . $this->dateTo,
                'total_sales' => $this->totalSales,
                'total_invoices' => $this->totalInvoices,
                'avg_order_value' => $this->avgOrderValue,
            ]
        ];

        $filename = 'sales_report_' . $this->dateFrom . '_to_' . $this->dateTo;

        if ($format === 'excel') {
            return Excel::download(new SalesReportExport($data), $filename . '.xlsx');
        } elseif ($format === 'csv') {
            return Excel::download(new SalesReportExport($data), $filename . '.csv');
        }
    }

    private function getFilteredQuery()
    {
        $query = Invoice::with(['client', 'items.product'])
            ->whereBetween('invoice_date', [$this->dateFrom, $this->dateTo]);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                    ->orWhere('client_name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('client', function ($clientQuery) {
                        $clientQuery->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->clientFilter) {
            $query->where('client_id', $this->clientFilter);
        }

        if ($this->statusFilter) {
            $query->where('payment_status', $this->statusFilter);
        }

        if ($this->invoiceTypeFilter) {
            $query->where('invoice_type', $this->invoiceTypeFilter);
        }

        if ($this->categoryFilter) {
            $query->whereHas('items.product', function ($q) {
                $q->where('category_id', $this->categoryFilter);
            });
        }

        return $query->orderBy('invoice_date', 'desc');
    }

    public function render()
    {
        $query = $this->getFilteredQuery();
        $invoices = $query->paginate($this->perPage);

        return view('livewire.sales-reports', [
            'invoices' => $invoices,
            'clients' => Client::where('is_active', true)->get(),
            'products' => Product::where('is_active', true)->get(),
            'categories' => ProductCategory::where('is_active', true)->get(),
        ]);
    }
}
