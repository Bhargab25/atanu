<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product as Products;
use App\Models\ProductCategory;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Product extends Component
{
    use WithPagination;
    use Toast;

    public $myModal = false;
    public $showDrawer = false;

    // Form fields matching your table structure
    public $name;
    public $service_id; // Changed from category_id to match your table
    public $description;
    public $is_active = true;

    // Component state
    public $isEdit = false;
    public $search = '';
    public $statusFilter = [];
    public $appliedStatusFilter = [];
    public $categoryFilter = [];
    public $appliedCategoryFilter = [];

    public $productId;
    public $sortBy = ['column' => 'name', 'direction' => 'asc'];
    public $sortDirection = 'asc';
    public $perPage = 10;
    public $selected = [];
    public $filter = 'all';

    public $showDeleteModal = false;
    public $productToDelete = null;
    public $deleteError = '';

    // Options for filters
    public $statusOptions = [
        ['id' => 'active', 'name' => 'Active'],
        ['id' => 'inactive', 'name' => 'Inactive'],
    ];

    public $categoryOptions = [];

    protected $listeners = ['refreshProducts' => '$refresh'];

    public function mount()
    {
        // Load categories for filter options
        $this->categoryOptions = ProductCategory::where('is_active', true)
            ->get()
            ->map(function ($category) {
                return ['id' => $category->id, 'name' => $category->name];
            })
            ->toArray();
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
        $this->reset(['statusFilter', 'appliedStatusFilter', 'categoryFilter', 'appliedCategoryFilter']);
        $this->resetPage();
    }

    public function applyFilters()
    {
        $this->appliedStatusFilter = $this->statusFilter;
        $this->appliedCategoryFilter = $this->categoryFilter;
        $this->showDrawer = false;
        $this->resetPage();
        $this->success('Filters Applied!', 'Products filtered successfully.');
    }

    public function newProduct()
    {
        $this->reset(['productId', 'name', 'service_id', 'description', 'is_active']);
        $this->isEdit = false;
        $this->myModal = true;
    }

    public function editProduct($id)
    {
        $product = Products::find($id);
        if ($product) {
            $this->productId = $product->id;
            $this->name = $product->name;
            $this->service_id = $product->service_id;
            $this->description = $product->description;
            $this->is_active = $product->is_active;
            $this->isEdit = true;
            $this->myModal = true;
        }
    }

    public function saveProduct()
    {
        $this->validate([
            'name' => 'required|unique:item_master,name,' . $this->productId,
            'service_id' => 'required|exists:service_master,id',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        if ($this->isEdit && $this->productId) {
            // Update
            $product = Products::find($this->productId);
            if ($product) {
                $product->update([
                    'name' => $this->name,
                    'service_id' => $this->service_id,
                    'description' => $this->description,
                    'is_active' => $this->is_active,
                ]);
                $this->success('Product Updated!', 'The product has been updated successfully.');
            }
        } else {
            // Create
            Products::create([
                'name' => $this->name,
                'service_id' => $this->service_id,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
            $this->success('Product Created!', 'The product has been added successfully.');
        }

        $this->reset(['name', 'service_id', 'description', 'is_active', 'productId']);
        $this->myModal = false;
        $this->isEdit = false;
        $this->dispatch('refreshProducts');
    }

    public function confirmDelete($id)
    {
        $this->productToDelete = Products::find($id);
        $this->deleteError = '';

        if ($this->productToDelete) {
            // Check if product is used in other tables
            $canDelete = $this->checkProductCanBeDeleted($id);

            if (!$canDelete['can_delete']) {
                $this->deleteError = $canDelete['message'];
            }

            $this->showDeleteModal = true;
        }
    }

    public function deleteProduct()
    {
        if (!$this->productToDelete) {
            $this->error('Product not found.');
            return;
        }

        try {
            // Double-check constraints before deletion
            $canDelete = $this->checkProductCanBeDeleted($this->productToDelete->id);

            if (!$canDelete['can_delete']) {
                $this->error($canDelete['message']);
                $this->closeDeleteModal();
                return;
            }

            // Perform the deletion
            $productName = $this->productToDelete->name;
            $this->productToDelete->delete();

            $this->success('Product Deleted!', "'{$productName}' has been removed successfully.");
            $this->closeDeleteModal();
            $this->dispatch('refreshProducts');
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle foreign key constraint violation
            if ($e->getCode() == '23000') {
                $this->error('Cannot delete this product!', 'This product is being used in invoices, stock movements, or other records.');
            } else {
                $this->error('Error occurred while deleting the product.');
            }
            $this->closeDeleteModal();
        } catch (\Exception $e) {
            $this->error('An unexpected error occurred.');
            $this->closeDeleteModal();
        }
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->productToDelete = null;
        $this->deleteError = '';
    }

    private function checkProductCanBeDeleted($productId)
    {
        $constraints = [];
        $canDelete = true;

        // Check invoice items
        if (Schema::hasTable('invoice_items')) {
            $invoiceItemsCount = DB::table('invoice_items')
                ->where('product_id', $productId)
                ->count();

            if ($invoiceItemsCount > 0) {
                $constraints[] = "Used in {$invoiceItemsCount} invoice item(s)";
                $canDelete = false;
            }
        }

        // Check stock movements
        if (Schema::hasTable('stock_movements')) {
            $stockMovementsCount = DB::table('stock_movements')
                ->where('product_id', $productId)
                ->count();

            if ($stockMovementsCount > 0) {
                $constraints[] = "Has {$stockMovementsCount} stock movement record(s)";
                $canDelete = false;
            }
        }

        // Check other related tables as needed
        if (Schema::hasTable('purchase_order_items')) {
            $purchaseOrderItemsCount = DB::table('purchase_order_items')
                ->where('product_id', $productId)
                ->count();

            if ($purchaseOrderItemsCount > 0) {
                $constraints[] = "Used in {$purchaseOrderItemsCount} purchase order item(s)";
                $canDelete = false;
            }
        }

        $message = '';
        if (!$canDelete) {
            $message = "Cannot delete this product because it is:\n• " . implode("\n• ", $constraints);
        }

        return [
            'can_delete' => $canDelete,
            'message' => $message,
            'constraints' => $constraints
        ];
    }

    public function toggleStatus()
    {
        foreach ($this->selected as $id) {
            $this->toggleActive($id);
        }
        $this->success('Products Updated!', count($this->selected) . ' products status toggled.');
        $this->dispatch('refreshProducts');
        $this->reset('selected');
    }

    public function toggleActive($id)
    {
        $product = Products::find($id);
        if ($product) {
            $product->is_active = !$product->is_active;
            $product->save();
        }
    }

    public function cancel()
    {
        $this->reset(['name', 'service_id', 'description', 'is_active']);
        $this->isEdit = false;
        $this->myModal = false;
    }

    public function clearSearch()
    {
        $this->reset('search');
    }

    public function render()
    {
        $products = Products::query()
            ->with('category')
            // Search functionality
            ->when($this->search, function ($query) {
                return $query->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhereHas('category', function ($categoryQuery) {
                            $categoryQuery->where('name', 'like', '%' . $this->search . '%');
                        });
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
            // Category filter
            ->when(!empty($this->appliedCategoryFilter), function ($query) {
                return $query->whereIn('service_id', $this->appliedCategoryFilter);
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

        $headers = [
            ['label' => 'ID', 'key' => 'id', 'sortable' => false],
            ['label' => 'Name', 'key' => 'name', 'sortable' => true],
            ['label' => 'Category', 'key' => 'category.name', 'sortable' => true],
            ['label' => 'Description', 'key' => 'description', 'sortable' => false],
            ['label' => 'Status', 'key' => 'is_active', 'sortable' => false],
            ['label' => 'Created At', 'key' => 'created_at', 'sortable' => true, 'format' => ['date', 'd/m/Y']],
            ['label' => 'Actions', 'key' => 'actions', 'type' => 'button', 'sortable' => false],
        ];

        $row_decoration = [
            'bg-warning/20' => fn($product) => !$product->is_active,
            'text-error' => fn($product) => $product->is_active === 0,
        ];

        return view('livewire.product', [
            'products' => $products,
            'headers' => $headers,
            'row_decoration' => $row_decoration,
            'categories' => ProductCategory::where('is_active', true)->get(),
        ]);
    }
}
