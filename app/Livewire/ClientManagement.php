<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Client;
use App\Models\ProductCategory;
use App\Models\Product;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class ClientManagement extends Component
{
    use WithPagination, Toast;

    // Modal states
    public $myModal = false;
    public $showDrawer = false;

    // Form fields
    public $name;
    public $company_name;
    public $email;
    public $phone;
    public $alternate_phone;
    public $address;
    public $city;
    public $state;
    public $postal_code;
    public $gstin;
    public $is_active = true;
    public $notes;

    // Services and Items
    public $selectedServices = [];
    public $serviceItems = []; // Items for each service
    public $availableServices = [];

    // Component state
    public $clientId;
    public $isEdit = false;
    public $search = '';
    public $statusFilter = [];
    public $appliedStatusFilter = [];

    public $sortBy = ['column' => 'name', 'direction' => 'asc'];
    public $perPage = 10;
    public $selected = [];

    // Delete confirmation
    public $showDeleteModal = false;
    public $clientToDelete = null;

    // Filter options
    public $statusOptions = [
        ['id' => 'active', 'name' => 'Active'],
        ['id' => 'inactive', 'name' => 'Inactive'],
    ];

    protected $listeners = ['refreshClients' => '$refresh'];

    public function mount()
    {
        $this->loadServices();
    }

    private function loadServices()
    {
        $this->availableServices = ProductCategory::with('activeItems')
            ->active()
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'items' => $service->activeItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'description' => $item->description,
                        ];
                    })->toArray()
                ];
            })
            ->toArray();
    }

    public function toggleProductSelection($serviceId, $itemId)
    {
        if (!isset($this->serviceItems[$serviceId])) {
            $service = collect($this->availableServices)->firstWhere('id', $serviceId);
            $this->serviceItems[$serviceId] = [
                'service_name' => $service['name'],
                'items' => []
            ];
        }

        $item = collect($this->availableServices)
            ->firstWhere('id', $serviceId)['items'];
        $item = collect($item)->firstWhere('id', $itemId);

        if ($this->isProductSelected($serviceId, $itemId)) {
            // Remove product
            $this->serviceItems[$serviceId]['items'] = collect($this->serviceItems[$serviceId]['items'])
                ->reject(function ($product) use ($itemId) {
                    return $product['item_id'] == $itemId;
                })
                ->values()
                ->toArray();
        } else {
            // Add product
            $this->serviceItems[$serviceId]['items'][] = [
                'item_id' => $item['id'],
                'item_name' => $item['name'],
                'description' => $item['description'] ?? '',
                'quantity' => 1, // Default values for invoice generation
                'price' => 0.00   // Can be set during invoice creation
            ];
        }
    }

    public function isProductSelected($serviceId, $itemId)
    {
        if (!isset($this->serviceItems[$serviceId]['items'])) {
            return false;
        }

        return collect($this->serviceItems[$serviceId]['items'])
            ->pluck('item_id')
            ->contains($itemId);
    }

    public function updatedSelectedServices()
    {
        // Remove services that are no longer selected
        foreach (array_keys($this->serviceItems) as $existingServiceId) {
            if (!in_array($existingServiceId, $this->selectedServices)) {
                unset($this->serviceItems[$existingServiceId]);
            }
        }

        // Add newly selected services
        foreach ($this->selectedServices as $serviceId) {
            if (!isset($this->serviceItems[$serviceId])) {
                $service = collect($this->availableServices)->firstWhere('id', $serviceId);
                if ($service) {
                    $this->serviceItems[$serviceId] = [
                        'service_name' => $service['name'],
                        'items' => []
                    ];
                }
            }
        }
    }

    public function addServiceItem($serviceId, $itemId)
    {
        $service = collect($this->availableServices)->firstWhere('id', $serviceId);
        $item = collect($service['items'] ?? [])->firstWhere('id', $itemId);

        if ($item && !$this->itemExistsInService($serviceId, $itemId)) {
            if (!isset($this->serviceItems[$serviceId]['items'])) {
                $this->serviceItems[$serviceId]['items'] = [];
            }

            $this->serviceItems[$serviceId]['items'][] = [
                'item_id' => $item['id'],
                'item_name' => $item['name'],
                'quantity' => 1,
                'price' => 0.00,
                'description' => $item['description'] ?? ''
            ];
        }
    }

    public function removeServiceItem($serviceId, $index)
    {
        if (isset($this->serviceItems[$serviceId]['items'][$index])) {
            unset($this->serviceItems[$serviceId]['items'][$index]);
            // Re-index array
            $this->serviceItems[$serviceId]['items'] = array_values($this->serviceItems[$serviceId]['items']);
        }
    }

    private function itemExistsInService($serviceId, $itemId)
    {
        if (!isset($this->serviceItems[$serviceId]['items'])) {
            return false;
        }

        foreach ($this->serviceItems[$serviceId]['items'] as $item) {
            if ($item['item_id'] == $itemId) {
                return true;
            }
        }

        return false;
    }

    public function newClient()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->myModal = true;
    }

    public function editClient($id)
    {
        $client = Client::find($id);
        if ($client) {
            $this->clientId = $client->id;
            $this->name = $client->name;
            $this->company_name = $client->company_name;
            $this->email = $client->email;
            $this->phone = $client->phone;
            $this->alternate_phone = $client->alternate_phone;
            $this->address = $client->address;
            $this->city = $client->city;
            $this->state = $client->state;
            $this->postal_code = $client->postal_code;
            $this->gstin = $client->gstin;
            $this->is_active = $client->is_active;
            $this->notes = $client->notes;

            // Load existing services and items
            if ($client->services_items) {
                $this->selectedServices = array_keys($client->services_items);
                $this->serviceItems = $client->services_items;
            }

            $this->isEdit = true;
            $this->myModal = true;
        }
    }

    public function saveClient()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:clients,email,' . $this->clientId,
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'gstin' => 'nullable|string|max:15',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'selectedServices' => 'required|array|min:1',
        ];

        // Validate service items
        foreach ($this->serviceItems as $serviceId => $serviceData) {
            if (!empty($serviceData['items'])) {
                foreach ($serviceData['items'] as $index => $item) {
                    $rules["serviceItems.{$serviceId}.items.{$index}.quantity"] = 'required|numeric|min:0.01';
                    $rules["serviceItems.{$serviceId}.items.{$index}.price"] = 'required|numeric|min:0';
                }
            }
        }

        $this->validate($rules, [
            'selectedServices.required' => 'Please select at least one service.',
            'selectedServices.min' => 'Please select at least one service.',
        ]);

        $data = [
            'name' => $this->name,
            'company_name' => $this->company_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'alternate_phone' => $this->alternate_phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'gstin' => $this->gstin,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'services_items' => $this->serviceItems,
        ];

        if ($this->isEdit && $this->clientId) {
            $client = Client::find($this->clientId);
            $client->update($data);
            $this->success('Client Updated!', 'The client has been updated successfully.');
        } else {
            $data['client_id'] = Client::generateClientId();
            Client::create($data);
            $this->success('Client Created!', 'The client has been added successfully.');
        }

        $this->resetForm();
        $this->myModal = false;
        $this->dispatch('refreshClients');
    }

    public function confirmDelete($id)
    {
        $this->clientToDelete = Client::find($id);
        $this->showDeleteModal = true;
    }

    public function deleteClient()
    {
        if ($this->clientToDelete) {
            $clientName = $this->clientToDelete->name;
            $this->clientToDelete->delete();
            $this->success('Client Deleted!', "'{$clientName}' has been removed successfully.");
            $this->closeDeleteModal();
            $this->dispatch('refreshClients');
        }
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->clientToDelete = null;
    }

    public function toggleStatus()
    {
        foreach ($this->selected as $id) {
            $client = Client::find($id);
            if ($client) {
                $client->is_active = !$client->is_active;
                $client->save();
            }
        }
        $this->success('Clients Updated!', count($this->selected) . ' clients status toggled.');
        $this->dispatch('refreshClients');
        $this->reset('selected');
    }

    public function resetForm()
    {
        $this->reset([
            'clientId',
            'name',
            'company_name',
            'email',
            'phone',
            'alternate_phone',
            'address',
            'city',
            'state',
            'postal_code',
            'gstin',
            'notes',
            'selectedServices',
            'serviceItems'
        ]);
        $this->is_active = true;
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

    public function resetFilters()
    {
        $this->reset(['statusFilter', 'appliedStatusFilter']);
        $this->resetPage();
    }

    public function applyFilters()
    {
        $this->appliedStatusFilter = $this->statusFilter;
        $this->showDrawer = false;
        $this->resetPage();
        $this->success('Filters Applied!', 'Clients filtered successfully.');
    }

    public function updateSort($column, $direction = 'asc')
    {
        $this->sortBy = ['column' => $column, 'direction' => $direction];
        $this->resetPage();
    }

    public function render()
    {
        $clients = Client::query()
            ->when($this->search, function ($query) {
                return $query->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('client_id', 'like', '%' . $this->search . '%')
                        ->orWhere('company_name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%');
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
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);

        $headers = [
            ['label' => 'Client ID', 'key' => 'client_id', 'sortable' => true],
            ['label' => 'Name', 'key' => 'name', 'sortable' => true],
            ['label' => 'Company', 'key' => 'company_name', 'sortable' => true],
            ['label' => 'Contact', 'key' => 'phone', 'sortable' => false],
            ['label' => 'Services', 'key' => 'services', 'sortable' => false],
            ['label' => 'Total Amount', 'key' => 'total_amount', 'sortable' => false],
            ['label' => 'Status', 'key' => 'is_active', 'sortable' => false],
            ['label' => 'Created At', 'key' => 'created_at', 'sortable' => true],
            ['label' => 'Actions', 'key' => 'actions', 'sortable' => false],
        ];

        $row_decoration = [
            'bg-warning/20' => fn($client) => !$client->is_active,
        ];

        return view('livewire.client-management', [
            'clients' => $clients,
            'headers' => $headers,
            'row_decoration' => $row_decoration,
        ]);
    }
}
