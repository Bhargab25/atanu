{{-- resources/views/livewire/sales-analytics.blade.php --}}
<div>
    <x-mary-header title="Sales Analytics" subtitle="Advanced sales performance insights" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                <x-mary-button icon="o-arrow-down-tray" label="Export Performance" class="btn-success"
                    @click="$wire.exportAnalytics('performance')" />
                <x-mary-button icon="o-arrow-down-tray" label="Export Clients" class="btn-primary"
                    @click="$wire.exportAnalytics('clients')" />
            </div>
        </x-slot:middle>
    </x-mary-header>

    {{-- Performance Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm">Sales Growth</p>
                    <p class="text-3xl font-bold">{{ number_format($salesGrowth, 1) }}%</p>
                    <p class="text-emerald-200 text-xs mt-1">vs previous period</p>
                </div>
                <x-mary-icon name="o-arrow-trending-{{ $salesGrowth >= 0 ? 'up' : 'down' }}"
                    class="w-12 h-12 text-emerald-200" />
            </div>
        </div>

        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Avg Order Growth</p>
                    <p class="text-3xl font-bold">{{ number_format($avgOrderGrowth, 1) }}%</p>
                    <p class="text-blue-200 text-xs mt-1">₹{{ number_format($avgOrderValue) }} current</p>
                </div>
                <x-mary-icon name="o-chart-bar-square" class="w-12 h-12 text-blue-200" />
            </div>
        </div>

        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Top Product</p>
                    <p class="text-lg font-bold truncate">{{ $topSellingProduct }}</p>
                    <p class="text-purple-200 text-xs mt-1">Best performer</p>
                </div>
                <x-mary-icon name="o-star" class="w-12 h-12 text-purple-200" />
            </div>
        </div>

        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm">Top Client</p>
                    <p class="text-lg font-bold truncate">{{ $topPayingClient }}</p>
                    <p class="text-orange-200 text-xs mt-1">Highest spender</p>
                </div>
                <x-mary-icon name="o-user-circle" class="w-12 h-12 text-orange-200" />
            </div>
        </div>
    </div>

    {{-- Date Range Filter --}}
    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-mary-input label="From Date" wire:model.live="dateFrom" type="date" />
            <x-mary-input label="To Date" wire:model.live="dateTo" type="date" />
            <div class="flex items-end">
                <div class="text-sm text-gray-600">
                    <strong>Analyzing:</strong>
                    {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} -
                    {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
                </div>
            </div>
        </div>
    </x-mary-card>

    {{-- Sales Trend Chart --}}
    <x-mary-card class="mb-6">
        <x-mary-header title="Sales Trend" subtitle="Daily sales performance" />
        <div id="salesTrendChart" style="height: 300px;"></div>
    </x-mary-card>

    {{-- Tabs Navigation --}}
    <div class="mb-6">
        <div class="bg-gray-100 rounded-xl p-1 inline-flex">
            <button wire:click="switchTab('performance')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'performance' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Product Performance
            </button>
            <button wire:click="switchTab('clients')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'clients' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Client Analytics
            </button>
            <button wire:click="switchTab('categories')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'categories' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Category Analysis
            </button>
            <button wire:click="switchTab('patterns')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'patterns' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Sales Patterns
            </button>
        </div>
    </div>

    {{-- Tab Content --}}
    @if($activeTab === 'performance')
    {{-- Product Performance Table --}}
    <x-mary-card>
        <x-mary-header title="Product Performance Analysis"
            subtitle="Top performing products by revenue and volume" />

        <div class="overflow-x-auto">
            <table class="table table-striped w-full">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-right">Total Revenue</th>
                        <th class="text-right">Qty Sold</th>
                        <th class="text-right">Total Orders</th>
                        <th class="text-right">Avg Price</th>
                        <th class="text-right">Revenue/Order</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productPerformance as $product)
                    <tr>
                        <td>
                            <div class="font-medium">{{ $product->name }}</div>
                            <div class="text-sm text-gray-500">per {{ $product->unit }}</div>
                        </td>
                        <td class="text-right font-bold">₹{{ number_format($product->total_revenue, 2) }}</td>
                        <td class="text-right">{{ number_format($product->total_quantity, 2) }}</td>
                        <td class="text-right">{{ $product->total_orders }}</td>
                        <td class="text-right">₹{{ number_format($product->avg_price, 2) }}</td>
                        <td class="text-right">₹{{ number_format($product->revenue_per_order, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-mary-card>

    @elseif($activeTab === 'clients')
    {{-- Client Analytics Table --}}
    <x-mary-card>
        <x-mary-header title="Client Performance Analysis"
            subtitle="Customer behavior and spending patterns" />

        <div class="overflow-x-auto">
            <table class="table table-striped w-full">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th class="text-right">Total Spent</th>
                        <th class="text-right">Orders</th>
                        <th class="text-right">Avg Order Value</th>
                        <th class="text-right">Days Since Last Order</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientPerformance as $client)
                    <tr>
                        <td class="font-medium">{{ $client->client_name }}</td>
                        <td class="text-right font-bold">₹{{ number_format($client->total_spent, 2) }}</td>
                        <td class="text-right">{{ $client->total_orders }}</td>
                        <td class="text-right">₹{{ number_format($client->avg_order_value, 2) }}</td>
                        <td class="text-right">{{ $client->days_since_last_order }}</td>
                        <td>
                            @if($client->days_since_last_order <= 7)
                                <span class="badge badge-success">Active</span>
                                @elseif($client->days_since_last_order <= 30)
                                    <span class="badge badge-warning">Regular</span>
                                    @else
                                    <span class="badge badge-error">Inactive</span>
                                    @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-mary-card>

    @elseif($activeTab === 'categories')
    {{-- Category Performance --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-mary-card>
            <x-mary-header title="Category Revenue" subtitle="Revenue by product category" />
            <div id="categoryRevenueChart" style="height: 400px;"></div>
        </x-mary-card>

        <x-mary-card>
            <x-mary-header title="Category Details" subtitle="Detailed category breakdown" />
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @foreach($categoryPerformance as $category)
                <div class="flex justify-between items-center p-3 bg-base-100 rounded-lg">
                    <div>
                        <div class="font-medium">{{ $category->name }}</div>
                        <div class="text-sm text-gray-500">
                            {{ $category->unique_products }} products •
                            {{ $category->unique_customers }} customers
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold">₹{{ number_format($category->total_revenue) }}</div>
                        <div class="text-sm text-gray-500">{{ number_format($category->total_quantity, 2) }} units</div>
                    </div>
                </div>
                @endforeach
            </div>
        </x-mary-card>
    </div>

    @elseif($activeTab === 'patterns')
    {{-- Sales Patterns --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-mary-card>
            <x-mary-header title="Sales by Hour" subtitle="Peak sales hours" />
            <div id="salesByHourChart" style="height: 300px;"></div>
        </x-mary-card>

        <x-mary-card>
            <x-mary-header title="Sales by Day" subtitle="Weekly sales pattern" />
            <div id="salesByDayChart" style="height: 300px;"></div>
        </x-mary-card>
    </div>
    @endif

    {{-- Charts JavaScript --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeAnalyticsCharts();
        });

        function initializeAnalyticsCharts() {
            // Sales Trend Chart
            if (document.querySelector("#salesTrendChart")) {
                const trendData = @json($salesTrend);
                const trendOptions = {
                    series: [{
                        name: 'Daily Sales',
                        data: trendData.map(item => ({
                            x: item.formatted_date,
                            y: item.total
                        }))
                    }],
                    chart: {
                        type: 'area',
                        height: 300,
                        toolbar: {
                            show: true
                        }
                    },
                    colors: ['#10b981'],
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.9,
                            stops: [0, 90, 100]
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth'
                    },
                    yaxis: {
                        labels: {
                            formatter: function(val) {
                                return "₹" + val.toLocaleString();
                            }
                        }
                    }
                };
                new ApexCharts(document.querySelector("#salesTrendChart"), trendOptions).render();
            }

            // Category Revenue Chart
            if (document.querySelector("#categoryRevenueChart")) {
                const categoryData = @json($categoryPerformance);
                const categoryOptions = {
                    series: categoryData.map(cat => parseFloat(cat.total_revenue)),
                    chart: {
                        type: 'donut',
                        height: 400
                    },
                    labels: categoryData.map(cat => cat.name),
                    colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'],
                    legend: {
                        position: 'bottom'
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val, opts) {
                            return "₹" + opts.w.globals.series[opts.seriesIndex].toLocaleString();
                        }
                    }
                };
                new ApexCharts(document.querySelector("#categoryRevenueChart"), categoryOptions).render();
            }

            // Sales by Hour Chart
            if (document.querySelector("#salesByHourChart")) {
                const hourlyData = @json($salesByHour);
                const hourlyOptions = {
                    series: [{
                        name: 'Sales',
                        data: Object.values(hourlyData)
                    }],
                    chart: {
                        type: 'bar',
                        height: 300
                    },
                    xaxis: {
                        categories: Object.keys(hourlyData).map(h => h + ':00')
                    },
                    colors: ['#06b6d4']
                };
                new ApexCharts(document.querySelector("#salesByHourChart"), hourlyOptions).render();
            }

            // Sales by Day Chart
            if (document.querySelector("#salesByDayChart")) {
                const dailyData = @json($salesByDay);
                const dailyOptions = {
                    series: [{
                        name: 'Sales',
                        data: Object.values(dailyData)
                    }],
                    chart: {
                        type: 'bar',
                        height: 300
                    },
                    xaxis: {
                        categories: Object.keys(dailyData)
                    },
                    colors: ['#8b5cf6']
                };
                new ApexCharts(document.querySelector("#salesByDayChart"), dailyOptions).render();
            }
        }
    </script>
</div>