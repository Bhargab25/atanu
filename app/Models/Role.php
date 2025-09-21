<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
        'is_active',
        'is_system_role'
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'is_system_role' => 'boolean',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Check if role has permission
    public function hasPermission($permission)
    {
        return in_array($permission, $this->permissions ?? []);
    }

    // Available permissions
    public static function availablePermissions()
    {
        return [
            'users' => [
                'users.view' => 'View Users',
                'users.create' => 'Create Users',
                'users.edit' => 'Edit Users',
                'users.delete' => 'Delete Users',
                'users.manage_roles' => 'Manage User Roles',
            ],
            'clients' => [
                'clients.view' => 'View Clients',
                'clients.create' => 'Create Clients',
                'clients.edit' => 'Edit Clients',
                'clients.delete' => 'Delete Clients',
            ],
            'products' => [
                'products.view' => 'View Products',
                'products.create' => 'Create Products',
                'products.edit' => 'Edit Products',
                'products.delete' => 'Delete Products',
                'products.manage_categories' => 'Manage Categories',
            ],
            'invoices' => [
                'invoices.view' => 'View Invoices',
                'invoices.create' => 'Create Invoices',
                'invoices.edit' => 'Edit Invoices',
                'invoices.delete' => 'Delete Invoices',
                'invoices.manage_payments' => 'Manage Payments',
            ],
            'expenses' => [
                'expenses.view' => 'View Expenses',
                'expenses.create' => 'Create Expenses',
                'expenses.edit' => 'Edit Expenses',
                'expenses.delete' => 'Delete Expenses',
                'expenses.approve' => 'Approve Expenses',
            ],
            'reports' => [
                'reports.view' => 'View Reports',
                'reports.export' => 'Export Reports',
                'reports.financial' => 'Financial Reports',
            ],
            'settings' => [
                'settings.view' => 'View Settings',
                'settings.edit' => 'Edit Settings',
                'settings.company' => 'Company Settings',
                'settings.system' => 'System Settings',
                'settings.backup' => 'Backup Management',
            ]
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotSystem($query)
    {
        return $query->where('is_system_role', false);
    }
}
