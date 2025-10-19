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
use App\Models\Invoice;
use App\Models\InvoicePayment;
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
                $this->dateTo = $now->copy()->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $this->dateFrom = $now->startOfMonth()->format('Y-m-d');
                $this->dateTo = $now->copy()->endOfMonth()->format('Y-m-d');
                break;
            case 'this_quarter':
                $this->dateFrom = $now->startOfQuarter()->format('Y-m-d');
                $this->dateTo = $now->copy()->endOfQuarter()->format('Y-m-d');
                break;
            case 'this_year':
                $this->dateFrom = $now->startOfYear()->format('Y-m-d');
                $this->dateTo = $now->copy()->endOfYear()->format('Y-m-d');
                break;
            case 'last_month':
                $lastMonth = $now->copy()->subMonth();
                $this->dateFrom = $lastMonth->startOfMonth()->format('Y-m-d');
                $this->dateTo = $lastMonth->endOfMonth()->format('Y-m-d');
                break;
            case 'last_quarter':
                $lastQuarter = $now->copy()->subQuarter();
                $this->dateFrom = $lastQuarter->startOfQuarter()->format('Y-m-d');
                $this->dateTo = $lastQuarter->endOfQuarter()->format('Y-m-d');
                break;
            case 'last_year':
                $lastYear = $now->copy()->subYear();
                $this->dateFrom = $lastYear->startOfYear()->format('Y-m-d');
                $this->dateTo = $lastYear->endOfYear()->format('Y-m-d');
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

    // FIXED: Comprehensive financial summary using ledger system
    private function generateFinancialSummary()
    {
        $companyId = $this->selectedCompanyId;

        // FIXED: Get income from ledger (invoices/sales revenue)
        $totalIncome = AccountLedger::getTotalIncome($companyId, $this->dateFrom, $this->dateTo);

        // FIXED: Get expenses from ledger system
        $totalExpenses = AccountLedger::getTotalExpenses($companyId, $this->dateFrom, $this->dateTo);

        // FIXED: Get employee payments from ledger
        $employeePayments = LedgerTransaction::forCompany($companyId)
            ->forDateRange($this->dateFrom, $this->dateTo)
            ->where('type', 'expense')
            ->whereHas('ledger', function ($query) {
                $query->where('ledger_type', 'expenses')
                    ->where('ledger_name', 'LIKE', '%salary%');
            })
            ->sum('debit_amount');

        // Current balances
        $cashBalance = AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'cash')
            ->sum('current_balance');

        $bankBalance = AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'bank')
            ->sum('current_balance');

        // Client receivables (positive balances = they owe us)
        $clientReceivables = AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'client')
            ->where('current_balance', '>', 0)
            ->sum('current_balance');

        // Employee liabilities (positive balances = we owe them)
        $employeeLiabilities = AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'employee')
            ->where('current_balance', '>', 0)
            ->sum('current_balance');

        // FIXED: Expenses by category from ledger
        $expensesByCategory = AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'expenses')
            ->whereHas('transactions', function ($query) {
                $query->whereBetween('date', [$this->dateFrom, $this->dateTo]);
            })
            ->with(['transactions' => function ($query) {
                $query->whereBetween('date', [$this->dateFrom, $this->dateTo])
                    ->where('type', 'expense');
            }])
            ->get()
            ->mapWithKeys(function ($ledger) {
                return [
                    $ledger->ledger_name => [
                        'total' => $ledger->transactions->sum('debit_amount'),
                        'count' => $ledger->transactions->count(),
                    ]
                ];
            });

        // FIXED: Monthly trends using ledger data
        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth()->format('Y-m-d');
            $monthEnd = $month->copy()->endOfMonth()->format('Y-m-d');

            $monthlyIncome = AccountLedger::getTotalIncome($companyId, $monthStart, $monthEnd);
            $monthlyExpenses = AccountLedger::getTotalExpenses($companyId, $monthStart, $monthEnd);

            $monthlyTrends[] = [
                'month' => $month->format('M Y'),
                'income' => $monthlyIncome,
                'expenses' => $monthlyExpenses,
                'net_profit' => $monthlyIncome - $monthlyExpenses,
            ];
        }

        return [
            'summary' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'employee_payments' => $employeePayments,
                'net_profit' => AccountLedger::getNetProfit($companyId, $this->dateFrom, $this->dateTo),
                'cash_balance' => $cashBalance,
                'bank_balance' => $bankBalance,
                'total_available' => $cashBalance + $bankBalance,
                'client_receivables' => $clientReceivables,
                'employee_liabilities' => $employeeLiabilities,
            ],
            'expenses_by_category' => $expensesByCategory,
            'monthly_trends' => $monthlyTrends,
        ];
    }

    // FIXED: Profit & Loss using proper accounting principles
    private function generateProfitLoss()
    {
        $companyId = $this->selectedCompanyId;

        // FIXED: Revenue from income ledger accounts
        $totalRevenue = AccountLedger::getTotalIncome($companyId, $this->dateFrom, $this->dateTo);

        // Revenue breakdown
        $revenue = [
            'sales_revenue' => $totalRevenue,
            'other_income' => 0, // You can add other income sources
            'total' => $totalRevenue,
        ];

        // FIXED: Operating expenses from expense ledger accounts
        $expenseLedgers = AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'expenses')
            ->whereHas('transactions', function ($query) {
                $query->whereBetween('date', [$this->dateFrom, $this->dateTo])
                    ->where('type', 'expense');
            })
            ->with(['transactions' => function ($query) {
                $query->whereBetween('date', [$this->dateFrom, $this->dateTo])
                    ->where('type', 'expense');
            }])
            ->get();

        $expenses = $expenseLedgers->mapWithKeys(function ($ledger) {
            return [$ledger->ledger_name => $ledger->transactions->sum('debit_amount')];
        });

        $totalOperatingExpenses = $expenses->sum();

        // Calculate profit margins
        $grossProfit = $revenue['total'] - $totalOperatingExpenses;
        $netProfit = $grossProfit; // Add taxes, interest if applicable

        // Profit margins
        $grossProfitMargin = $revenue['total'] > 0 ? ($grossProfit / $revenue['total']) * 100 : 0;
        $netProfitMargin = $revenue['total'] > 0 ? ($netProfit / $revenue['total']) * 100 : 0;

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'total_operating_expenses' => $totalOperatingExpenses,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'gross_profit_margin' => round($grossProfitMargin, 2),
            'net_profit_margin' => round($netProfitMargin, 2),
        ];
    }

    // FIXED: Balance Sheet using proper accounting equation
    private function generateBalanceSheet()
    {
        $companyId = $this->selectedCompanyId;

        // ASSETS
        $cashAccounts = AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'cash')
            ->sum('current_balance');

        $bankAccounts = AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'bank')
            ->sum('current_balance');

        // Accounts Receivable (clients owe us - positive balances)
        $accountsReceivable = AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'client')
            ->where('current_balance', '>', 0)
            ->sum('current_balance');

        $currentAssets = $cashAccounts + $bankAccounts + $accountsReceivable;

        $assets = [
            'current_assets' => [
                'cash' => $cashAccounts,
                'bank' => $bankAccounts,
                'accounts_receivable' => $accountsReceivable,
                'total' => $currentAssets,
            ],
            'total' => $currentAssets,
        ];

        // LIABILITIES
        // Accounts Payable (we owe clients - negative client balances)
        $accountsPayable = abs(AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'client')
            ->where('current_balance', '<', 0)
            ->sum('current_balance'));

        // Employee Liabilities (we owe employees - positive employee balances)
        $employeePayables = AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'employee')
            ->where('current_balance', '>', 0)
            ->sum('current_balance');

        $currentLiabilities = $accountsPayable + $employeePayables;

        $liabilities = [
            'current_liabilities' => [
                'accounts_payable' => $accountsPayable,
                'employee_payables' => $employeePayables,
                'total' => $currentLiabilities,
            ],
            'total' => $currentLiabilities,
        ];

        // EQUITY (Assets - Liabilities)
        $retainedEarnings = $assets['total'] - $liabilities['total'];

        $equity = [
            'retained_earnings' => $retainedEarnings,
            'total' => $retainedEarnings,
        ];

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
        ];
    }

    // FIXED: Cash Flow using ledger transactions
    private function generateCashFlow()
    {
        $companyId = $this->selectedCompanyId;

        // Get all cash and bank transactions
        $cashTransactions = LedgerTransaction::forCompany($companyId)
            ->forDateRange($this->dateFrom, $this->dateTo)
            ->whereHas('ledger', function ($query) {
                $query->whereIn('ledger_type', ['cash', 'bank']);
            })
            ->with('ledger')
            ->orderBy('date', 'desc')
            ->get();

        // Operating cash flows
        $operatingInflows = $cashTransactions
            ->where('debit_amount', '>', 0)
            ->where('type', 'receipt')
            ->sum('debit_amount');

        $operatingOutflows = $cashTransactions
            ->where('credit_amount', '>', 0)
            ->whereIn('type', ['expense', 'payment'])
            ->sum('credit_amount');

        $netOperatingCashFlow = $operatingInflows - $operatingOutflows;

        // Opening and closing balances
        $openingBalance = AccountLedger::forCompany($companyId)
            ->whereIn('ledger_type', ['cash', 'bank'])
            ->sum('opening_balance');

        $closingBalance = AccountLedger::forCompany($companyId)
            ->whereIn('ledger_type', ['cash', 'bank'])
            ->sum('current_balance');

        // Group transactions by type for better reporting
        $transactionsByType = $cashTransactions->groupBy('type')
            ->map(function ($typeTransactions, $type) {
                return [
                    'inflows' => $typeTransactions->sum('debit_amount'),
                    'outflows' => $typeTransactions->sum('credit_amount'),
                    'count' => $typeTransactions->count(),
                ];
            });

        return [
            'operating_cash_flows' => [
                'inflows' => $operatingInflows,
                'outflows' => $operatingOutflows,
                'net' => $netOperatingCashFlow,
            ],
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'net_change' => $closingBalance - $openingBalance,
            'transactions' => $cashTransactions,
            'transactions_by_type' => $transactionsByType,
        ];
    }

    // FIXED: Enhanced expense report
    private function generateExpenseReport()
    {
        $companyId = $this->selectedCompanyId;

        $query = Expense::where('company_profile_id', $companyId)
            ->whereBetween('expense_date', [$this->dateFrom, $this->dateTo])
            ->with(['category', 'creator', 'company']);

        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->statusFilter) {
            $query->where('approval_status', $this->statusFilter);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->get();

        // FIXED: Category breakdown with percentage
        $categoryBreakdown = $expenses->groupBy('category.name')
            ->map(function ($categoryExpenses, $categoryName) use ($expenses) {
                $total = $categoryExpenses->sum('amount');
                $grandTotal = $expenses->sum('amount');

                return [
                    'total' => $total,
                    'count' => $categoryExpenses->count(),
                    'avg' => $categoryExpenses->avg('amount'),
                    'percentage' => $grandTotal > 0 ? round(($total / $grandTotal) * 100, 2) : 0,
                ];
            });

        // Monthly trend
        $monthlyBreakdown = $expenses->groupBy(function ($expense) {
            return $expense->expense_date->format('Y-m');
        })->map(function ($monthExpenses) {
            return [
                'total' => $monthExpenses->sum('amount'),
                'count' => $monthExpenses->count(),
            ];
        });

        return [
            'expenses' => $expenses,
            'category_breakdown' => $categoryBreakdown,
            'monthly_breakdown' => $monthlyBreakdown,
            'total_amount' => $expenses->sum('amount'),
            'total_count' => $expenses->count(),
            'average_expense' => $expenses->count() > 0 ? $expenses->avg('amount') : 0,
        ];
    }

    // FIXED: Enhanced employee report
    private function generateEmployeeReport()
    {
        $companyId = $this->selectedCompanyId;

        $query = EmployeePayment::whereHas('employee', function ($q) use ($companyId) {
            $q->where('company_profile_id', $companyId);
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

        // FIXED: Employee breakdown with payment types
        $employeeBreakdown = $payments->groupBy('employee.name')
            ->map(function ($employeePayments) {
                $paymentTypes = $employeePayments->groupBy('payment_type')
                    ->map(function ($typePayments) {
                        return [
                            'total' => $typePayments->sum('amount'),
                            'count' => $typePayments->count(),
                        ];
                    });

                return [
                    'total' => $employeePayments->sum('amount'),
                    'count' => $employeePayments->count(),
                    'avg' => $employeePayments->avg('amount'),
                    'by_type' => $paymentTypes,
                ];
            });

        return [
            'payments' => $payments,
            'employee_breakdown' => $employeeBreakdown,
            'total_amount' => $payments->sum('amount'),
            'total_count' => $payments->count(),
            'average_payment' => $payments->count() > 0 ? $payments->avg('amount') : 0,
        ];
    }

    // FIXED: Enhanced client report with invoice data
    private function generateClientReport()
    {
        $companyId = $this->selectedCompanyId;

        $clients = Client::where('company_profile_id', $companyId)
            ->with(['company'])
            ->withCount(['invoices'])
            ->withSum(['invoices'], 'total_amount')
            ->withSum(['invoices'], 'paid_amount')
            ->get();

        // Client ledger balances
        $clientLedgers = AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'client')
            ->with('ledgerable')
            ->get();

        // Client payment history in date range
        $clientPayments = InvoicePayment::whereHas('invoice', function ($query) use ($companyId) {
            $query->where('company_profile_id', $companyId);
        })
            ->whereBetween('payment_date', [$this->dateFrom, $this->dateTo])
            ->with(['invoice.client'])
            ->get()
            ->groupBy('invoice.client.name')
            ->map(function ($payments) {
                return [
                    'total_paid' => $payments->sum('amount'),
                    'payment_count' => $payments->count(),
                ];
            });

        return [
            'clients' => $clients,
            'client_ledgers' => $clientLedgers,
            'client_payments' => $clientPayments,
            'total_receivables' => $clientLedgers->where('current_balance', '>', 0)->sum('current_balance'),
            'total_clients' => $clients->count(),
        ];
    }

    // FIXED: Enhanced ledger report
    private function generateLedgerReport()
    {
        $companyId = $this->selectedCompanyId;

        $query = LedgerTransaction::forCompany($companyId)
            ->forDateRange($this->dateFrom, $this->dateTo)
            ->with(['ledger', 'company']);

        if ($this->ledgerTypeFilter) {
            $query->whereHas('ledger', function ($q) {
                $q->where('ledger_type', $this->ledgerTypeFilter);
            });
        }

        $transactions = $query->orderBy('date', 'desc')->get();

        // FIXED: Comprehensive breakdown by ledger type and transaction type
        $ledgerBreakdown = $transactions->groupBy('ledger.ledger_type')
            ->map(function ($typeTransactions) {
                $byTransactionType = $typeTransactions->groupBy('type')
                    ->map(function ($transTypeTransactions) {
                        return [
                            'total_debits' => $transTypeTransactions->sum('debit_amount'),
                            'total_credits' => $transTypeTransactions->sum('credit_amount'),
                            'count' => $transTypeTransactions->count(),
                        ];
                    });

                return [
                    'total_debits' => $typeTransactions->sum('debit_amount'),
                    'total_credits' => $typeTransactions->sum('credit_amount'),
                    'count' => $typeTransactions->count(),
                    'by_transaction_type' => $byTransactionType,
                ];
            });

        // Trial balance
        $trialBalance = AccountLedger::forCompany($companyId)
            ->where('current_balance', '!=', 0)
            ->get()
            ->map(function ($ledger) {
                return [
                    'ledger_name' => $ledger->ledger_name,
                    'ledger_type' => $ledger->ledger_type,
                    'debit_balance' => $ledger->current_balance >= 0 ? $ledger->current_balance : 0,
                    'credit_balance' => $ledger->current_balance < 0 ? abs($ledger->current_balance) : 0,
                ];
            });

        return [
            'transactions' => $transactions,
            'ledger_breakdown' => $ledgerBreakdown,
            'trial_balance' => $trialBalance,
            'total_debits' => $transactions->sum('debit_amount'),
            'total_credits' => $transactions->sum('credit_amount'),
            'total_trial_debits' => $trialBalance->sum('debit_balance'),
            'total_trial_credits' => $trialBalance->sum('credit_balance'),
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
        $expenseCategories = [];
        $employees = [];

        if ($this->selectedCompanyId) {
            $expenseCategories = ExpenseCategory::where('company_profile_id', $this->selectedCompanyId)
                ->where('is_active', true)
                ->get();

            $employees = Employee::where('company_profile_id', $this->selectedCompanyId)
                ->where('is_active', true)
                ->get();
        }

        return view('livewire.financial-reports', [
            'expenseCategories' => $expenseCategories,
            'employees' => $employees,
        ]);
    }
}
