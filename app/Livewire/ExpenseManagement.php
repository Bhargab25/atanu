<?php
// app/Livewire/ExpenseManagement.php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\CompanyProfile;
use App\Models\AccountLedger;
use App\Models\LedgerTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ExpenseManagement extends Component
{
    use WithPagination, WithFileUploads, Toast;

    // Tab management
    public $activeTab = 'expenses';

    // Modal properties
    public $showExpenseModal = false;
    public $showCategoryModal = false;
    public $showViewModal = false;
    public $editingExpense = null;
    public $viewingExpense = null;

    // Company management
    public $companyProfileId;
    public $selectedCompanyId = null;

    // Expense form properties
    public $expenseTitle = '';
    public $categoryId = '';
    public $amount = '';
    public $description = '';
    public $expenseDate;
    public $paymentMethod = 'cash';
    public $referenceNumber = '';
    public $isBusinessExpense = true;
    public $isReimbursable = false;
    public $reimbursedTo = '';
    public $receipt;

    // Category form properties
    public $categoryName = '';
    public $categoryDescription = '';

    // Filters and search
    public $search = '';
    public $categoryFilter = '';
    public $statusFilter = '';
    public $paymentMethodFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 15;
    public $companyFilter = [];
    public $appliedCompanyFilter = [];

    // Statistics
    public $totalExpenses = 0;
    public $totalAmount = 0;
    public $pendingAmount = 0;
    public $reimbursableAmount = 0;

    // Filter options
    public $companyOptions = [];
    public $statusOptions = [
        ['id' => 'pending', 'name' => 'Pending'],
        ['id' => 'approved', 'name' => 'Approved'],
        ['id' => 'rejected', 'name' => 'Rejected'],
    ];

    public $paymentMethodOptions = [
        ['id' => 'cash', 'name' => 'Cash'],
        ['id' => 'bank', 'name' => 'Bank Transfer'],
        ['id' => 'upi', 'name' => 'UPI'],
        ['id' => 'card', 'name' => 'Card'],
        ['id' => 'cheque', 'name' => 'Cheque'],
    ];

    protected $rules = [
        'companyProfileId' => 'required|exists:company_profiles,id',
        'expenseTitle' => 'required|string|max:255',
        'categoryId' => 'required|exists:expense_categories,id',
        'amount' => 'required|numeric|min:0.01',
        'expenseDate' => 'required|date',
        'paymentMethod' => 'required|in:cash,bank,upi,card,cheque',
        'description' => 'nullable|string',
        'referenceNumber' => 'nullable|string|max:255',
        'isBusinessExpense' => 'boolean',
        'isReimbursable' => 'boolean',
        'reimbursedTo' => 'nullable|string|max:255',
        'receipt' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
    ];

    protected $messages = [
        'companyProfileId.required' => 'Please select a company',
        'expenseTitle.required' => 'Expense title is required',
        'categoryId.required' => 'Please select a category',
        'amount.required' => 'Amount is required',
        'amount.min' => 'Amount must be greater than 0',
        'expenseDate.required' => 'Expense date is required',
    ];

    public function getCompaniesProperty()
    {
        return CompanyProfile::active()->get();
    }

    public function mount()
    {
        $this->loadCompanyOptions();
        $this->expenseDate = now()->format('Y-m-d');
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');

        // Set default company if only one exists
        if (count($this->companyOptions) === 1) {
            $this->selectedCompanyId = $this->companyOptions[0]['id'];
            $this->companyProfileId = $this->companyOptions[0]['id'];
        }

        $this->calculateStats();
    }

    private function loadCompanyOptions()
    {
        $this->companyOptions = CompanyProfile::active()
            ->get()
            ->map(function ($company) {
                return ['id' => $company->id, 'name' => $company->name];
            })
            ->toArray();
    }

    private function loadCompanies()
    {
        $this->companyOptions = CompanyProfile::active()
            ->get()
            ->map(function ($company) {
                return ['id' => $company->id, 'name' => $company->name];
            })
            ->toArray();
    }

    public function updatedSelectedCompanyId()
    {
        $this->companyProfileId = $this->selectedCompanyId;
        $this->calculateStats();
        $this->resetPage();
    }


    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function openExpenseModal()
    {
        $this->showExpenseModal = true;
        $this->resetExpenseForm();
    }

    public function closeExpenseModal()
    {
        $this->showExpenseModal = false;
        $this->editingExpense = null;
        $this->resetValidation();
        $this->resetExpenseForm();
    }

    public function resetExpenseForm()
    {
        $this->expenseTitle = '';
        $this->categoryId = '';
        $this->amount = '';
        $this->description = '';
        $this->expenseDate = now()->format('Y-m-d');
        $this->paymentMethod = 'cash';
        $this->referenceNumber = '';
        $this->isBusinessExpense = true;
        $this->isReimbursable = false;
        $this->reimbursedTo = '';
        $this->receipt = null;

        // Set default company if only one exists
        if (count($this->companyOptions) === 1) {
            $this->companyProfileId = $this->companyOptions[0]['id'];
        }
    }

    public function saveExpense()
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $receiptPath = null;
                if ($this->receipt) {
                    $receiptPath = $this->receipt->store('expenses/receipts', 'public');
                }

                $data = [
                    'company_profile_id' => $this->companyProfileId,
                    'expense_title' => $this->expenseTitle,
                    'category_id' => $this->categoryId,
                    'amount' => $this->amount,
                    'description' => $this->description,
                    'expense_date' => $this->expenseDate,
                    'payment_method' => $this->paymentMethod,
                    'reference_number' => $this->referenceNumber,
                    'is_business_expense' => $this->isBusinessExpense,
                    'is_reimbursable' => $this->isReimbursable,
                    'reimbursed_to' => $this->reimbursedTo,
                    'receipt_path' => $receiptPath,
                    'created_by' => auth()->id(),
                ];

                if ($this->editingExpense) {
                    // Update existing expense (no ledger changes for edits)
                    $this->editingExpense->update($data);
                    $this->success('Expense updated successfully!');
                } else {
                    // Create new expense
                    $expense = Expense::create($data);

                    // Create ledger transactions for the expense
                    $this->createExpenseLedgerTransactions($expense);

                    $this->success('Expense added and recorded in ledger successfully!');
                }

                $this->closeExpenseModal();
                $this->calculateStats();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '23000') {
                Log::error('Database constraint violation in expense save: ' . $e->getMessage());
                $this->error('Error saving expense', 'A duplicate reference number was generated. Please try again.');
            } else {
                Log::error('Database error in expense save: ' . $e->getMessage());
                $this->error('Database error occurred while saving expense.');
            }
        } catch (\Exception $e) {
            Log::error('Error saving expense: ' . $e->getMessage());
            $this->error('Error saving expense: ' . $e->getMessage());
        }
    }

    private function createExpenseLedgerTransactions($expense)
    {
        // Get or create expense category ledger
        $categoryLedger = AccountLedger::getOrCreateExpenseLedger(
            $expense->company_profile_id,
            $expense->category
        );

        // Create expense ledger transaction (Debit - expense increases)
        LedgerTransaction::create([
            'company_profile_id' => $expense->company_profile_id,
            'ledger_id' => $categoryLedger->id,
            'date' => $expense->expense_date,
            'type' => 'expense',
            'description' => $expense->expense_title . ' - ' . $expense->category->name,
            'debit_amount' => $expense->amount, // Expense increases (debit)
            'credit_amount' => 0,
            'reference' => $expense->expense_ref,
        ]);

        // Get cash/bank ledger based on payment method
        if (in_array($expense->payment_method, ['cash'])) {
            $paymentLedger = AccountLedger::getOrCreateCashLedger($expense->company_profile_id);
        } else {
            $paymentLedger = AccountLedger::getOrCreateBankLedger($expense->company_profile_id, $expense->payment_method);
        }

        // Create payment ledger transaction (Credit - cash/bank decreases)
        LedgerTransaction::create([
            'company_profile_id' => $expense->company_profile_id,
            'ledger_id' => $paymentLedger->id,
            'date' => $expense->expense_date,
            'type' => 'expense',
            'description' => "Payment for {$expense->expense_title} via {$expense->payment_method_label}",
            'debit_amount' => 0,
            'credit_amount' => $expense->amount, // Cash/Bank decreases (credit)
            'reference' => $expense->expense_ref,
        ]);
    }



    public function editExpense($expenseId)
    {
        $this->editingExpense = Expense::with('company')->find($expenseId);

        if ($this->editingExpense) {
            $this->companyProfileId = $this->editingExpense->company_profile_id;
            $this->expenseTitle = $this->editingExpense->expense_title;
            $this->categoryId = $this->editingExpense->category_id;
            $this->amount = $this->editingExpense->amount;
            $this->description = $this->editingExpense->description;
            $this->expenseDate = $this->editingExpense->expense_date->format('Y-m-d');
            $this->paymentMethod = $this->editingExpense->payment_method;
            $this->referenceNumber = $this->editingExpense->reference_number;
            $this->isBusinessExpense = $this->editingExpense->is_business_expense;
            $this->isReimbursable = $this->editingExpense->is_reimbursable;
            $this->reimbursedTo = $this->editingExpense->reimbursed_to;

            $this->showExpenseModal = true;
        }
    }

    public function deleteExpense($expenseId)
    {
        try {
            DB::transaction(function () use ($expenseId) {
                $expense = Expense::find($expenseId);

                if ($expense) {
                    // Delete related ledger transactions
                    LedgerTransaction::where('reference', $expense->expense_ref)
                        ->where('company_profile_id', $expense->company_profile_id)
                        ->delete();

                    // Delete receipt file if exists
                    if ($expense->receipt_path) {
                        Storage::disk('public')->delete($expense->receipt_path);
                    }

                    $expense->delete();
                    $this->success('Expense and related ledger entries deleted successfully!');
                    $this->calculateStats();
                }
            });
        } catch (\Exception $e) {
            Log::error('Error deleting expense: ' . $e->getMessage());
            $this->error('Error deleting expense');
        }
    }

    public function viewExpense($expenseId)
    {
        $this->viewingExpense = Expense::with(['category', 'creator', 'approver'])->find($expenseId);
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingExpense = null;
    }

    public function approveExpense($expenseId)
    {
        try {
            $expense = Expense::find($expenseId);

            if ($expense) {
                $expense->approve(auth()->id(), 'Approved from expense management');
                $this->success('Expense approved successfully!');
                $this->calculateStats();

                // Refresh the viewing expense if it's the same one
                if ($this->viewingExpense && $this->viewingExpense->id == $expenseId) {
                    $this->viewingExpense->refresh();
                }
            }
        } catch (\Exception $e) {
            Log::error('Error approving expense: ' . $e->getMessage());
            $this->error('Error approving expense');
        }
    }

    public function rejectExpense($expenseId, $notes = '')
    {
        try {
            $expense = Expense::find($expenseId);

            if ($expense) {
                $expense->reject(auth()->id(), $notes ?: 'Rejected from expense management');
                $this->success('Expense rejected!');
                $this->calculateStats();

                // Refresh the viewing expense if it's the same one
                if ($this->viewingExpense && $this->viewingExpense->id == $expenseId) {
                    $this->viewingExpense->refresh();
                }
            }
        } catch (\Exception $e) {
            Log::error('Error rejecting expense: ' . $e->getMessage());
            $this->error('Error rejecting expense');
        }
    }

    public function openCategoryModal()
    {
        $this->showCategoryModal = true;
        $this->resetCategoryForm();
    }

    public function closeCategoryModal()
    {
        $this->showCategoryModal = false;
        $this->resetValidation();
        $this->resetCategoryForm();
    }

    public function resetCategoryForm()
    {
        $this->categoryName = '';
        $this->categoryDescription = '';
    }

    public function saveCategory()
    {
        $this->validate([
            'categoryName' => 'required|string|max:255',
            'categoryDescription' => 'nullable|string',
        ]);

        // Ensure a company is selected
        if (!$this->selectedCompanyId) {
            $this->addError('categoryName', 'Please select a company first.');
            return;
        }

        // Check for duplicate names within the same company (company-specific check)
        $existingCategory = ExpenseCategory::where('name', $this->categoryName)
            ->where('company_profile_id', $this->selectedCompanyId)
            ->first();

        if ($existingCategory) {
            $this->addError('categoryName', 'A category with this name already exists for this company.');
            return;
        }

        try {
            ExpenseCategory::create([
                'name' => $this->categoryName,
                'description' => $this->categoryDescription,
                'company_profile_id' => $this->selectedCompanyId, // Now properly set
                'is_active' => true,
            ]);

            $this->success('Category added successfully!');
            $this->closeCategoryModal();
        } catch (\Exception $e) {
            Log::error('Error saving category: ' . $e->getMessage());
            $this->error('Error saving category');
        }
    }

    public function calculateStats()
    {
        $query = Expense::whereBetween('expense_date', [$this->dateFrom, $this->dateTo]);

        // Filter by selected company if one is selected
        if ($this->selectedCompanyId) {
            $query->where('company_profile_id', $this->selectedCompanyId);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_expenses,
            SUM(amount) as total_amount,
            SUM(CASE WHEN approval_status = "pending" THEN amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN is_reimbursable = 1 AND is_reimbursed = 0 THEN amount ELSE 0 END) as reimbursable_amount
        ')->first();

        $this->totalExpenses = $stats->total_expenses ?? 0;
        $this->totalAmount = $stats->total_amount ?? 0;
        $this->pendingAmount = $stats->pending_amount ?? 0;
        $this->reimbursableAmount = $stats->reimbursable_amount ?? 0;
    }

    public function updatedDateFrom()
    {
        $this->calculateStats();
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->calculateStats();
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function applyFilters()
    {
        $this->appliedCompanyFilter = $this->companyFilter;
        $this->resetPage();
        $this->success('Filters Applied!', 'Expenses filtered successfully.');
    }

    public function resetFilters()
    {
        $this->reset(['companyFilter', 'appliedCompanyFilter', 'categoryFilter', 'statusFilter', 'paymentMethodFilter']);
        $this->resetPage();
    }

    private function getFilteredQuery()
    {
        $query = Expense::with(['category', 'creator', 'company'])
            ->whereBetween('expense_date', [$this->dateFrom, $this->dateTo]);

        // Filter by selected company
        if ($this->selectedCompanyId) {
            $query->where('company_profile_id', $this->selectedCompanyId);
        }

        // Apply additional company filter if set
        if (!empty($this->appliedCompanyFilter)) {
            $query->whereIn('company_profile_id', $this->appliedCompanyFilter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('expense_title', 'like', '%' . $this->search . '%')
                    ->orWhere('expense_ref', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhereHas('company', function ($companyQuery) {
                        $companyQuery->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->statusFilter) {
            $query->where('approval_status', $this->statusFilter);
        }

        if ($this->paymentMethodFilter) {
            $query->where('payment_method', $this->paymentMethodFilter);
        }

        return $query->orderBy('expense_date', 'desc');
    }

    public function getExpenseCategoriesProperty()
    {
        // return ExpenseCategory::active()->get();
        // If categories are company-specific, use:
        return ExpenseCategory::active()
            ->when($this->selectedCompanyId, function ($query) {
                return $query->where('company_profile_id', $this->selectedCompanyId);
            })
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        if ($this->activeTab === 'expenses') {
            $expenses = $this->getFilteredQuery()->paginate($this->perPage);

            // Get expense ledger summary for selected company
            $expenseLedgerSummary = [];
            if ($this->selectedCompanyId) {
                $expenseLedgerSummary = AccountLedger::getExpenseBalances($this->selectedCompanyId);
            }

            return view('livewire.expense-management', [
                'expenses' => $expenses,
                'categories' => collect(),
                'expenseCategories' => $this->getExpenseCategoriesProperty(),
                'expenseLedgerSummary' => $expenseLedgerSummary,
            ]);
        } else {
            $categories = ExpenseCategory::with('company')
                ->withCount('expenses')
                ->when($this->selectedCompanyId, function ($query) {
                    return $query->where('company_profile_id', $this->selectedCompanyId);
                })
                ->when($this->search, function ($q) {
                    return $q->where('name', 'like', '%' . $this->search . '%');
                })
                ->orderBy('name')
                ->paginate($this->perPage);

            return view('livewire.expense-management', [
                'expenses' => collect(),
                'categories' => $categories,
                'expenseCategories' => $this->getExpenseCategoriesProperty(),
                'expenseLedgerSummary' => [],
            ]);
        }
    }
}
