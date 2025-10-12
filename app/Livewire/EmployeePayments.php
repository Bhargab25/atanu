<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\EmployeePayment;
use App\Models\Employee;
use App\Models\CompanyProfile;
use App\Models\AccountLedger;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Carbon\Carbon;

class EmployeePayments extends Component
{
    use WithPagination, Toast;

    public $search = '';
    public $monthFilter = '';
    public $statusFilter = '';
    public $employeeFilter = '';
    public $companyFilter = [];
    public $appliedCompanyFilter = [];

    // Company management
    public $selectedCompanyId = null;
    public $companyOptions = [];

    public $sortBy = ['column' => 'payment_date', 'direction' => 'desc'];
    public $perPage = 20;


    public function mount()
    {
        $this->loadCompanyOptions();

        // Set default company if only one exists
        if (count($this->companyOptions) === 1) {
            $this->selectedCompanyId = $this->companyOptions[0]['id'];
        }
    }

    private function loadCompanyOptions()
    {
        $this->companyOptions = CompanyProfile::active()
            ->get()
            ->map(fn($company) => ['id' => $company->id, 'name' => $company->name])
            ->toArray();
    }

    public function updatedSelectedCompanyId()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedMonthFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedEmployeeFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'monthFilter', 'statusFilter', 'employeeFilter', 'companyFilter', 'appliedCompanyFilter']);
    }

    public function applyFilters()
    {
        $this->appliedCompanyFilter = $this->companyFilter;
        $this->resetPage();
        $this->success('Filters Applied!', 'Payments filtered successfully.');
    }

    public function render()
    {
        $payments = EmployeePayment::query()
            ->with(['employee.company', 'creator'])
            ->when($this->selectedCompanyId, function ($query) {
                return $query->whereHas('employee', function ($employeeQuery) {
                    $employeeQuery->where('company_profile_id', $this->selectedCompanyId);
                });
            })
            ->when(!empty($this->appliedCompanyFilter), function ($query) {
                return $query->whereHas('employee', function ($employeeQuery) {
                    $employeeQuery->whereIn('company_profile_id', $this->appliedCompanyFilter);
                });
            })
            ->when($this->search, function ($query) {
                return $query->where(function ($subQuery) {
                    $subQuery->where('payment_id', 'like', '%' . $this->search . '%')
                        ->orWhere('reference_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('employee', function ($employeeQuery) {
                            $employeeQuery->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('employee_id', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->monthFilter, function ($query) {
                return $query->where('month_year', $this->monthFilter);
            })
            ->when($this->statusFilter, function ($query) {
                return $query->where('status', $this->statusFilter);
            })
            ->when($this->employeeFilter, function ($query) {
                return $query->where('employee_id', $this->employeeFilter);
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);

        // Filter employees by selected company
        $employees = Employee::active()
            ->when($this->selectedCompanyId, function ($query) {
                return $query->where('company_profile_id', $this->selectedCompanyId);
            })
            ->orderBy('name')
            ->get();

        // Calculate totals for selected company
        $totalPaidQuery = EmployeePayment::where('status', 'paid');
        $thisMonthPaidQuery = EmployeePayment::where('status', 'paid')
            ->where('month_year', now()->format('Y-m'));

        if ($this->selectedCompanyId) {
            $totalPaidQuery->whereHas('employee', function ($query) {
                $query->where('company_profile_id', $this->selectedCompanyId);
            });
            $thisMonthPaidQuery->whereHas('employee', function ($query) {
                $query->where('company_profile_id', $this->selectedCompanyId);
            });
        }

        $totalPaid = $totalPaidQuery->sum('amount');
        $thisMonthPaid = $thisMonthPaidQuery->sum('amount');

        // Get employee ledger summary for selected company
        $employeeLedgerSummary = [];
        if ($this->selectedCompanyId) {
            $employeeLedgerSummary = AccountLedger::getEmployeeBalances($this->selectedCompanyId);
        }

        return view('livewire.employee-payments', [
            'payments' => $payments,
            'employees' => $employees,
            'totalPaid' => $totalPaid,
            'thisMonthPaid' => $thisMonthPaid,
            'employeeLedgerSummary' => $employeeLedgerSummary,
        ]);
    }
}
