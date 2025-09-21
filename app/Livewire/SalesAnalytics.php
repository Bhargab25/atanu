<?php
// app/Livewire/SalesAnalytics.php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesAnalyticsExport;

class SalesAnalytics extends Component
{
    use WithPagination, Toast;

    // Tab management
    public $activeTab = 'performance';

    // Date filters
    public $dateFrom;
    public $dateTo;
    public $comparisonPeriod = 'previous_month';

    // Filters
    public $search = '';
    public $clientFilter = '';
    public $productFilter = '';
    public $categoryFilter = '';
    public $perPage = 15;

    // Performance Metrics
    public $currentPeriodSales = 0;
    public $previousPeriodSales = 0;
    public $salesGrowth = 0;
    public $avgOrderValue = 0;
    public $avgOrderGrowth = 0;
    public $topSellingProduct = '';
    public $topPayingClient = '';
    public $salesTrend = [];

    // Analytics Data
    public $productPerformance = [];
    public $clientPerformance = [];
    public $categoryPerformance = [];
    public $salesByHour = [];
    public $salesByDay = [];
    public $salesByMonth = [];

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
        $this->loadAnalyticsData();
    }

    public function updatedDateFrom()
    {
        $this->loadAnalyticsData();
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->loadAnalyticsData();
        $this->resetPage();
    }

    public function updatedComparisonPeriod()
    {
        $this->loadAnalyticsData();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function loadAnalyticsData()
    {
        $this->calculatePerformanceMetrics();
        $this->loadProductPerformance();
        $this->loadClientPerformance();
        $this->loadCategoryPerformance();
        $this->loadSalesPatterns();
    }

    private function calculatePerformanceMetrics()
    {
        // Current period stats
        $currentStats = Invoice::whereBetween('invoice_date', [$this->dateFrom, $this->dateTo])
            ->selectRaw('
                SUM(total_amount) as total_sales,
                AVG(total_amount) as avg_order_value,
                COUNT(*) as total_orders
            ')
            ->first();

        $this->currentPeriodSales = $currentStats->total_sales ?? 0;
        $this->avgOrderValue = $currentStats->avg_order_value ?? 0;

        // Previous period for comparison
        $daysDiff = now()->parse($this->dateTo)->diffInDays(now()->parse($this->dateFrom));
        $prevDateFrom = now()->parse($this->dateFrom)->subDays($daysDiff + 1)->format('Y-m-d');
        $prevDateTo = now()->parse($this->dateFrom)->subDay()->format('Y-m-d');

        $previousStats = Invoice::whereBetween('invoice_date', [$prevDateFrom, $prevDateTo])
            ->selectRaw('
                SUM(total_amount) as total_sales,
                AVG(total_amount) as avg_order_value
            ')
            ->first();

        $this->previousPeriodSales = $previousStats->total_sales ?? 0;
        $previousAvgOrder = $previousStats->avg_order_value ?? 0;

        // Calculate growth
        $this->salesGrowth = $this->previousPeriodSales > 0
            ? (($this->currentPeriodSales - $this->previousPeriodSales) / $this->previousPeriodSales) * 100
            : 0;

        $this->avgOrderGrowth = $previousAvgOrder > 0
            ? (($this->avgOrderValue - $previousAvgOrder) / $previousAvgOrder) * 100
            : 0;

        // Top performing product and client
        $topProduct = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->whereBetween('invoices.invoice_date', [$this->dateFrom, $this->dateTo])
            ->select('products.name', DB::raw('SUM(invoice_items.total_amount) as total'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total')
            ->first();

        $this->topSellingProduct = $topProduct->name ?? 'N/A';

        $topClient = Invoice::whereBetween('invoice_date', [$this->dateFrom, $this->dateTo])
            ->where('invoice_type', 'client')
            ->with('client')
            ->select('client_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('client_id')
            ->orderByDesc('total')
            ->first();

        $this->topPayingClient = $topClient && $topClient->client ? $topClient->client->name : 'N/A';
    }

    private function loadProductPerformance()
    {
        $this->productPerformance = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->whereBetween('invoices.invoice_date', [$this->dateFrom, $this->dateTo])
            ->select(
                'products.name',
                'products.unit',
                DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                DB::raw('SUM(invoice_items.total_amount) as total_revenue'),
                DB::raw('COUNT(DISTINCT invoices.id) as total_orders'),
                DB::raw('AVG(invoice_items.unit_price) as avg_price')
            )
            ->groupBy('products.id', 'products.name', 'products.unit')
            ->orderByDesc('total_revenue')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                $item->revenue_per_order = $item->total_orders > 0 ? $item->total_revenue / $item->total_orders : 0;
                return $item;
            });
    }

    private function loadClientPerformance()
    {
        $this->clientPerformance = Invoice::whereBetween('invoice_date', [$this->dateFrom, $this->dateTo])
            ->where('invoice_type', 'client')
            ->with('client')
            ->select(
                'client_id',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('AVG(total_amount) as avg_order_value'),
                DB::raw('MAX(invoice_date) as last_order_date')
            )
            ->groupBy('client_id')
            ->orderByDesc('total_spent')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                $item->client_name = $item->client ? $item->client->name : 'Unknown';
                $item->days_since_last_order = now()->diffInDays($item->last_order_date);
                return $item;
            });
    }

    private function loadCategoryPerformance()
    {
        $this->categoryPerformance = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->whereBetween('invoices.invoice_date', [$this->dateFrom, $this->dateTo])
            ->select(
                'product_categories.name',
                DB::raw('SUM(invoice_items.total_amount) as total_revenue'),
                DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT products.id) as unique_products'),
                DB::raw('COUNT(DISTINCT invoices.client_id) as unique_customers')
            )
            ->groupBy('product_categories.id', 'product_categories.name')
            ->orderByDesc('total_revenue')
            ->get();
    }

    private function loadSalesPatterns()
    {
        // Sales by hour of day
        $this->salesByHour = Invoice::whereBetween('invoice_date', [$this->dateFrom, $this->dateTo])
            ->selectRaw('HOUR(created_at) as hour, SUM(total_amount) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('total', 'hour')
            ->toArray();

        // Sales by day of week
        $this->salesByDay = Invoice::whereBetween('invoice_date', [$this->dateFrom, $this->dateTo])
            ->selectRaw('DAYOFWEEK(invoice_date) as day, SUM(total_amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->pluck('total', 'day')
            ->mapWithKeys(function ($total, $day) {
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                return [$days[$day - 1] => $total];
            })
            ->toArray();

        // Sales trend over the period
        $this->salesTrend = Invoice::whereBetween('invoice_date', [$this->dateFrom, $this->dateTo])
            ->selectRaw('DATE(invoice_date) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'total' => (float) $item->total,
                    'formatted_date' => \Carbon\Carbon::parse($item->date)->format('M d')
                ];
            })
            ->toArray();
    }

    public function exportAnalytics($type = 'performance')
    {
        $data = [
            'performance' => $this->productPerformance,
            'clients' => $this->clientPerformance,
            'categories' => $this->categoryPerformance,
            'metrics' => [
                'current_sales' => $this->currentPeriodSales,
                'previous_sales' => $this->previousPeriodSales,
                'growth' => $this->salesGrowth,
                'avg_order_value' => $this->avgOrderValue,
            ]
        ];

        $filename = "sales_analytics_{$type}_" . now()->format('Y_m_d_H_i');

        return Excel::download(new SalesAnalyticsExport($data), $filename . '.xlsx');
    }

    public function render()
    {
        $query = $this->getFilteredQuery();

        return view('livewire.sales-analytics', [
            'data' => $query->paginate($this->perPage),
            'clients' => Client::where('is_active', true)->get(),
            'products' => Product::where('is_active', true)->get(),
            'categories' => ProductCategory::where('is_active', true)->get(),
        ]);
    }

    private function getFilteredQuery()
    {
        return DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->leftJoin('clients', 'invoices.client_id', '=', 'clients.id')
            ->whereBetween('invoices.invoice_date', [$this->dateFrom, $this->dateTo])
            ->when($this->search, function ($q) {
                return $q->where(function ($query) {
                    $query->where('products.name', 'like', '%' . $this->search . '%')
                        ->orWhere('clients.name', 'like', '%' . $this->search . '%')
                        ->orWhere('invoices.invoice_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->clientFilter, function ($q) {
                return $q->where('invoices.client_id', $this->clientFilter);
            })
            ->when($this->productFilter, function ($q) {
                return $q->where('invoice_items.product_id', $this->productFilter);
            })
            ->when($this->categoryFilter, function ($q) {
                return $q->where('products.category_id', $this->categoryFilter);
            })
            ->select(
                'invoices.invoice_number',
                'invoices.invoice_date',
                'products.name as product_name',
                'clients.name as client_name',
                'invoice_items.quantity',
                'invoice_items.unit_price',
                'invoice_items.total_amount',
                'products.unit'
            )
            ->orderBy('invoices.invoice_date', 'desc');
    }
}
