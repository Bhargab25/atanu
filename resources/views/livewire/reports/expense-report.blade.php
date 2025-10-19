<div class="space-y-6">
    {{-- Expense Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-mary-card class="bg-gradient-to-r from-red-50 to-red-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Total Expenses</div>
                <div class="text-xl font-bold text-red-600">₹{{ number_format($reportData['total_amount'], 2) }}</div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Total Count</div>
                <div class="text-xl font-bold text-blue-600">{{ $reportData['total_count'] }}</div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Average Expense</div>
                <div class="text-xl font-bold text-green-600">₹{{ number_format($reportData['average_expense'], 2) }}</div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-purple-50 to-purple-100">
            <div class="text-center">
                <div class="text-xs text-gray-600 uppercase tracking-wide">Categories</div>
                <div class="text-xl font-bold text-purple-600">{{ count($reportData['category_breakdown']) }}</div>
            </div>
        </x-mary-card>
    </div>

    {{-- Filters --}}
    <x-mary-card>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-mary-select
                label="Category"
                wire:model.live="categoryFilter"
                :options="$expenseCategories"
                option-value="id"
                option-label="name"
                placeholder="All Categories" />

            <x-mary-select
                label="Status"
                wire:model.live="statusFilter"
                :options="[
                    ['id' => 'pending', 'name' => 'Pending'],
                    ['id' => 'approved', 'name' => 'Approved'],
                    ['id' => 'rejected', 'name' => 'Rejected']
                ]"
                option-value="id"
                option-label="name"
                placeholder="All Status" />

            <div class="flex items-end">
                <x-mary-button label="Clear Filters" class="btn-outline w-full" 
                    wire:click="$set('categoryFilter', ''); $set('statusFilter', '')" />
            </div>
        </div>
    </x-mary-card>

    {{-- Category Breakdown --}}
    @if(!empty($reportData['category_breakdown']))
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-chart-pie" class="w-5 h-5 text-purple-600" />
                    Expenses by Category
                </div>
            </x-slot:title>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Total Amount</th>
                            <th>Count</th>
                            <th>Average</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['category_breakdown'] as $category => $data)
                            <tr>
                                <td class="font-medium">{{ $category }}</td>
                                <td class="font-mono">₹{{ number_format($data['total'], 2) }}</td>
                                <td>{{ $data['count'] }}</td>
                                <td class="font-mono">₹{{ number_format($data['avg'], 2) }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="w-full bg-gray-200 rounded-full h-2 max-w-24">
                                            <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $data['percentage'] }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium">{{ $data['percentage'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    @endif

    {{-- Monthly Breakdown --}}
    @if(!empty($reportData['monthly_breakdown']))
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-calendar-days" class="w-5 h-5 text-blue-600" />
                    Monthly Breakdown
                </div>
            </x-slot:title>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Total Amount</th>
                            <th>Count</th>
                            <th>Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['monthly_breakdown'] as $month => $data)
                            <tr>
                                <td class="font-medium">{{ \Carbon\Carbon::parse($month)->format('M Y') }}</td>
                                <td class="font-mono">₹{{ number_format($data['total'], 2) }}</td>
                                <td>{{ $data['count'] }}</td>
                                <td>
                                    <div class="w-full bg-gray-200 rounded-full h-2 max-w-24">
                                        @php
                                            $maxAmount = collect($reportData['monthly_breakdown'])->max('total');
                                            $percentage = $maxAmount > 0 ? ($data['total'] / $maxAmount) * 100 : 0;
                                        @endphp
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    @endif

    {{-- Detailed Expenses List --}}
    @if(!empty($reportData['expenses']) && $reportData['expenses']->count() > 0)
        <x-mary-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-list-bullet" class="w-5 h-5 text-gray-600" />
                    Detailed Expenses
                </div>
            </x-slot:title>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['expenses']->take(50) as $expense)
                            <tr>
                                <td>{{ $expense->expense_date->format('M d, Y') }}</td>
                                <td class="font-medium max-w-xs truncate">{{ $expense->expense_title }}</td>
                                <td>
                                    <x-mary-badge :value="$expense->category->name" 
                                        class="badge-info badge-sm" />
                                </td>
                                <td class="font-mono">₹{{ number_format($expense->amount, 2) }}</td>
                                <td>
                                    <x-mary-badge 
                                        :value="ucfirst($expense->approval_status)"
                                        :class="match($expense->approval_status) {
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-error',
                                            'pending' => 'badge-warning',
                                            default => 'badge-outline'
                                        } . ' badge-sm'" />
                                </td>
                                <td class="text-sm">{{ $expense->creator->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    @endif
</div>
