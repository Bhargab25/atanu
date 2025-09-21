{{-- resources/views/livewire/expense-management.blade.php --}}
<div>
    <x-mary-header title="Expense Management" subtitle="Track and manage business expenses" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                <x-mary-button icon="o-plus" label="Add Category" class="btn-secondary"
                    @click="$wire.openCategoryModal()" />
                <x-mary-button icon="o-plus" label="Add Expense" class="btn-primary"
                    @click="$wire.openExpenseModal()" />
            </div>
        </x-slot:middle>
    </x-mary-header>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Total Expenses</p>
                    <p class="text-3xl font-bold">{{ number_format($totalExpenses) }}</p>
                </div>
                <x-mary-icon name="o-receipt-percent" class="w-12 h-12 text-blue-200" />
            </div>
        </div>

        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Total Amount</p>
                    <p class="text-3xl font-bold">₹{{ number_format($totalAmount) }}</p>
                </div>
                <x-mary-icon name="o-currency-rupee" class="w-12 h-12 text-green-200" />
            </div>
        </div>

        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm">Pending Approval</p>
                    <p class="text-3xl font-bold">₹{{ number_format($pendingAmount) }}</p>
                </div>
                <x-mary-icon name="o-clock" class="w-12 h-12 text-orange-200" />
            </div>
        </div>

        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Reimbursable</p>
                    <p class="text-3xl font-bold">₹{{ number_format($reimbursableAmount) }}</p>
                </div>
                <x-mary-icon name="o-arrow-uturn-left" class="w-12 h-12 text-purple-200" />
            </div>
        </div>
    </div>

    {{-- Date Range Filter --}}
    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-mary-input label="From Date" wire:model.live="dateFrom" type="date" />
            <x-mary-input label="To Date" wire:model.live="dateTo" type="date" />
        </div>
    </x-mary-card>

    {{-- Tabs Navigation --}}
    <div class="mb-6">
        <div class="bg-gray-100 rounded-xl p-1 inline-flex">
            <button wire:click="switchTab('expenses')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'expenses' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Expenses
            </button>
            <button wire:click="switchTab('categories')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'categories' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Categories
            </button>
        </div>
    </div>

    {{-- Tab Content --}}
    @if($activeTab === 'expenses')
    {{-- Filters --}}
    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <x-mary-input label="Search" wire:model.live.debounce.300ms="search"
                placeholder="Title, ref, description..." icon="o-magnifying-glass" />

            <x-mary-select label="Category" wire:model.live="categoryFilter"
                :options="$expenseCategories" option-value="id" option-label="name"
                placeholder="All Categories" />

            <x-mary-select label="Status" wire:model.live="statusFilter"
                :options="[
                        ['value' => '', 'label' => 'All Status'],
                        ['value' => 'pending', 'label' => 'Pending'],
                        ['value' => 'approved', 'label' => 'Approved'],
                        ['value' => 'rejected', 'label' => 'Rejected']
                    ]" option-value="value" option-label="label" />

            <x-mary-select label="Payment Method" wire:model.live="paymentMethodFilter"
                :options="[
                        ['value' => '', 'label' => 'All Methods'],
                        ['value' => 'cash', 'label' => 'Cash'],
                        ['value' => 'bank', 'label' => 'Bank'],
                        ['value' => 'upi', 'label' => 'UPI'],
                        ['value' => 'card', 'label' => 'Card'],
                        ['value' => 'cheque', 'label' => 'Cheque']
                    ]" option-value="value" option-label="label" />

            <div class="flex items-end">
                <x-mary-select label="Per Page" wire:model.live="perPage"
                    :options="[
                            ['value' => 10, 'label' => '10'],
                            ['value' => 15, 'label' => '15'],
                            ['value' => 25, 'label' => '25'],
                            ['value' => 50, 'label' => '50']
                        ]" option-value="value" option-label="label" />
            </div>
        </div>
    </x-mary-card>

    {{-- Expenses Table --}}
    <x-mary-card>
        <x-mary-table
            :headers="[
                    ['label' => '#', 'key' => 'sl_no'],
                    ['label' => 'Ref No.', 'key' => 'expense_ref'],
                    ['label' => 'Title', 'key' => 'title'],
                    ['label' => 'Category', 'key' => 'category'],
                    ['label' => 'Amount', 'key' => 'amount'],
                    ['label' => 'Date', 'key' => 'date'],
                    ['label' => 'Payment', 'key' => 'payment'],
                    ['label' => 'Status', 'key' => 'status'],
                    ['label' => 'Actions', 'key' => 'actions']
                ]"
            :rows="$expenses"
            striped
            with-pagination>

            @scope('cell_sl_no', $expense)
            <span class="font-medium">{{ $loop->iteration }}</span>
            @endscope

            @scope('cell_expense_ref', $expense)
            <div class="font-medium text-primary">{{ $expense->expense_ref }}</div>
            @if($expense->is_reimbursable)
            <span class="badge badge-info badge-xs">Reimbursable</span>
            @endif
            @endscope

            @scope('cell_title', $expense)
            <div class="font-medium">{{ $expense->expense_title }}</div>
            @if($expense->description)
            <div class="text-sm text-gray-500 truncate max-w-xs">{{ $expense->description }}</div>
            @endif
            @endscope

            @scope('cell_category', $expense)
            <span class="badge badge-outline">{{ $expense->category->name }}</span>
            @endscope

            @scope('cell_amount', $expense)
            <div class="text-right font-bold">₹{{ number_format($expense->amount, 2) }}</div>
            @endscope

            @scope('cell_date', $expense)
            {{ $expense->expense_date->format('d/m/Y') }}
            @endscope

            @scope('cell_payment', $expense)
            <div class="text-sm">
                <div class="font-medium">{{ $expense->payment_method_label }}</div>
                @if($expense->reference_number)
                <div class="text-gray-500">{{ $expense->reference_number }}</div>
                @endif
            </div>
            @endscope

            @scope('cell_status', $expense)
            <x-mary-badge :value="ucfirst($expense->approval_status)"
                :class="$expense->status_badge_class" />
            @endscope

            @scope('cell_actions', $expense)
            <div class="flex gap-1">
                <x-mary-button icon="o-eye" class="btn-circle btn-ghost btn-xs"
                    tooltip="View" @click="$wire.viewExpense({{ $expense->id }})" />

                <x-mary-button icon="o-pencil" class="btn-circle btn-ghost btn-xs text-primary"
                    tooltip="Edit" @click="$wire.editExpense({{ $expense->id }})" />

                @if($expense->approval_status === 'pending')
                <x-mary-button icon="o-check" class="btn-circle btn-ghost btn-xs text-success"
                    tooltip="Approve" @click="$wire.approveExpense({{ $expense->id }})" />

                <x-mary-button icon="o-x-mark" class="btn-circle btn-ghost btn-xs text-error"
                    tooltip="Reject" @click="$wire.rejectExpense({{ $expense->id }})" />
                @endif

                <x-mary-button icon="o-trash" class="btn-circle btn-ghost btn-xs text-error"
                    tooltip="Delete" @click="$wire.deleteExpense({{ $expense->id }})" />
            </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    @else
    {{-- Categories Table --}}
    <x-mary-card>
        <x-mary-table
            :headers="[
                    ['label' => '#', 'key' => 'sl_no'],
                    ['label' => 'Name', 'key' => 'name'],
                    ['label' => 'Description', 'key' => 'description'],
                    ['label' => 'Expenses Count', 'key' => 'count'],
                    ['label' => 'Status', 'key' => 'status'],
                    ['label' => 'Actions', 'key' => 'actions']
                ]"
            :rows="$categories"
            striped
            with-pagination>

            @scope('cell_sl_no', $category)
            <span class="font-medium">{{ $loop->iteration }}</span>
            @endscope

            @scope('cell_name', $category)
            <div class="font-medium">{{ $category->name }}</div>
            @endscope

            @scope('cell_description', $category)
            <div class="text-sm text-gray-600">{{ $category->description ?: 'N/A' }}</div>
            @endscope

            @scope('cell_count', $category)
            <span class="badge badge-info">{{ $category->expenses_count }}</span>
            @endscope

            @scope('cell_status', $category)
            <x-mary-badge :value="$category->is_active ? 'Active' : 'Inactive'"
                :class="$category->is_active ? 'badge-success' : 'badge-error'" />
            @endscope

            @scope('cell_actions', $category)
            <div class="flex gap-1">
                <x-mary-button icon="o-pencil" class="btn-circle btn-ghost btn-xs text-primary"
                    tooltip="Edit" />
            </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
    @endif

    {{-- Add Expense Modal --}}
    <x-mary-modal wire:model="showExpenseModal"
        :title="$editingExpense ? 'Edit Expense' : 'Add New Expense'"
        box-class="backdrop-blur max-w-4xl">

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Expense Title *" wire:model="expenseTitle"
                    placeholder="Enter expense title"
                    :error="$errors->first('expenseTitle')" />

                <x-mary-select label="Category *" wire:model="categoryId"
                    :options="$expenseCategories" option-value="id" option-label="name"
                    placeholder="Select category"
                    :error="$errors->first('categoryId')" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Amount *" wire:model="amount" type="number"
                    step="0.01" prefix="₹" placeholder="0.00"
                    :error="$errors->first('amount')" />

                <x-mary-input label="Expense Date *" wire:model="expenseDate"
                    type="date" :error="$errors->first('expenseDate')" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-select label="Payment Method *" wire:model="paymentMethod"
                    :options="[
                        ['value' => 'cash', 'label' => 'Cash'],
                        ['value' => 'bank', 'label' => 'Bank Transfer'],
                        ['value' => 'upi', 'label' => 'UPI'],
                        ['value' => 'card', 'label' => 'Card'],
                        ['value' => 'cheque', 'label' => 'Cheque']
                    ]" option-value="value" option-label="label"
                    :error="$errors->first('paymentMethod')" />

                <x-mary-input label="Reference Number" wire:model="referenceNumber"
                    placeholder="Transaction ID / Cheque No." />
            </div>

            <x-mary-textarea label="Description" wire:model="description"
                placeholder="Enter expense description..." rows="3" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <x-mary-checkbox label="Business Expense" wire:model="isBusinessExpense" />
                    <x-mary-checkbox label="Reimbursable" wire:model="isReimbursable" />
                </div>

                @if($isReimbursable)
                <x-mary-input label="Reimbursed To" wire:model="reimbursedTo"
                    placeholder="Employee/Person name" />
                @endif
            </div>

            <x-mary-file label="Receipt" wire:model="receipt"
                hint="Upload receipt (PDF, JPG, PNG - Max 5MB)"
                accept="image/*,application/pdf" />
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.closeExpenseModal()" />
            <x-mary-button label="{{ $editingExpense ? 'Update' : 'Save' }} Expense"
                class="btn-primary" spinner="saveExpense" @click="$wire.saveExpense()" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- Add Category Modal --}}
    <x-mary-modal wire:model="showCategoryModal" title="Add New Category"
        box-class="backdrop-blur max-w-lg">

        <div class="space-y-4">
            <x-mary-input label="Category Name *" wire:model="categoryName"
                placeholder="Enter category name" />

            <x-mary-textarea label="Description" wire:model="categoryDescription"
                placeholder="Enter category description..." rows="3" />
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.closeCategoryModal()" />
            <x-mary-button label="Save Category" class="btn-primary"
                spinner="saveCategory" @click="$wire.saveCategory()" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- View Expense Modal --}}
    <x-mary-modal wire:model="showViewModal"
        :title="$viewingExpense ? 'Expense Details: ' . $viewingExpense->expense_ref : 'Expense Details'"
        box-class="backdrop-blur max-w-3xl">

        @if($viewingExpense)
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Title</label>
                    <p class="font-medium">{{ $viewingExpense->expense_title }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Category</label>
                    <p>{{ $viewingExpense->category->name }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Amount</label>
                    <p class="font-bold text-lg text-primary">₹{{ number_format($viewingExpense->amount, 2) }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Date</label>
                    <p>{{ $viewingExpense->expense_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Payment Method</label>
                    <p>{{ $viewingExpense->payment_method_label }}</p>
                </div>
            </div>

            @if($viewingExpense->description)
            <div>
                <label class="text-sm font-medium text-gray-600">Description</label>
                <p class="text-gray-700">{{ $viewingExpense->description }}</p>
            </div>
            @endif

            @if($viewingExpense->receipt_path)
            <div>
                <label class="text-sm font-medium text-gray-600">Receipt</label>
                <div class="mt-2">
                    <a href="{{ Storage::url($viewingExpense->receipt_path) }}"
                        target="_blank" class="btn btn-outline btn-sm">
                        <x-mary-icon name="o-document" class="w-4 h-4 mr-2" />
                        View Receipt
                    </a>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Created By</label>
                    <p>{{ $viewingExpense->creator->name }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Status</label>
                    <x-mary-badge :value="ucfirst($viewingExpense->approval_status)"
                        :class="$viewingExpense->status_badge_class" />
                </div>
            </div>
        </div>
        @endif

        <x-slot:actions>
            <x-mary-button label="Close" @click="$wire.closeViewModal()" />
        </x-slot:actions>
    </x-mary-modal>
</div>