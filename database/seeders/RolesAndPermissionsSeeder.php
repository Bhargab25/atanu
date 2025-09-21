<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Create Super Admin Role
        $superAdmin = Role::create([
            'name' => 'super_admin',
            'display_name' => 'Super Administrator',
            'description' => 'Full system access with all permissions',
            'is_system_role' => true,
            'permissions' => array_keys(collect(Role::availablePermissions())->flatten(1)->toArray())
        ]);

        // Create Admin Role
        $admin = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Administrative access with most permissions',
            'permissions' => [
                'users.view',
                'users.create',
                'users.edit',
                'clients.view',
                'clients.create',
                'clients.edit',
                'clients.delete',
                'products.view',
                'products.create',
                'products.edit',
                'products.delete',
                'invoices.view',
                'invoices.create',
                'invoices.edit',
                'invoices.delete',
                'expenses.view',
                'expenses.create',
                'expenses.edit',
                'expenses.approve',
                'reports.view',
                'reports.export',
                'reports.financial',
                'settings.view',
                'settings.edit',
                'settings.company'
            ]
        ]);

        // Create Manager Role
        $manager = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Management level access',
            'permissions' => [
                'clients.view',
                'clients.create',
                'clients.edit',
                'products.view',
                'products.create',
                'products.edit',
                'invoices.view',
                'invoices.create',
                'invoices.edit',
                'expenses.view',
                'expenses.create',
                'expenses.edit',
                'reports.view',
                'reports.export'
            ]
        ]);

        // Create Employee Role
        $employee = Role::create([
            'name' => 'employee',
            'display_name' => 'Employee',
            'description' => 'Basic user access',
            'permissions' => [
                'clients.view',
                'products.view',
                'invoices.view',
                'expenses.view',
                'expenses.create'
            ]
        ]);

        // Create Super Admin User
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@erpsystem.com',
            'password' => Hash::make('password'),
            'role_id' => $superAdmin->id,
            'email_verified_at' => now(),
        ]);
    }
}
