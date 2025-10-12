<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\CompanyProfile;
use App\Models\AccountLedger;
use App\Models\LedgerTransaction;
use App\Models\Expense;
use App\Models\EmployeePayment;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialReports extends Component
{
    use WithPagination, Toast;

    // Company management
    public $selectedCompanyId = null;
    public $companyOptions = [];

    // Report type management
    public $activeReport = 'summary';
    public $reportTypes = [
        'summary' => 'Financial Summary',
        'profit_loss' => 'Profit & Loss',
        'balance_sheet' => 'Balance Sheet',
        'cash_flow' => 'Cash Flow',
        'expenses' => 'Expense Reports',
        'employees' => 'Employee Reports',
        'clients' => 'Client Reports',
        'ledger' => 'Ledger Reports',
    ];

    // Date filters
    public $dateFrom;
    public $dateTo;
    public $period = 'this_month';
    public $periodOptions = [
        'today' => 'Today',
        'this_week' => 'This Week',
        'this_month' => 'This Month',
        'this_quarter' => 'This Quarter',
        'this_year' => 'This Year',
        'last_month' => 'Last Month',
        'last_quarter' => 'Last Quarter',
        'last_year' => 'Last Year',
        'custom' => 'Custom Range',
    ];

    // Additional filters
    public $categoryFilter = '';
    public $employeeFilter = '';
    public $statusFilter = '';
    public $ledgerTypeFilter = '';

    // Report data
    public $reportData = [];
    public $chartData = [];
    
    // Pagination
    public $perPage = 20;

    protected $listeners = ['refreshReports' => '$refresh'];

    public function mount()
    {
        $this->loadCompanies();
        $this->setPeriodDates();
        
        // Set default company if only one exists
        if (count($this->companyOptions) === 1) {
            $this->selectedCompanyId = $this->companyOptions[0]['id'];
        }
        
        $this->generateReport();
    }

    private function loadCompanies()
    {
        $this->companyOptions = CompanyProfile::active()
            ->get()
            ->map(fn($company) => ['id' => $company->id, 'name' => $company->name])
            ->toArray();
    }

    public function updatedSelectedCompanyId()
    {
        $this->generateReport();
        $this->resetPage();
    }

    public function updatedPeriod()
    {
        $this->setPeriodDates();
        $this->generateReport();
    }

    public function updatedDateFrom()
    {
        $this->generateReport();
    }

    public function updatedDateTo()
    {
        $this->generateReport();
    }

    public function switchReport($reportType)
    {
        $this->activeReport = $reportType;
        $this->generateReport();
        $this->resetPage();
    }

    private function setPeriodDates()
    {
        $now = Carbon::now();
        
        switch ($this->period) {
            case 'today':
                $this->dateFrom = $now->format('Y-m-d');
                $this->dateTo = $now->format('Y-m-d');
                break;
            case 'this_week':
                $this->dateFrom = $now->startOfWeek()->format('Y-m-d');
                $this->dateTo = $now->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $this->dateFrom = $now->startOfMonth()->format('Y-m-d');
                $this->dateTo = $now->endOfMonth()->format('Y-m-d');
                break;
            case 'this_quarter':
                $this->dateFrom = $now->startOfQuarter()->format('Y-m-d');
                $this->dateTo = $now->endOfQuarter()->format('Y-m-d');
                break;
            case 'this_year':
                $this->dateFrom = $now->startOfYear()->format('Y-m-d');
                $this->dateTo = $now->endOfYear()->format('Y-m-d');
                break;
            case 'last_month':
                $this->dateFrom = $now->subMonth()->startOfMonth()->format('Y-m-d');
                $this->dateTo = $now->endOfMonth()->format('Y-m-d');
                break;
            case 'last_quarter':
                $this->dateFrom = $now->subQuarter()->startOfQuarter()->format('Y-m-d');
                $this->dateTo = $now->endOfQuarter()->format('Y-m-d');
                break;
            case 'last_year':
                $this->dateFrom = $now->subYear()->startOfYear()->format('Y-m-d');
                $this->dateTo = $now->endOfYear()->format('Y-m-d');
                break;
            default:
                // Custom range - don't change dates
                break;
        }
    }

    private function generateReport()
    {
        if (!$this->selectedCompanyId) {
            $this->reportData = [];
            return;
        }

        switch ($this->activeReport) {
            case 'summary':
                $this->reportData = $this->generateFinancialSummary();
                break;
            case 'profit_loss':
                $this->reportData = $this->generateProfitLoss();
                break;
            case 'balance_sheet':
                $this->reportData = $this->generateBalanceSheet();
                break;
            case 'cash_flow':
                $this->reportData = $this->generateCashFlow();
                break;
            case 'expenses':
                $this->reportData = $this->generateExpenseReport();
                break;
            case 'employees':
                $this->reportData = $this->generateEmployeeReport();
                break;
            case 'clients':
                $this->reportData = $this->generateClientReport();
                break;
            case 'ledger':
                $this->reportData = $this->generateLedgerReport();
                break;
        }
    }

    private function generateFinancialSummary()
    {
        $companyId = $this->selectedCompanyId;
        
        // Total Income (Client payments, etc.)
        $totalIncome = 0; // You can add client payment logic here
        
        // Total Expenses
        $totalExpenses = Expense::forCompany($companyId)
            ->forDateRange($this->dateFrom, $this->dateTo)
            ->approved()
            ->sum('amount');
        
        // Employee Payments
        $employeePayments = EmployeePayment::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_profile_id', $companyId);
        })
        ->whereBetween('payment_date', [$this->dateFrom, $this->dateTo])
        ->where('status', 'paid')
        ->sum('amount');
        
        // Cash Balance
        $cashBalance = AccountLedger::forCompany($companyId)
            ->cashAccounts()
            ->sum('current_balance');
        
        // Bank Balance
        $bankBalance = AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'bank')
            ->sum('current_balance');
        
        // Outstanding Client Balances
        $clientBalances = AccountLedger::forCompany($companyId)
            ->clients()
            ->sum('current_balance');
        
        // Outstanding Employee Balances
        $employeeBalances = AccountLedger::forCompany($companyId)
            ->employees()
            ->sum('current_balance');
        
        // Expense by Category
        $expensesByCategory = Expense::forCompany($companyId)
            ->forDateRange($this->dateFrom, $this->dateTo)
            ->approved()
            ->with('category')
            ->get()
            ->groupBy('category.name')
            ->map(function ($expenses) {
                return [
                    'total' => $expenses->sum('amount'),
                    'count' => $expenses->count(),
                ];
            });
        
        // Monthly Trends (last 6 months)
        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $monthlyExpenses = Expense::forCompany($companyId)
                ->forDateRange($monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d'))
                ->approved()
                ->sum('amount');
            
            $monthlyPayments = EmployeePayment::whereHas('employee', function ($query) use ($companyId) {
                $query->where('company_profile_id', $companyId);
            })
            ->whereBetween('payment_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->where('status', 'paid')
            ->sum('amount');
            
            $monthlyTrends[] = [
                'month' => $month->format('M Y'),
                'expenses' => $monthlyExpenses,
                'employee_payments' => $monthlyPayments,
                'total_outflow' => $monthlyExpenses + $monthlyPayments,
            ];
        }
        
        return [
            'summary' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'employee_payments' => $employeePayments,
                'net_profit' => $totalIncome - $totalExpenses - $employeePayments,
                'cash_balance' => $cashBalance,
                'bank_balance' => $bankBalance,
                'total_available' => $cashBalance + $bankBalance,
                'client_balances' => $clientBalances,
                'employee_balances' => $employeeBalances,
            ],
            'expenses_by_category' => $expensesByCategory,
            'monthly_trends' => $monthlyTrends,
        ];
    }

    private function generateProfitLoss()
    {
        $companyId = $this->selectedCompanyId;
        
        // Revenue (you can extend this based on your business model)
        $revenue = [
            'total' => 0, // Add your revenue sources here
        ];
        
        // Expenses by Category
        $expenses = Expense::forCompany($companyId)
            ->forDateRange($this->dateFrom, $this->dateTo)
            ->approved()
            ->with('category')
            ->get()
            ->groupBy('category.name')
            ->map(function ($categoryExpenses) {
                return $categoryExpenses->sum('amount');
            });
        
        // Employee Costs
        $employeeCosts = EmployeePayment::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_profile_id', $companyId);
        })
        ->whereBetween('payment_date', [$this->dateFrom, $this->dateTo])
        ->where('status', 'paid')
        ->sum('amount');
        
        $totalExpenses = $expenses->sum() + $employeeCosts;
        $grossProfit = $revenue['total'] - $totalExpenses;
        
        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'employee_costs' => $employeeCosts,
            'total_expenses' => $totalExpenses,
            'gross_profit' => $grossProfit,
        ];
    }

    private function generateBalanceSheet()
    {
        $companyId = $this->selectedCompanyId;
        
        // Assets
        $assets = [
            'current_assets' => [
                'cash' => AccountLedger::forCompany($companyId)->cashAccounts()->sum('current_balance'),
                'bank' => AccountLedger::forCompany($companyId)->where('ledger_type', 'bank')->sum('current_balance'),
                'accounts_receivable' => AccountLedger::forCompany($companyId)->clients()->sum('current_balance'),
            ],
        ];
        
        $assets['current_assets']['total'] = array_sum($assets['current_assets']);
        $assets['total'] = $assets['current_assets']['total'];
        
        // Liabilities
        $liabilities = [
            'current_liabilities' => [
                'accounts_payable' => AccountLedger::forCompany($companyId)->employees()->where('current_balance', '<', 0)->sum('current_balance'),
                'accrued_expenses' => 0, // You can add more liability types
            ],
        ];
        
        $liabilities['current_liabilities']['total'] = array_sum($liabilities['current_liabilities']);
        $liabilities['total'] = $liabilities['current_liabilities']['total'];
        
        // Equity
        $equity = [
            'retained_earnings' => $assets['total'] - abs($liabilities['total']),
        ];
        
        $equity['total'] = $equity['retained_earnings'];
        
        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
        ];
    }

    private function generateCashFlow()
    {
        $companyId = $this->selectedCompanyId;
        
        // Cash transactions
        $cashTransactions = LedgerTransaction::forCompany($companyId)
            ->forDateRange($this->dateFrom, $this->dateTo)
            ->whereHas('ledger', function ($query) {
                $query->whereIn('ledger_type', ['cash', 'bank']);
            })
            ->with('ledger')
            ->orderBy('date', 'desc')
            ->get();
        
        $cashInflows = $cashTransactions->where('debit_amount', '>', 0)->sum('debit_amount');
        $cashOutflows = $cashTransactions->where('credit_amount', '>', 0)->sum('credit_amount');
        $netCashFlow = $cashInflows - $cashOutflows;
        
        // Opening and closing balances
        $openingBalance = AccountLedger::forCompany($companyId)
            ->whereIn('ledger_type', ['cash', 'bank'])
            ->sum('opening_balance');
        
        $closingBalance = AccountLedger::forCompany($companyId)
            ->whereIn('ledger_type', ['cash', 'bank'])
            ->sum('current_balance');
        
        return [
            'cash_inflows' => $cashInflows,
            'cash_outflows' => $cashOutflows,
            'net_cash_flow' => $netCashFlow,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'transactions' => $cashTransactions,
        ];
    }

    private function generateExpenseReport()
    {
        $query = Expense::forCompany($this->selectedCompanyId)
            ->forDateRange($this->dateFrom, $this->dateTo)
            ->with(['category', 'creator', 'company']);
        
        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }
        
        if ($this->statusFilter) {
            $query->where('approval_status', $this->statusFilter);
        }
        
        $expenses = $query->orderBy('expense_date', 'desc')->get();
        
        // Summary by category
        $categoryBreakdown = $expenses->groupBy('category.name')
            ->map(function ($categoryExpenses) {
                return [
                    'total' => $categoryExpenses->sum('amount'),
                    'count' => $categoryExpenses->count(),
                    'avg' => $categoryExpenses->avg('amount'),
                ];
            });
        
        return [
            'expenses' => $expenses,
            'category_breakdown' => $categoryBreakdown,
            'total_amount' => $expenses->sum('amount'),
            'total_count' => $expenses->count(),
        ];
    }

    private function generateEmployeeReport()
    {
        $query = EmployeePayment::whereHas('employee', function ($q) {
            $q->where('company_profile_id', $this->selectedCompanyId);
        })
        ->whereBetween('payment_date', [$this->dateFrom, $this->dateTo])
        ->with(['employee', 'creator']);
        
        if ($this->employeeFilter) {
            $query->where('employee_id', $this->employeeFilter);
        }
        
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        $payments = $query->orderBy('payment_date', 'desc')->get();
        
        // Summary by employee
        $employeeBreakdown = $payments->groupBy('employee.name')
            ->map(function ($employeePayments) {
                return [
                    'total' => $employeePayments->sum('amount'),
                    'count' => $employeePayments->count(),
                    'avg' => $employeePayments->avg('amount'),
                ];
            });
        
        return [
            'payments' => $payments,
            'employee_breakdown' => $employeeBreakdown,
            'total_amount' => $payments->sum('amount'),
            'total_count' => $payments->count(),
        ];
    }

    private function generateClientReport()
    {
        $clients = Client::where('company_profile_id', $this->selectedCompanyId)
            ->with(['company'])
            ->get();
        
        // Client balances from ledger
        $clientLedgers = AccountLedger::forCompany($this->selectedCompanyId)
            ->clients()
            ->with('ledgerable')
            ->get();
        
        return [
            'clients' => $clients,
            'client_ledgers' => $clientLedgers,
            'total_receivables' => $clientLedgers->sum('current_balance'),
        ];
    }

    private function generateLedgerReport()
    {
        $query = LedgerTransaction::forCompany($this->selectedCompanyId)
            ->forDateRange($this->dateFrom, $this->dateTo)
            ->with(['ledger', 'company']);
        
        if ($this->ledgerTypeFilter) {
            $query->whereHas('ledger', function ($q) {
                $q->where('ledger_type', $this->ledgerTypeFilter);
            });
        }
        
        $transactions = $query->orderBy('date', 'desc')->get();
        
        // Summary by ledger type
        $ledgerBreakdown = $transactions->groupBy('ledger.ledger_type')
            ->map(function ($typeTransactions) {
                return [
                    'total_debits' => $typeTransactions->sum('debit_amount'),
                    'total_credits' => $typeTransactions->sum('credit_amount'),
                    'count' => $typeTransactions->count(),
                ];
            });
        
        return [
            'transactions' => $transactions,
            'ledger_breakdown' => $ledgerBreakdown,
            'total_debits' => $transactions->sum('debit_amount'),
            'total_credits' => $transactions->sum('credit_amount'),
        ];
    }

    public function exportReport($format = 'csv')
    {
        // You can implement CSV/PDF export logic here
        $this->success('Export functionality will be implemented', 'Report export in ' . strtoupper($format) . ' format');
    }

    public function render()
    {
        // Get filter options
        $expenseCategories = ExpenseCategory::forCompany($this->selectedCompanyId)->active()->get();
        $employees = Employee::where('company_profile_id', $this->selectedCompanyId)->active()->get();
        
        return view('livewire.financial-reports', [
            'expenseCategories' => $expenseCategories,
            'employees' => $employees,
        ]);
    }
}
