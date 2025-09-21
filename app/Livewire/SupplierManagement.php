<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Supplier;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\AccountLedger;
use App\Models\LedgerTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SupplierManagement extends Component
{
    use WithPagination;
    use Toast;

    public $myModal = false;
    public $showDrawer = false;
    public $isEdit = false;
    public $search = '';
    public $statusFilter = [];
    public $appliedStatusFilter = [];
    public $locationFilter = [];
    public $appliedLocationFilter = [];
    public $showDetailsModal = false;
    public $selectedSupplier = null;
    public $showConfirmDeleteModal = false;
    public $confirmingDeleteId = null;
    public $transactionFilter = '10';
    public $isGeneratingPdf = false;

    // Basic Details
    public $name;
    public $contact_person;
    public $phone;
    public $email;

    // Address
    public $address;
    public $city;
    public $state;
    public $country;
    public $pincode;

    // Tax / Business Info
    public $gstin;
    public $pan;
    public $tin;

    // Banking Details
    public $bank_name;
    public $account_number;
    public $ifsc_code;
    public $branch;

    // Status
    public $is_active = true;

    public $supplierId;
    public $sortBy = ['column' => 'name', 'direction' => 'asc'];
    public $sortDirection = 'asc';
    public $perPage = 10;
    public $selected = [];
    public $filter = 'all';

    // Options for filters
    public $statusOptions = [
        ['id' => 'active', 'name' => 'Active'],
        ['id' => 'inactive', 'name' => 'Inactive'],
    ];

    public $activeTab = 'details';
    public $newTransaction = [
        'date' => '',
        'type' => 'purchase',
        'description' => '',
        'amount' => 0,
        'reference' => ''
    ];

    public $transactionFilterOptions = [
        '10' => 'Last 10 Transactions',
        '100' => 'Last 100 Transactions',
        'month' => 'Last Month',
        '3month' => 'Last 3 Months',
    ];

    // Statistics properties
    public $totalSuppliers = 0;
    public $activeSuppliers = 0;
    public $totalOutstanding = 0;
    public $totalPurchases = 0;

    public $locationOptions = [];

    protected $listeners = [
        'refreshSuppliers' => '$refresh',
        'pdfReady' => 'handlePdfReady'
    ];

    public function handlePdfReady($data)
    {
        $this->success(
            'PDF Ready!',
            'Your ledger PDF has been generated and is ready for download.'
        );

        // Dispatch browser event for download
        $this->dispatch('download-ready', [
            'url' => $data['download_url'],
            'filename' => $data['filename']
        ]);
    }

    public function mount()
    {
        // Load unique cities for location filter
        $this->locationOptions = Supplier::whereNotNull('city')
            ->distinct()
            ->pluck('city')
            ->filter()
            ->map(function ($city) {
                return ['id' => $city, 'name' => $city];
            })
            ->values()
            ->toArray();
        $this->calculateStatistics();
        $this->newTransaction = [
            'date' => now()->format('Y-m-d'),
            'type' => 'purchase',
            'description' => '',
            'amount' => 0,
            'reference' => ''
        ];
    }

    public function getFilteredTransactionsProperty()
    {
        if (!$this->selectedSupplier || !$this->selectedSupplier->ledger) {
            return collect();
        }

        $query = $this->selectedSupplier->ledger->transactions()->orderBy('date', 'desc');

        switch ($this->transactionFilter) {
            case '10':
                return $query->take(10)->get();
            case '100':
                return $query->take(100)->get();
            case 'month':
                return $query->where('date', '>=', now()->subMonth())->get();
            case '3month':
                return $query->where('date', '>=', now()->subMonths(3))->get();
            default:
                return $query->take(10)->get();
        }
    }

    // Method to trigger PDF download
    public function downloadLedgerPdf()
    {
        // Dispatch background job
        \App\Jobs\GenerateSupplierLedgerPdf::dispatch($this->selectedSupplier->id, Auth::id());

        // Show immediate feedback with Mary UI toast
        $this->success(
            'PDF Generation Started!',
            'Your ledger PDF is being prepared. You will receive a notification when ready.'
        );
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilter()
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
        $this->reset(['statusFilter', 'appliedStatusFilter', 'locationFilter', 'appliedLocationFilter']);
        $this->resetPage();
    }

    public function applyFilters()
    {
        $this->appliedStatusFilter = $this->statusFilter;
        $this->appliedLocationFilter = $this->locationFilter;
        $this->showDrawer = false;
        $this->resetPage();
        $this->success('Filters Applied!', 'Suppliers filtered successfully.');
    }

    public function newSupplier()
    {
        $this->reset([
            'supplierId',
            'name',
            'contact_person',
            'phone',
            'email',
            'address',
            'city',
            'state',
            'country',
            'pincode',
            'gstin',
            'pan',
            'tin',
            'bank_name',
            'account_number',
            'ifsc_code',
            'branch',
            'is_active'
        ]);
        $this->is_active = true;
        $this->isEdit = false;
        $this->myModal = true;
    }

    public function editSupplier($id)
    {
        $supplier = Supplier::find($id);
        if ($supplier) {
            $this->supplierId = $supplier->id;
            $this->name = $supplier->name;
            $this->contact_person = $supplier->contact_person;
            $this->phone = $supplier->phone;
            $this->email = $supplier->email;
            $this->address = $supplier->address;
            $this->city = $supplier->city;
            $this->state = $supplier->state;
            $this->country = $supplier->country;
            $this->pincode = $supplier->pincode;
            $this->gstin = $supplier->gstin;
            $this->pan = $supplier->pan;
            $this->tin = $supplier->tin;
            $this->bank_name = $supplier->bank_name;
            $this->account_number = $supplier->account_number;
            $this->ifsc_code = $supplier->ifsc_code;
            $this->branch = $supplier->branch;
            $this->is_active = $supplier->is_active;
            $this->isEdit = true;
            $this->myModal = true;
        }
    }


    public function saveSupplier()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:suppliers,email,' . $this->supplierId,
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:10',
            'gstin' => 'nullable|string|max:15',
            'pan' => 'nullable|string|max:10',
            'tin' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:11',
            'branch' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($this->isEdit && $this->supplierId) {
            // Update
            $supplier = Supplier::find($this->supplierId);
            if ($supplier) {
                $supplier->update([
                    'name' => $this->name,
                    'contact_person' => $this->contact_person,
                    'phone' => $this->phone,
                    'email' => $this->email,
                    'address' => $this->address,
                    'city' => $this->city,
                    'state' => $this->state,
                    'country' => $this->country,
                    'pincode' => $this->pincode,
                    'gstin' => $this->gstin,
                    'pan' => $this->pan,
                    'tin' => $this->tin,
                    'bank_name' => $this->bank_name,
                    'account_number' => $this->account_number,
                    'ifsc_code' => $this->ifsc_code,
                    'branch' => $this->branch,
                    'is_active' => $this->is_active,
                ]);
                $this->success('Supplier Updated!', 'The supplier has been updated successfully.');
            }
        } else {
            // Create
            Supplier::create([
                'name' => $this->name,
                'contact_person' => $this->contact_person,
                'phone' => $this->phone,
                'email' => $this->email,
                'address' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'pincode' => $this->pincode,
                'gstin' => $this->gstin,
                'pan' => $this->pan,
                'tin' => $this->tin,
                'bank_name' => $this->bank_name,
                'account_number' => $this->account_number,
                'ifsc_code' => $this->ifsc_code,
                'branch' => $this->branch,
                'is_active' => $this->is_active,
            ]);
            $this->success('Supplier Created!', 'The supplier has been added successfully.');
        }

        $this->reset([
            'name',
            'contact_person',
            'phone',
            'email',
            'address',
            'city',
            'state',
            'country',
            'pincode',
            'gstin',
            'pan',
            'tin',
            'bank_name',
            'account_number',
            'ifsc_code',
            'branch',
            'is_active',
            'supplierId'
        ]);
        $this->myModal = false;
        $this->isEdit = false;
        $this->dispatch('refreshSuppliers');
    }

    public function showDetails($id)
    {

        // Always reload with proper eager loading
        $supplier = Supplier::with(['ledger.transactions'])->find($id);

        if ($supplier) {
            // Create ledger if it doesn't exist
            if (!$supplier->ledger) {
                $supplier->ledger()->create([
                    'ledger_name' => $supplier->name,
                    'ledger_type' => 'supplier',
                    'opening_balance' => 0,
                    'opening_balance_type' => 'debit',
                    'current_balance' => 0,
                    'is_active' => true,
                ]);

                // Reload the supplier with the new ledger and transactions
                $supplier = Supplier::with(['ledger.transactions'])->find($id);
            }

            $this->selectedSupplier = $supplier;
            $this->activeTab = 'details';
            $this->showDetailsModal = true;
        }
    }

    public function hydrateSelectedSupplier()
    {
        if ($this->selectedSupplier && $this->selectedSupplier->exists) {
            // Reload the relationships if they're missing
            if (!$this->selectedSupplier->relationLoaded('ledger')) {
                $this->selectedSupplier->load('ledger.transactions');
            } elseif ($this->selectedSupplier->ledger && !$this->selectedSupplier->ledger->relationLoaded('transactions')) {
                $this->selectedSupplier->ledger->load('transactions');
            }
        }
    }


    // Add the Add Transaction tab content
    public function addTransaction()
    {
        $this->validate([
            'newTransaction.date' => 'required|date',
            'newTransaction.type' => 'required|in:purchase,payment,return,adjustment',
            'newTransaction.description' => 'required|string|max:255',
            'newTransaction.amount' => 'required|numeric|min:0',
            'newTransaction.reference' => 'nullable|string|max:100',
        ]);

        if ($this->selectedSupplier && $this->selectedSupplier->ledger) {
            $ledger = $this->selectedSupplier->ledger;

            // Determine debit/credit based on transaction type
            $debitAmount = 0;
            $creditAmount = 0;

            switch ($this->newTransaction['type']) {
                case 'purchase':
                    $debitAmount = $this->newTransaction['amount']; // Increase supplier balance
                    break;
                case 'payment':
                    $creditAmount = $this->newTransaction['amount']; // Decrease supplier balance
                    break;
                case 'return':
                    $creditAmount = $this->newTransaction['amount']; // Decrease supplier balance
                    break;
                case 'adjustment':
                    $debitAmount = $this->newTransaction['amount']; // Adjust balance
                    break;
            }

            // Create transaction
            $ledger->transactions()->create([
                'date' => $this->newTransaction['date'],
                'type' => $this->newTransaction['type'],
                'description' => $this->newTransaction['description'],
                'debit_amount' => $debitAmount,
                'credit_amount' => $creditAmount,
                'reference' => $this->newTransaction['reference'],
            ]);

            // Reset form
            $this->reset('newTransaction');
            $this->newTransaction = [
                'date' => now()->format('Y-m-d'),
                'type' => 'purchase',
                'description' => '',
                'amount' => 0,
                'reference' => ''
            ];

            // Refresh data
            $this->selectedSupplier = Supplier::with(['ledger.transactions'])->find($this->selectedSupplier->id);
            $this->success('Transaction added successfully!');
        }
    }

    public function calculateStatistics()
    {
        $this->totalSuppliers = Supplier::count();
        $this->activeSuppliers = Supplier::where('is_active', true)->count();

        // Calculate totals from polymorphic ledgers
        $supplierLedgers = AccountLedger::where('ledger_type', 'supplier')->get();
        $this->totalOutstanding = $supplierLedgers->where('current_balance', '>', 0)->sum('current_balance');
        $this->totalPurchases = LedgerTransaction::whereIn('ledger_id', $supplierLedgers->pluck('id'))
            ->where('type', 'purchase')
            ->sum('debit_amount');
    }
    public function confirmDeletion($id)
    {
        $this->confirmingDeleteId = $id;
        $this->showConfirmDeleteModal = true;
    }
    public function deleteSupplier()
    {
        if ($this->confirmingDeleteId) {
            $supplier = Supplier::find($this->confirmingDeleteId);
            if ($supplier) {
                $supplier->delete(); // This will soft delete
                $this->success('Supplier Deleted!', 'The supplier has been removed successfully.');
                $this->dispatch('refreshSuppliers');
            }
            $this->showConfirmDeleteModal = false;
            $this->confirmingDeleteId = null;
        }
    }
    public function cancelDeletion()
    {
        $this->showConfirmDeleteModal = false;
        $this->confirmingDeleteId = null;
    }

    public function toggleStatus()
    {
        foreach ($this->selected as $id) {
            $this->toggleActive($id);
        }
        $this->success('Suppliers Updated!', count($this->selected) . ' suppliers status toggled.');
        $this->dispatch('refreshSuppliers');
        $this->reset('selected');
    }

    public function toggleActive($id)
    {
        $supplier = Supplier::find($id);
        if ($supplier) {
            $supplier->is_active = !$supplier->is_active;
            $supplier->save();
        }
    }

    public function cancel()
    {
        $this->reset([
            'name',
            'contact_person',
            'phone',
            'email',
            'address',
            'city',
            'state',
            'country',
            'pincode',
            'gstin',
            'pan',
            'tin',
            'bank_name',
            'account_number',
            'ifsc_code',
            'branch',
            'is_active'
        ]);
        $this->isEdit = false;
        $this->myModal = false;
    }

    public function clearSearch()
    {
        $this->reset('search');
    }

    public function render()
    {
        $suppliers = Supplier::query()
            // Search functionality
            ->when($this->search, function ($query) {
                return $query->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('contact_person', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('city', 'like', '%' . $this->search . '%')
                        ->orWhere('gstin', 'like', '%' . $this->search . '%');
                });
            })
            // Status filter
            ->when(!empty($this->appliedStatusFilter), function ($query) {
                if (in_array('active', $this->appliedStatusFilter) && !in_array('inactive', $this->appliedStatusFilter)) {
                    return $query->where('is_active', true);
                }
                if (in_array('inactive', $this->appliedStatusFilter) && !in_array('active', $this->appliedStatusFilter)) {
                    return $query->where('is_active', false);
                }
                return $query;
            })
            // Location filter
            ->when(!empty($this->appliedLocationFilter), function ($query) {
                return $query->whereIn('city', $this->appliedLocationFilter);
            })
            // Dropdown filter (fallback)
            ->when(empty($this->appliedStatusFilter) && $this->filter === 'active', function ($query) {
                return $query->where('is_active', true);
            })
            ->when(empty($this->appliedStatusFilter) && $this->filter === 'inactive', function ($query) {
                return $query->where('is_active', false);
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);

        $startIndex = ($suppliers->currentPage() - 1) * $suppliers->perPage();

        $headers = [
            ['label' => 'Sl No.', 'key' => 'sl_no', 'sortable' => false],
            ['label' => 'Name', 'key' => 'name', 'sortable' => true],
            ['label' => 'Contact Person', 'key' => 'contact_person', 'sortable' => false],
            ['label' => 'Phone', 'key' => 'phone', 'sortable' => false],
            ['label' => 'GSTIN', 'key' => 'gstin', 'sortable' => false],
            ['label' => 'Status', 'key' => 'is_active', 'sortable' => false],
            ['label' => 'Actions', 'key' => 'actions', 'type' => 'button', 'sortable' => false],
        ];

        $row_decoration = [
            'bg-warning/20' => fn($supplier) => !$supplier->is_active,
            'text-error' => fn($supplier) => $supplier->is_active === 0,
        ];

        return view('livewire.supplier-management', [
            'suppliers' => $suppliers,
            'headers' => $headers,
            'row_decoration' => $row_decoration,
            'startIndex' => $startIndex,
            'selectedSupplier' => $this->selectedSupplier, // Explicitly pass this
            'activeTab' => $this->activeTab,
        ]);
    }
}
