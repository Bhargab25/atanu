{{-- resources/views/livewire/expense-management.blade.php --}}
<div>
    <x-mary-header title="Expense Management" subtitle="Track and manage business expenses" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                {{-- Company Selection --}}
                @if(count($companyOptions) > 1)
                <x-mary-select
                    wire:model.live="selectedCompanyId"
                    :options="$companyOptions"
                    option-value="id"
                    option-label="name"
                    placeholder="Select Company..."
                    icon="o-building-office"
                    class="w-48" />
                @endif

                <x-mary-button icon="o-plus" label="Add Category" class="btn-secondary"
                    @click="$wire.openCategoryModal()" />
                <x-mary-button icon="o-plus" label="Add Expense" class="btn-primary"
                    @click="$wire.openExpenseModal()" />
            </div>
        </x-slot:middle>
    </x-mary-header>

    {{-- Company Info Banner (when single company or company selected) --}}
    @if($selectedCompanyId)
    @php
    $selectedCompany = collect($companyOptions)->firstWhere('id', $selectedCompanyId);
    @endphp
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <x-mary-icon name="o-building-office" class="w-6 h-6 text-blue-600" />
            </div>
            <div>
                <h3 class="font-semibold text-blue-900">{{ $selectedCompany['name'] ?? 'Company' }}</h3>
                <p class="text-sm text-blue-600">Viewing expenses for this company</p>
            </div>
        </div>
    </div>
    @endif


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

    @if($selectedCompanyId && count($expenseLedgerSummary) > 0)
    <x-mary-card class="mb-6">
        <x-mary-header title="Expense Ledger Summary" subtitle="Category-wise expense totals" />
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @foreach($expenseLedgerSummary as $summary)
            <div class="p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                        <x-mary-icon name="o-receipt-percent" class="w-5 h-5 text-red-600" />
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 text-sm">{{ $summary['name'] }}</h4>
                        <p class="text-xs text-gray-500">{{ $summary['category']->name ?? 'N/A' }}</p>
                    </div>
                </div>
                <p class="text-lg font-bold text-red-600">
                    ₹{{ number_format($summary['balance'], 2) }}
                    <span class="text-sm font-normal">Total</span>
                </p>
            </div>
            @endforeach
        </div>
    </x-mary-card>
    @endif

    {{-- Add Cash & Bank Summary --}}
    @if($selectedCompanyId)
    @php
    $cashBankSummary = \App\Models\AccountLedger::getCashAndBankSummary($selectedCompanyId);
    @endphp
    @if(count($cashBankSummary) > 0)
    <x-mary-card class="mb-6">
        <x-mary-header title="Cash & Bank Summary" subtitle="Available balances" />
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @foreach($cashBankSummary as $summary)
            <div class="p-4 bg-green-50 rounded-lg">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <x-mary-icon name="{{ $summary['type'] === 'cash' ? 'o-banknotes' : 'o-building-library' }}" class="w-5 h-5 text-green-600" />
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 text-sm">{{ $summary['name'] }}</h4>
                        <p class="text-xs text-gray-500">{{ ucfirst($summary['type']) }}</p>
                    </div>
                </div>
                <p class="text-lg font-bold {{ $summary['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    ₹{{ number_format(abs($summary['balance']), 2) }}
                    <span class="text-sm font-normal">{{ $summary['balance'] >= 0 ? 'Available' : 'Overdrawn' }}</span>
                </p>
            </div>
            @endforeach
        </div>
    </x-mary-card>
    @endif
    @endif

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
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <x-mary-input label="Search" wire:model.live.debounce.300ms="search"
                placeholder="Title, ref, description..." icon="o-magnifying-glass" />

            {{-- Company Filter (only show if multiple companies) --}}
            @if(count($companyOptions) > 1)
            <x-mary-select label="Company" wire:model.live="companyFilter"
                :options="$companyOptions" option-value="id" option-label="name"
                placeholder="All Companies" />
            @endif

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
                        ['label' => 'Company', 'key' => 'company'],
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

            @scope('cell_company', $expense)
            <div class="text-sm">
                <div class="font-medium text-blue-600">{{ $expense->company->name ?? 'N/A' }}</div>
                @if($expense->company && $expense->company->legal_name)
                <div class="text-xs text-gray-500">{{ $expense->company->legal_name }}</div>
                @endif
            </div>
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
                ['label' => 'Company', 'key' => 'company'],
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

            @scope('cell_company', $category)
            <div class="text-sm">
                <div class="font-medium text-blue-600">{{ $category->company->name ?? 'N/A' }}</div>
                @if($category->company && $category->company->legal_name)
                <div class="text-xs text-gray-500">{{ $category->company->legal_name }}</div>
                @endif
            </div>
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
                <x-mary-button icon="o-trash" class="btn-circle btn-ghost btn-xs text-error"
                    tooltip="Delete" />
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
            {{-- Company Selection (New Section) --}}
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="text-lg font-semibold text-blue-800 mb-3">Company Assignment</h3>
                <x-mary-select label="Company *" wire:model="companyProfileId"
                    :options="$companyOptions" option-value="id" option-label="name"
                    placeholder="Select company..."
                    icon="o-building-office"
                    hint="Select which company this expense belongs to"
                    :error="$errors->first('companyProfileId')" />
            </div>

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
            {{-- Company Info Display --}}
            @if($selectedCompanyId)
            @php
            $selectedCompany = collect($companyOptions)->firstWhere('id', $selectedCompanyId);
            @endphp
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-building-office" class="w-5 h-5 text-blue-600" />
                    <div>
                        <p class="text-sm font-medium text-blue-900">{{ $selectedCompany['name'] ?? 'Company' }}</p>
                        <p class="text-xs text-blue-600">Category will be created for this company</p>
                    </div>
                </div>
            </div>
            @else
            <div class="p-3 bg-orange-50 border border-orange-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 text-orange-600" />
                    <p class="text-sm text-orange-800">Please select a company first to create categories</p>
                </div>
            </div>
            @endif

            <x-mary-input label="Category Name *" wire:model="categoryName"
                placeholder="Enter category name"
                :disabled="!$selectedCompanyId" />

            <x-mary-textarea label="Description" wire:model="categoryDescription"
                placeholder="Enter category description..." rows="3"
                :disabled="!$selectedCompanyId" />
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.closeCategoryModal()" />
            <x-mary-button label="Save Category" class="btn-primary"
                spinner="saveCategory" @click="$wire.saveCategory()"
                :disabled="!$selectedCompanyId" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- View Expense Modal --}}
    <x-mary-modal wire:model="showViewModal"
        :title="$viewingExpense ? 'Expense Details: ' . $viewingExpense->expense_ref : 'Expense Details'"
        box-class="backdrop-blur max-w-3xl">

        @if($viewingExpense)
        <div class="space-y-6">
            {{-- Company Info --}}
            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <x-mary-icon name="o-building-office" class="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Company</label>
                        <p class="font-medium">{{ $viewingExpense->company->name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

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
        {{-- Add Ledger Information Section --}}
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center gap-3 mb-3">
                <x-mary-icon name="o-book-open" class="w-5 h-5 text-blue-600" />
                <h4 class="font-semibold text-blue-800">Ledger Information</h4>
            </div>

            @php
            $ledgerTransactions = \App\Models\LedgerTransaction::where('reference', $viewingExpense->expense_ref)
            ->where('company_profile_id', $viewingExpense->company_profile_id)
            ->with('ledger')
            ->get();
            @endphp

            @if($ledgerTransactions->count() > 0)
            <div class="space-y-2">
                @foreach($ledgerTransactions as $transaction)
                <div class="flex justify-between items-center p-2 bg-white rounded">
                    <div>
                        <p class="font-medium text-sm">{{ $transaction->ledger->ledger_name }}</p>
                        <p class="text-xs text-gray-600">{{ $transaction->description }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold {{ $transaction->debit_amount > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $transaction->debit_amount > 0 ? 'Dr.' : 'Cr.' }} ₹{{ number_format($transaction->amount, 2) }}
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-blue-700 text-sm">No ledger entries found for this expense.</p>
            @endif
        </div>
        @endif

        <x-slot:actions>
            @if($viewingExpense && $viewingExpense->approval_status === 'pending')
            <x-mary-button label="Approve" class="btn-success"
                @click="$wire.approveExpense({{ $viewingExpense->id }})" />
            <x-mary-button label="Reject" class="btn-error"
                @click="$wire.rejectExpense({{ $viewingExpense->id }})" />
            @endif
            <x-mary-button label="Close" @click="$wire.closeViewModal()" />
        </x-slot:actions>
    </x-mary-modal>
</div>