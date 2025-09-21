<div>
    <x-mary-header title="Sales Reports" subtitle="Comprehensive sales analytics and reporting" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                <x-mary-button icon="o-arrow-down-tray" label="Export Excel" class="btn-success"
                    @click="$wire.exportSalesReport('excel')" />
                <x-mary-button icon="o-arrow-down-tray" label="Export CSV" class="btn-primary"
                    @click="$wire.exportSalesReport('csv')" />
            </div>
        </x-slot:middle>
    </x-mary-header>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Total Sales</p>
                    <p class="text-3xl font-bold">₹{{ number_format($totalSales) }}</p>
                </div>
                <x-mary-icon name="o-currency-rupee" class="w-12 h-12 text-blue-200" />
            </div>
        </div>

        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Total Invoices</p>
                    <p class="text-3xl font-bold">{{ number_format($totalInvoices) }}</p>
                </div>
                <x-mary-icon name="o-document-text" class="w-12 h-12 text-green-200" />
            </div>
        </div>

        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Avg Order Value</p>
                    <p class="text-3xl font-bold">₹{{ number_format($avgOrderValue) }}</p>
                </div>
                <x-mary-icon name="o-chart-bar" class="w-12 h-12 text-purple-200" />
            </div>
        </div>

        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm">Total Tax</p>
                    <p class="text-3xl font-bold">₹{{ number_format($totalTax) }}</p>
                </div>
                <x-mary-icon name="o-receipt-percent" class="w-12 h-12 text-orange-200" />
            </div>
        </div>
    </div>

    {{-- Date Range & Period Filter --}}
    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-mary-select
                label="Report Period"
                wire:model.live="reportPeriod"
                :options="[
                    ['value' => 'today', 'label' => 'Today'],
                    ['value' => 'yesterday', 'label' => 'Yesterday'],
                    ['value' => 'this_week', 'label' => 'This Week'],
                    ['value' => 'last_week', 'label' => 'Last Week'],
                    ['value' => 'this_month', 'label' => 'This Month'],
                    ['value' => 'last_month', 'label' => 'Last Month'],
                    ['value' => 'this_quarter', 'label' => 'This Quarter'],
                    ['value' => 'this_year', 'label' => 'This Year'],
                    ['value' => 'custom', 'label' => 'Custom Range']
                ]"
                option-value="value"
                option-label="label" />

            @if($reportPeriod === 'custom')
            <x-mary-input label="From Date" wire:model.live="dateFrom" type="date" />
            <x-mary-input label="To Date" wire:model.live="dateTo" type="date" />
            @else
            <div class="col-span-2 flex items-end">
                <div class="text-sm text-gray-600">
                    <strong>Selected Period:</strong>
                    {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} -
                    {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
                </div>
            </div>
            @endif
        </div>
    </x-mary-card>

    {{-- Tabs Navigation --}}
    <div class="mb-6">
        <div class="bg-gray-100 rounded-xl p-1 inline-flex">
            <button wire:click="switchTab('summary')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'summary' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Summary
            </button>
            <button wire:click="switchTab('detailed')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'detailed' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Detailed Report
            </button>
            <!-- <button wire:click="switchTab('analytics')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'analytics' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Analytics
            </button> -->
        </div>
    </div>

    {{-- Tab Content --}}
    @if($activeTab === 'summary')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <x-mary-card>
            <x-mary-stat title="Cash Sales" description="Direct cash transactions"
                value="₹{{ number_format($cashSales) }}" icon="o-banknotes" />
        </x-mary-card>

        <x-mary-card>
            <x-mary-stat title="Client Sales" description="Credit transactions"
                value="₹{{ number_format($clientSales) }}" icon="o-users" />
        </x-mary-card>

        <x-mary-card>
            <x-mary-stat title="Paid Amount" description="Collected payments"
                value="₹{{ number_format($paidAmount) }}" icon="o-check-circle" />
        </x-mary-card>

        <x-mary-card>
            <x-mary-stat title="Pending Amount" description="Outstanding balance"
                value="₹{{ number_format($pendingAmount) }}" icon="o-clock" />
        </x-mary-card>
    </div>

    {{-- Daily Sales Chart --}}
    <x-mary-card class="mb-6">
        <x-mary-header title="Daily Sales Trend" subtitle="Sales performance over time" />
        <div id="dailySalesChart" style="height: 400px;"></div>
    </x-mary-card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <x-mary-card>
            <x-mary-header title="Top Clients" subtitle="Best performing clients by sales" />
            <div id="topClientsChart" style="height: 300px;"></div>
        </x-mary-card>

        <x-mary-card>
            <x-mary-header title="Category Sales" subtitle="Sales by product category" />
            <div id="categorySalesChart" style="height: 300px;"></div>
        </x-mary-card>
    </div>

    <x-mary-card>
        <x-mary-header title="Top Products" subtitle="Best selling products" />
        <div id="productSalesChart" style="height: 400px;"></div>
    </x-mary-card>

    @elseif($activeTab === 'detailed')
    {{-- Filters --}}
    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <x-mary-input label="Search" wire:model.live.debounce.300ms="search"
                placeholder="Invoice, client name..." icon="o-magnifying-glass" />

            <x-mary-select label="Client" wire:model.live="clientFilter"
                :options="$clients" option-value="id" option-label="name"
                placeholder="All Clients" />

            <x-mary-select label="Status" wire:model.live="statusFilter"
                :options="[
                        ['value' => '', 'label' => 'All Status'],
                        ['value' => 'paid', 'label' => 'Paid'],
                        ['value' => 'unpaid', 'label' => 'Unpaid'],
                        ['value' => 'partial', 'label' => 'Partial']
                    ]" option-value="value" option-label="label" />

            <x-mary-select label="Type" wire:model.live="invoiceTypeFilter"
                :options="[
                        ['value' => '', 'label' => 'All Types'],
                        ['value' => 'cash', 'label' => 'Cash'],
                        ['value' => 'client', 'label' => 'Client']
                    ]" option-value="value" option-label="label" />

            <x-mary-select label="Category" wire:model.live="categoryFilter"
                :options="$categories" option-value="id" option-label="name"
                placeholder="All Categories" />
        </div>
    </x-mary-card>

    {{-- Detailed Invoice Table --}}
    <x-mary-card>
        <x-mary-table
            :headers="[
                    ['label' => '#', 'key' => 'sl_no'],
                    ['label' => 'Invoice No.', 'key' => 'invoice_number'],
                    ['label' => 'Date', 'key' => 'invoice_date'],
                    ['label' => 'Client', 'key' => 'client'],
                    ['label' => 'Type', 'key' => 'type'],
                    ['label' => 'Items', 'key' => 'items'],
                    ['label' => 'Amount', 'key' => 'amount'],
                    ['label' => 'Tax', 'key' => 'tax'],
                    ['label' => 'Status', 'key' => 'status']
                ]"
            :rows="$invoices"
            striped
            with-pagination>

            @scope('cell_sl_no', $invoice)
            <span class="font-medium">{{ $loop->iteration }}</span>
            @endscope

            @scope('cell_invoice_number', $invoice)
            <div class="font-medium text-primary">{{ $invoice->invoice_number }}</div>
            @endscope

            @scope('cell_invoice_date', $invoice)
            {{ $invoice->invoice_date->format('d/m/Y') }}
            @endscope

            @scope('cell_client', $invoice)
            <div class="font-medium">{{ $invoice->display_client_name }}</div>
            @endscope

            @scope('cell_type', $invoice)
            <x-mary-badge :value="ucfirst($invoice->invoice_type)"
                :class="$invoice->invoice_type === 'client' ? 'badge-primary' : 'badge-success'" />
            @endscope

            @scope('cell_items', $invoice)
            <span class="badge badge-info">{{ $invoice->items->count() }}</span>
            @endscope

            @scope('cell_amount', $invoice)
            <div class="text-right font-mono">₹{{ number_format($invoice->total_amount, 2) }}</div>
            @endscope

            @scope('cell_tax', $invoice)
            <div class="text-right font-mono">₹{{ number_format($invoice->total_tax, 2) }}</div>
            @endscope

            @scope('cell_status', $invoice)
            <x-mary-badge :value="ucfirst($invoice->payment_status)"
                :class="$invoice->status_badge_class" />
            @endscope
        </x-mary-table>
    </x-mary-card>

    @elseif($activeTab === 'analytics')
    {{-- Analytics Charts --}}
    
    @endif

    {{-- Charts JavaScript --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
        });

        function initializeCharts() {
            // Daily Sales Chart
            if (document.querySelector("#dailySalesChart")) {
                const dailySalesOptions = {
                    series: [{
                        name: 'Sales',
                        data: @json($dailySalesChart['data'] ?? [])
                    }],
                    chart: {
                        type: 'line',
                        height: 400
                    },
                    xaxis: {
                        categories: @json($dailySalesChart['labels'] ?? [])
                    },
                    colors: ['#3b82f6']
                };
                new ApexCharts(document.querySelector("#dailySalesChart"), dailySalesOptions).render();
            }

            // Top Clients Chart
            if (document.querySelector("#topClientsChart")) {
                const topClientsOptions = {
                    series: @json($topClientsChart['data'] ?? ["2452.00"]),
                    chart: {
                        type: 'donut',
                        height: 300
                    },
                    labels: @json($topClientsChart['labels'] ?? ["Kustav Chatterjee"]),
                    colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
                };
                new ApexCharts(document.querySelector("#topClientsChart"), topClientsOptions).render();
            }

            // Category Sales Chart
            if (document.querySelector("#categorySalesChart")) {
                const categorySalesOptions = {
                    series: @json($categorySalesChart['data'] ?? []),
                    chart: {
                        type: 'pie',
                        height: 300
                    },
                    labels: @json($categorySalesChart['labels'] ?? []),
                    colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444']
                };
                new ApexCharts(document.querySelector("#categorySalesChart"), categorySalesOptions).render();
            }

            // Product Sales Chart
            if (document.querySelector("#productSalesChart")) {
                const productSalesOptions = {
                    series: [{
                        name: 'Sales',
                        data: @json($productSalesChart['data'] ?? [])
                    }],
                    chart: {
                        type: 'bar',
                        height: 400
                    },
                    xaxis: {
                        categories: @json($productSalesChart['labels'] ?? [])
                    },
                    colors: ['#10b981']
                };
                new ApexCharts(document.querySelector("#productSalesChart"), productSalesOptions).render();
            }
        }
    </script>
</div>