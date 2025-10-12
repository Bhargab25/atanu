{{-- Expense Report --}}
<div class="space-y-6">
    {{-- Expense Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-mary-card class="bg-gradient-to-r from-red-50 to-red-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-currency-dollar" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Amount</div>
                    <div class="text-xl font-bold text-red-600">₹{{ number_format($reportData['total_amount'], 2) }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-document-text" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total Expenses</div>
                    <div class="text-xl font-bold text-blue-600">{{ $reportData['total_count'] }}</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-calculator" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Average Amount</div>
                    <div class="text-xl font-bold text-green-600">
                        ₹{{ $reportData['total_count'] > 0 ? number_format($reportData['total_amount'] / $reportData['total_count'], 2) : '0.00' }}
                    </div>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Category Breakdown --}}
    @if(!empty($reportData['category_breakdown']))
        <x-mary-card>
            <x-mary-header title="Category Breakdown" subtitle="Expense analysis by category" />
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($reportData['category_breakdown'] as $categoryName => $categoryData)
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-semibold text-gray-900 mb-2">{{ $categoryName }}</h4>
                        <div class="space-y-1">
                            <p class="text-2xl font-bold text-red-600">₹{{ number_format($categoryData['total'], 2) }}</p>
                            <p class="text-sm text-gray-600">{{ $categoryData['count'] }} transactions</p>
                            <p class="text-xs text-gray-500">Avg: ₹{{ number_format($categoryData['avg'], 2) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-mary-card>
    @endif

    {{-- Filters --}}
    <x-mary-card>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-mary-select
                label="Category"
                wire:model.live="categoryFilter"
                :options="$expenseCategories->map(fn($cat) => ['value' => $cat->id, 'label' => $cat->name])"
                option-value="value"
                option-label="label"
                placeholder="All Categories" />

            <x-mary-select
                label="Status"
                wire:model.live="statusFilter"
                :options="[
                    ['value' => '', 'label' => 'All Status'],
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'approved', 'label' => 'Approved'],
                    ['value' => 'rejected', 'label' => 'Rejected']
                ]"
                option-value="value"
                option-label="label" />

            <div class="flex items-end">
                <x-mary-button label="Clear Filters" class="btn-outline w-full" 
                    wire:click="$set('categoryFilter', ''); $set('statusFilter', '')" />
            </div>
        </div>
    </x-mary-card>

    {{-- Detailed Expense List --}}
    <x-mary-card>
        <x-mary-header title="Expense Details" subtitle="Complete expense transactions" />
        
        @if($reportData['expenses']->count() > 0)
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['expenses'] as $expense)
                            <tr>
                                <td>
                                    <x-mary-badge :value="$expense->expense_ref" class="badge-outline badge-sm font-mono" />
                                </td>
                                <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                                <td class="max-w-xs">
                                    <div class="font-medium">{{ $expense->expense_title }}</div>
                                    @if($expense->description)
                                        <div class="text-sm text-gray-500 truncate">{{ $expense->description }}</div>
                                    @endif
                                </td>
                                <td>
                                    <x-mary-badge :value="$expense->category->name" class="badge-outline badge-sm" />
                                </td>
                                <td class="font-mono font-semibold text-red-600">₹{{ number_format($expense->amount, 2) }}</td>
                                <td>
                                    <x-mary-badge :value="$expense->payment_method_label" class="badge-info badge-sm" />
                                </td>
                                <td>
                                    <x-mary-badge :value="ucfirst($expense->approval_status)" :class="$expense->status_badge_class" />
                                </td>
                                <td class="text-sm">{{ $expense->creator->name ?? 'System' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <x-mary-icon name="o-receipt-percent" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <h3 class="text-lg font-medium text-gray-600 mb-2">No Expenses Found</h3>
                <p class="text-gray-500">No expenses found for the selected criteria.</p>
            </div>
        @endif
    </x-mary-card>
</div>
