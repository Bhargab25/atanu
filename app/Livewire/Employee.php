<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Employee as EmployeeModel;
use App\Models\CompanyProfile;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Storage;

class Employee extends Component
{
    use WithPagination, WithFileUploads, Toast;

    // Modal states
    public $myModal = false;
    public $showDrawer = false;

    // Form fields
    public $company_profile_id;
    public $name;
    public $email;
    public $phone;
    public $alternate_phone;
    public $address;
    public $city;
    public $state;
    public $postal_code;
    public $position;
    public $department;
    public $joining_date;
    public $salary_amount = 0;
    public $photo;
    public $document;
    public $is_active = true;
    public $notes;

    // Component state
    public $employeeId;
    public $isEdit = false;
    public $search = '';
    public $statusFilter = [];
    public $appliedStatusFilter = [];
    public $departmentFilter = [];
    public $appliedDepartmentFilter = [];
    public $companyFilter = [];
    public $appliedCompanyFilter = [];

    public $sortBy = ['column' => 'name', 'direction' => 'asc'];
    public $perPage = 10;
    public $selected = [];

    // Delete confirmation
    public $showDeleteModal = false;
    public $employeeToDelete = null;

    // Filter options
    public $statusOptions = [
        ['id' => 'active', 'name' => 'Active'],
        ['id' => 'inactive', 'name' => 'Inactive'],
    ];

    public $departmentOptions = [];
    public $companyOptions = [];

    protected $listeners = ['refreshEmployees' => '$refresh'];

    public function mount()
    {
        $this->joining_date = now()->format('Y-m-d');
        $this->loadFilterOptions();

        // Set default company if only one exists
        $companies = CompanyProfile::active()->get();
        if ($companies->count() === 1) {
            $this->company_profile_id = $companies->first()->id;
        }
    }

    private function loadFilterOptions()
    {
        $this->departmentOptions = EmployeeModel::whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->map(fn($dept) => ['id' => $dept, 'name' => $dept])
            ->toArray();

        $this->companyOptions = CompanyProfile::active()
            ->get()
            ->map(fn($company) => ['id' => $company->id, 'name' => $company->name])
            ->toArray();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updateSort($column, $direction = 'asc')
    {
        $this->sortBy = ['column' => $column, 'direction' => $direction];
        $this->resetPage();
    }

    public function resetSort()
    {
        $this->sortBy = ['column' => 'name', 'direction' => 'asc'];
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset([
            'statusFilter',
            'appliedStatusFilter',
            'departmentFilter',
            'appliedDepartmentFilter',
            'companyFilter',
            'appliedCompanyFilter'
        ]);
        $this->resetPage();
    }

    public function applyFilters()
    {
        $this->appliedStatusFilter = $this->statusFilter;
        $this->appliedDepartmentFilter = $this->departmentFilter;
        $this->appliedCompanyFilter = $this->companyFilter;
        $this->showDrawer = false;
        $this->resetPage();
        $this->success('Filters Applied!', 'Employees filtered successfully.');
    }

    public function newEmployee()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->myModal = true;
    }

    public function editEmployee($id)
    {
        $employee = EmployeeModel::with('company')->find($id);
        if ($employee) {
            $this->employeeId = $employee->id;
            $this->company_profile_id = $employee->company_profile_id;
            $this->name = $employee->name;
            $this->email = $employee->email;
            $this->phone = $employee->phone;
            $this->alternate_phone = $employee->alternate_phone;
            $this->address = $employee->address;
            $this->city = $employee->city;
            $this->state = $employee->state;
            $this->postal_code = $employee->postal_code;
            $this->position = $employee->position;
            $this->department = $employee->department;
            $this->joining_date = $employee->joining_date->format('Y-m-d');
            $this->salary_amount = $employee->salary_amount;
            $this->is_active = $employee->is_active;
            $this->notes = $employee->notes;
            $this->isEdit = true;
            $this->myModal = true;
        }
    }

