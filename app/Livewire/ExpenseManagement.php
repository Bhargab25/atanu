<?php
// app/Livewire/ExpenseManagement.php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use App\Models\Expense;
use App\Models\ExpenseCategory;
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

    // Statistics
    public $totalExpenses = 0;
    public $totalAmount = 0;
    public $pendingAmount = 0;
    public $reimbursableAmount = 0;

    protected $rules = [
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
        'expenseTitle.required' => 'Expense title is required',
        'categoryId.required' => 'Please select a category',
        'amount.required' => 'Amount is required',
        'amount.min' => 'Amount must be greater than 0',
        'expenseDate.required' => 'Expense date is required',
    ];

    public function mount()
    {
        $this->expenseDate = now()->format('Y-m-d');
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->calculateStats();
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
    }

    public function saveExpense()
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $receiptPath = null;
                if ($this->receipt) {
                    $receiptPath = $this->receipt->store('receipts', 'public');
                }

                $data = [
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
                    // Don't update expense_ref when editing
                    $this->editingExpense->update($data);
                    $this->success('Expense updated successfully!');
                } else {
                    // Let the model generate the expense_ref automatically
                    Expense::create($data);
                    $this->success('Expense added successfully!');
                }

                $this->closeExpenseModal();
                $this->calculateStats();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle specific database errors
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
    public function editExpense($expenseId)
    {
        $this->editingExpense = Expense::find($expenseId);

        if ($this->editingExpense) {
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
            $expense = Expense::find($expenseId);

            if ($expense) {
                // Delete receipt file if exists
                if ($expense->receipt_path) {
                    Storage::disk('public')->delete($expense->receipt_path);
                }

                $expense->delete();
                $this->success('Expense deleted successfully!');
                $this->calculateStats();
            }
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
                $expense->update([
                    'approval_status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                $this->success('Expense approved successfully!');
                $this->calculateStats();
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
                $expense->update([
                    'approval_status' => 'rejected',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'approval_notes' => $notes,
                ]);

                $this->success('Expense rejected!');
                $this->calculateStats();
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
            'categoryName' => 'required|string|max:255|unique:expense_categories,name',
            'categoryDescription' => 'nullable|string',
        ]);

        try {
            ExpenseCategory::create([
                'name' => $this->categoryName,
                'description' => $this->categoryDescription,
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

    private function getFilteredQuery()
    {
        $query = Expense::with(['category', 'creator'])
            ->whereBetween('expense_date', [$this->dateFrom, $this->dateTo]);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('expense_title', 'like', '%' . $this->search . '%')
                    ->orWhere('expense_ref', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
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

    public function render()
    {
        if ($this->activeTab === 'categories') {
            $categories = ExpenseCategory::withCount('expenses')
                ->when($this->search, function ($q) {
                    return $q->where('name', 'like', '%' . $this->search . '%');
                })
                ->paginate($this->perPage);

            return view('livewire.expense-management', [
                'expenses' => collect(),
                'categories' => $categories,
                'expenseCategories' => ExpenseCategory::active()->get(),
            ]);
        } else {
            $expenses = $this->getFilteredQuery()->paginate($this->perPage);

            return view('livewire.expense-management', [
                'expenses' => $expenses,
                'categories' => collect(),
                'expenseCategories' => ExpenseCategory::active()->get(),
            ]);
        }
    }
}
