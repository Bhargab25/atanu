<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ProductCategory as Products;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;


class ProductCategory extends Component
{
    use WithPagination;
    use Toast;

    public $myModal2 = false;
    public $showDrawer = false;
    public $name;
    public $description;
    public $is_active = true;
    public $isEdit = false;
    public $search = '';
    public $statusFilter = [];
    public $appliedStatusFilter = [];
    public $statusOptions = [
        ['id' => 'active', 'name' => 'Active'],
        ['id' => 'inactive', 'name' => 'Inactive'],
    ];

    public $categoryId;
    public $sortBy = ['column' => 'name', 'direction' => 'asc'];
    public $sortDirection = 'asc';
    public $perPage = 5;
    public $selected = [];
    public $filter = 'all'; // 'all', 'active', 'inactive'

    public $showDeleteCategoryModal = false;
    public $categoryToDelete = null;
    public $categoryDeleteError = '';
    public $categoryDeleteConfirmation = '';


    protected $listeners = ['refreshCategories' => '$refresh'];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            // Toggle direction
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters()
    {
        $this->reset(['statusFilter', 'appliedStatusFilter']);
        // $this->showDrawer = false;
        $this->resetPage();
    }

    public function applyFilters()
    {
        $this->appliedStatusFilter = $this->statusFilter;
        $this->showDrawer = false;
        $this->resetPage();

        // Show success message
        $this->success('Filters Applied!', 'Categories filtered successfully.');
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
    public function mount() {}

    public function toggle()
    {
        $this->myModal2 = !$this->myModal2;
    }

    public function newCategory()
    {
        $this->reset(['categoryId', 'name', 'description', 'is_active']);
        $this->isEdit = false;
        $this->myModal2 = true;
    }

     public function confirmDeleteCategory($id)
    {
        $this->categoryToDelete = Products::find($id);
        $this->categoryDeleteError = '';
        $this->categoryDeleteConfirmation = '';
        
        if ($this->categoryToDelete) {
            // Check if category can be deleted
            $canDelete = $this->checkCategoryCanBeDeleted($id);
            
            if (!$canDelete['can_delete']) {
                $this->categoryDeleteError = $canDelete['message'];
            }
            
            $this->showDeleteCategoryModal = true;
        }
    }

    public function deleteCategory()
    {
        if (!$this->categoryToDelete) {
            $this->error('Category not found.');
            return;
        }

        try {
            // Double-check constraints before deletion
            $canDelete = $this->checkCategoryCanBeDeleted($this->categoryToDelete->id);
            
            if (!$canDelete['can_delete']) {
                $this->error($canDelete['message']);
                $this->closeDeleteCategoryModal();
                return;
            }

            // Perform the deletion
            $categoryName = $this->categoryToDelete->name;
            $this->categoryToDelete->delete();
            
            $this->success('Service Deleted!', "'{$categoryName}' has been removed successfully.");
            $this->closeDeleteCategoryModal();
            $this->dispatch('refreshCategories');
            
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle foreign key constraint violation
            if ($e->getCode() == '23000') {
                $this->error('Cannot delete this Service!', 'This Service has items assigned to it.');
            } else {
                $this->error('Error occurred while deleting the service.');
            }
            $this->closeDeleteCategoryModal();
        } catch (\Exception $e) {
            $this->error('An unexpected error occurred.');
            $this->closeDeleteCategoryModal();
        }
    }

    public function closeDeleteCategoryModal()
    {
        $this->showDeleteCategoryModal = false;
        $this->categoryToDelete = null;
        $this->categoryDeleteError = '';
        $this->categoryDeleteConfirmation = '';
    }

    private function checkCategoryCanBeDeleted($categoryId)
    {
        $constraints = [];
        $canDelete = true;

        // Check if category has products
        $productsCount = DB::table('item_master')
            ->where('service_id', $categoryId)
            ->count();
        
        if ($productsCount > 0) {
            $constraints[] = "Has {$productsCount} item(s) assigned";
            $canDelete = false;
        }

        $message = '';
        if (!$canDelete) {
            $message = "Cannot delete this service because it:\n• " . implode("\n• ", $constraints);
        }

        return [
            'can_delete' => $canDelete,
            'message' => $message,
            'constraints' => $constraints
        ];
    }

    public function makeDisable()
    {
        foreach ($this->selected as $id) {
            $this->toggleActive($id);
        }
        $this->dispatch('refreshCategories');
        $this->reset('selected');
    }
    public function toggleActive($id)
    {
        $category = Products::find($id);
        if ($category) {
            $category->is_active = !$category->is_active;
            $category->save();
        }
    }

    public function editCategory($id)
    {
        $category = Products::find($id);
        if ($category) {
            $this->categoryId = $category->id;
            $this->name = $category->name;
            $this->description = $category->description;
            $this->is_active = $category->is_active;
            $this->isEdit = true;
            $this->myModal2 = true;
        }
    }

    public function saveCategory()
    {
        $this->validate([
            'name' => 'required|unique:service_master,name,' . $this->categoryId,
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($this->isEdit && $this->categoryId) {
            // Update
            $category = Products::find($this->categoryId);
            if ($category) {
                $category->update([
                    'name' => $this->name,
                    'description' => $this->description,
                    'is_active' => $this->is_active,
                ]);
                $this->toast(
                    type: 'success',
                    title: 'Service Updated!',
                    description: 'The Service has been updated successfully.',
                    position: 'toast-top toast-end',
                    icon: 'o-check-circle',
                    timeout: 3000
                );
            }
        } else {
            // Create
            Products::create([
                'name' => $this->name,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
        }
        $this->toast(
            type: 'success',
            title: 'Service Created!',
            description: 'The Service has been added successfully.',
            position: 'toast-top toast-end',
            icon: 'o-check-circle',
            timeout: 3000
        );
        $this->reset(['name', 'description', 'is_active', 'categoryId']);
        $this->myModal2 = false;
        $this->dispatch('refreshCategories');
    }



    public function cancel()
    {
        $this->reset(['name', 'description', 'is_active']);
        // $this->myModal2 = false;
        $this->isEdit = false;
    }

    public function clearSearch()
    {
        $this->reset('search');
    }

    public function render()
    {
        $categories = Products::query()
            // Existing search functionality
            ->when($this->search, function ($query) {
                return $query->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            // Applied status filter from drawer (use appliedStatusFilter instead of statusFilter)
            ->when(!empty($this->appliedStatusFilter), function ($query) {
                if (in_array('active', $this->appliedStatusFilter) && !in_array('inactive', $this->appliedStatusFilter)) {
                    return $query->where('is_active', true);
                }
                if (in_array('inactive', $this->appliedStatusFilter) && !in_array('active', $this->appliedStatusFilter)) {
                    return $query->where('is_active', false);
                }
                // If both selected, show all
                return $query;
            })
            // Existing dropdown filter functionality (only if no drawer filter is applied)
            ->when(empty($this->appliedStatusFilter) && $this->filter === 'active', function ($query) {
                return $query->where('is_active', true);
            })
            ->when(empty($this->appliedStatusFilter) && $this->filter === 'inactive', function ($query) {
                return $query->where('is_active', false);
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);

        $headers = [
            ['label' => 'ID', 'key' => 'id', 'sortable' => false],
            ['label' => 'Name', 'key' => 'name', 'sortable' => true],
            ['label' => 'Description', 'key' => 'description', 'sortable' => false],
            ['label' => 'Status', 'key' => 'is_active', 'sortable' => false,],
            ['label' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'format' => ['date', 'd/m/Y']],
            ['label' => 'Actions', 'key' => 'actions', 'type' => 'button', 'sortable' => false],
        ];
        $row_decoration = [
            'bg-warning/20' => fn($category) => !$category->is_active,
            'text-error' => fn($category) => $category->is_active === 0,
        ];
        return view('livewire.product-category', [
            'categories' => $categories,
            'headers' => $headers,
            'row_decoration' => $row_decoration,
        ]);
    }
}
