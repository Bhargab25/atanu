<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\EmployeePayment;
use App\Models\Employee;
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

    public $sortBy = ['column' => 'payment_date', 'direction' => 'desc'];
    public $perPage = 20;

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
        $this->reset(['search', 'monthFilter', 'statusFilter', 'employeeFilter']);
    }

    public function render()
    {
        $payments = EmployeePayment::query()
            ->with(['employee', 'creator'])
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

        $employees = Employee::active()->orderBy('name')->get();

        $totalPaid = EmployeePayment::where('status', 'paid')
            ->sum('amount');

        $thisMonthPaid = EmployeePayment::where('status', 'paid')
            ->where('month_year', now()->format('Y-m'))
            ->sum('amount');

        return view('livewire.employee-payments', [
            'payments' => $payments,
            'employees' => $employees,
            'totalPaid' => $totalPaid,
            'thisMonthPaid' => $thisMonthPaid,
        ]);
    }
}