    public function saveEmployee()
    {
        $rules = [
            'company_profile_id' => 'required|exists:company_profiles,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:employees,email,' . $this->employeeId,
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'position' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'joining_date' => 'required|date',
            'salary_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        if (!$this->isEdit) {
            $rules['photo'] = 'nullable|image|max:2048';
            $rules['document'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120';
        } else {
            $rules['photo'] = 'nullable|image|max:2048';
            $rules['document'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120';
        }

        $this->validate($rules);

        $data = [
            'company_profile_id' => $this->company_profile_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'alternate_phone' => $this->alternate_phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'position' => $this->position,
            'department' => $this->department,
            'joining_date' => $this->joining_date,
            'salary_amount' => $this->salary_amount,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
        ];

        if ($this->isEdit && $this->employeeId) {
            $employee = EmployeeModel::find($this->employeeId);

            // Handle photo upload
            if ($this->photo) {
                if ($employee->photo_path && Storage::disk('public')->exists($employee->photo_path)) {
                    Storage::disk('public')->delete($employee->photo_path);
                }
                $data['photo_path'] = $this->photo->store('employees/photos', 'public');
            }

            // Handle document upload
            if ($this->document) {
                if ($employee->document_path && Storage::disk('public')->exists($employee->document_path)) {
                    Storage::disk('public')->delete($employee->document_path);
                }
                $data['document_path'] = $this->document->store('employees/documents', 'public');
            }

            $employee->update($data);
            $this->success('Employee Updated!', 'The employee has been updated successfully.');
        } else {
            // Generate employee ID
            $data['employee_id'] = EmployeeModel::generateEmployeeId($this->company_profile_id);

            // Handle file uploads
            if ($this->photo) {
                $data['photo_path'] = $this->photo->store('employees/photos', 'public');
            }
            if ($this->document) {
                $data['document_path'] = $this->document->store('employees/documents', 'public');
            }

            EmployeeModel::create($data);
            $this->success('Employee Created!', 'The employee has been added successfully.');
        }

        $this->resetForm();
        $this->myModal = false;
        $this->loadFilterOptions();
        $this->dispatch('refreshEmployees');
    }

    public function confirmDelete($id)
    {
        $this->employeeToDelete = EmployeeModel::find($id);
        $this->showDeleteModal = true;
    }

    public function deleteEmployee()
    {
        if ($this->employeeToDelete) {
            $employeeName = $this->employeeToDelete->name;

            // Delete files
            if ($this->employeeToDelete->photo_path && Storage::disk('public')->exists($this->employeeToDelete->photo_path)) {
                Storage::disk('public')->delete($this->employeeToDelete->photo_path);
            }
            if ($this->employeeToDelete->document_path && Storage::disk('public')->exists($this->employeeToDelete->document_path)) {
                Storage::disk('public')->delete($this->employeeToDelete->document_path);
            }

            $this->employeeToDelete->delete();
            $this->success('Employee Deleted!', "'{$employeeName}' has been removed successfully.");
            $this->closeDeleteModal();
            $this->dispatch('refreshEmployees');
        }
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->employeeToDelete = null;
    }

    public function toggleStatus()
    {
        foreach ($this->selected as $id) {
            $employee = EmployeeModel::find($id);
            if ($employee) {
                $employee->is_active = !$employee->is_active;
                $employee->save();
            }
        }
        $this->success('Employees Updated!', count($this->selected) . ' employees status toggled.');
        $this->dispatch('refreshEmployees');
        $this->reset('selected');
    }

    public function resetForm()
    {
        $this->reset([
            'employeeId',
            'company_profile_id',
            'name',
            'email',
            'phone',
            'alternate_phone',
            'address',
            'city',
            'state',
            'postal_code',
            'position',
            'department',
            'salary_amount',
            'notes',
            'photo',
            'document'
        ]);
        $this->joining_date = now()->format('Y-m-d');
        $this->is_active = true;

        // Set default company if only one exists
        $companies = CompanyProfile::active()->get();
        if ($companies->count() === 1) {
            $this->company_profile_id = $companies->first()->id;
        }
    }

    public function cancel()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->myModal = false;
    }

    public function clearSearch()
    {
        $this->reset('search');
    }

    public function render()
    {
        $employees = EmployeeModel::with('company')
            ->when($this->search, function ($query) {
                return $query->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('employee_id', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%')
                        ->orWhere('position', 'like', '%' . $this->search . '%')
                        ->orWhere('department', 'like', '%' . $this->search . '%')
                        ->orWhereHas('company', function ($companyQuery) {
                            $companyQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when(!empty($this->appliedStatusFilter), function ($query) {
                if (in_array('active', $this->appliedStatusFilter) && !in_array('inactive', $this->appliedStatusFilter)) {
                    return $query->where('is_active', true);
                }
                if (in_array('inactive', $this->appliedStatusFilter) && !in_array('active', $this->appliedStatusFilter)) {
                    return $query->where('is_active', false);
                }
                return $query;
            })
            ->when(!empty($this->appliedDepartmentFilter), function ($query) {
                return $query->whereIn('department', $this->appliedDepartmentFilter);
            })
            ->when(!empty($this->appliedCompanyFilter), function ($query) {
                return $query->whereIn('company_profile_id', $this->appliedCompanyFilter);
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);

        $headers = [
            ['label' => 'Photo', 'key' => 'photo', 'sortable' => false],
            ['label' => 'Employee ID', 'key' => 'employee_id', 'sortable' => true],
            ['label' => 'Name', 'key' => 'name', 'sortable' => true],
            ['label' => 'Company', 'key' => 'company', 'sortable' => false],
            ['label' => 'Contact', 'key' => 'phone', 'sortable' => false],
            ['label' => 'Position', 'key' => 'position', 'sortable' => true],
            ['label' => 'Department', 'key' => 'department', 'sortable' => true],
            ['label' => 'Salary', 'key' => 'salary_amount', 'sortable' => true],
            ['label' => 'Status', 'key' => 'is_active', 'sortable' => false],
            ['label' => 'Joining Date', 'key' => 'joining_date', 'sortable' => true],
            ['label' => 'Actions', 'key' => 'actions', 'sortable' => false],
        ];

        $row_decoration = [
            'bg-warning/20' => fn($employee) => !$employee->is_active,
            'text-error' => fn($employee) => $employee->is_active === 0,
        ];

        return view('livewire.employee', [
            'employees' => $employees,
            'headers' => $headers,
            'row_decoration' => $row_decoration,
        ]);
    }
}
