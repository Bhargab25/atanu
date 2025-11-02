<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CompanyProfile;
use App\Models\AccountLedger;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Models\Client;
use App\Models\InvoicePayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public $selectedCompanyId = null;
    public $companyOptions = [];
    
    // Date range
    public $period = 'this_month';
    public $dateFrom;
    public $dateTo;
    
    // Dashboard data
    public $stats = [];
    public $recentInvoices = [];
    public $recentExpenses = [];
    public $recentPayments = [];
    public $cashFlowData = [];
    public $topClients = [];
    
    public function mount()
    {
        $this->loadCompanies();
        $this->setPeriodDates();
        
        // Set default company if only one exists
        if (count($this->companyOptions) === 1) {
            $this->selectedCompanyId = $this->companyOptions[0]['id'];
        }
        
        $this->loadDashboardData();
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
        $this->loadDashboardData();
    }
    
    public function updatedPeriod()
    {
        $this->setPeriodDates();
        $this->loadDashboardData();
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
            case 'this_year':
                $this->dateFrom = $now->startOfYear()->format('Y-m-d');
                $this->dateTo = $now->copy()->endOfYear()->format('Y-m-d');
                break;
        }
    }
    
    private function loadDashboardData()
    {
        if (!$this->selectedCompanyId) {
            return;
        }
        
        $companyId = $this->selectedCompanyId;
        
        // Financial Stats
        $this->stats = $this->calculateStats($companyId);
        
        // Recent Activities
        $this->recentInvoices = Invoice::where('company_profile_id', $companyId)
            ->with(['client'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        $this->recentExpenses = Expense::where('company_profile_id', $companyId)
            ->with(['category', 'creator'])
            ->orderBy('expense_date', 'desc')
            ->limit(5)
            ->get();
        
        $this->recentPayments = EmployeePayment::whereHas('employee', function($q) use ($companyId) {
            $q->where('company_profile_id', $companyId);
        })
        ->with(['employee'])
        ->orderBy('payment_date', 'desc')
        ->limit(5)
        ->get();
        
        // Cash Flow (Last 7 days)
        $this->cashFlowData = $this->getCashFlowData($companyId);
        
        // Top Clients by Revenue
        $this->topClients = $this->getTopClients($companyId);
    }
    
    private function calculateStats($companyId)
    {
        // Total Income (from income ledger)
        $totalIncome = AccountLedger::getTotalIncome($companyId, $this->dateFrom, $this->dateTo);
        
        // Total Expenses
        $totalExpenses = AccountLedger::getTotalExpenses($companyId, $this->dateFrom, $this->dateTo);
        
        // Net Profit
        $netProfit = $totalIncome - $totalExpenses;
        
        // Cash Balance
        $cashBalance = AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'cash')
            ->sum('current_balance');
        
        // Bank Balance
        $bankBalance = AccountLedger::forCompany($companyId)
            ->where('ledger_type', 'bank')
            ->sum('current_balance');
        
        // Outstanding Invoices
        $outstandingInvoices = Invoice::where('company_profile_id', $companyId)
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->sum('total_amount') - Invoice::where('company_profile_id', $companyId)
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->sum('paid_amount');
        
        // Pending Expenses
        $pendingExpenses = Expense::where('company_profile_id', $companyId)
            ->where('approval_status', 'pending')
            ->sum('amount');
        
        // Active Employees
        $activeEmployees = Employee::where('company_profile_id', $companyId)
            ->where('is_active', true)
            ->count();
        
        // Active Clients
        $activeClients = Client::where('company_profile_id', $companyId)
            ->where('is_active', true)
            ->count();
        
        // Invoices Count
        $invoicesCount = Invoice::where('company_profile_id', $companyId)
            ->whereBetween('invoice_date', [$this->dateFrom, $this->dateTo])
            ->count();
        
        // Expenses Count
        $expensesCount = Expense::where('company_profile_id', $companyId)
            ->whereBetween('expense_date', [$this->dateFrom, $this->dateTo])
            ->count();
        
        return [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'cash_balance' => $cashBalance,
            'bank_balance' => $bankBalance,
            'total_available' => $cashBalance + $bankBalance,
            'outstanding_invoices' => $outstandingInvoices,
            'pending_expenses' => $pendingExpenses,
            'active_employees' => $activeEmployees,
            'active_clients' => $activeClients,
            'invoices_count' => $invoicesCount,
            'expenses_count' => $expensesCount,
        ];
    }
    
    private function getCashFlowData($companyId)
    {
        $cashFlowData = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            
            // Inflows
            $inflows = InvoicePayment::whereHas('invoice', function($q) use ($companyId) {
                $q->where('company_profile_id', $companyId);
            })
            ->whereDate('payment_date', $dateStr)
            ->sum('amount');
            
            // Outflows
            $expenseOutflows = Expense::where('company_profile_id', $companyId)
                ->whereDate('expense_date', $dateStr)
                ->sum('amount');
            
            $employeeOutflows = EmployeePayment::whereHas('employee', function($q) use ($companyId) {
                $q->where('company_profile_id', $companyId);
            })
            ->whereDate('payment_date', $dateStr)
            ->sum('amount');
            
            $outflows = $expenseOutflows + $employeeOutflows;
            
            $cashFlowData[] = [
                'date' => $date->format('M d'),
                'inflows' => $inflows,
                'outflows' => $outflows,
                'net' => $inflows - $outflows,
            ];
        }
        
        return $cashFlowData;
    }
    
    private function getTopClients($companyId)
    {
        return Invoice::where('company_profile_id', $companyId)
            ->whereBetween('invoice_date', [$this->dateFrom, $this->dateTo])
            ->select('client_id', DB::raw('SUM(total_amount) as total_revenue'))
            ->groupBy('client_id')
            ->orderBy('total_revenue', 'desc')
            ->limit(5)
            ->with('client')
            ->get()
            ->map(function($invoice) {
                return [
                    'client' => $invoice->client,
                    'revenue' => $invoice->total_revenue,
                ];
            });
    }
    
    public function render()
    {
        return view('livewire.dashboard');
    }
}
