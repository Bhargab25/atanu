<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use App\Models\User;
use App\Models\Role;
use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserManagement extends Component
{
    use WithPagination, WithFileUploads, Toast;

    // Tab management
    public $activeTab = 'users';

    // Modal management
    public $showUserModal = false;
    public $showRoleModal = false;
    public $showActivityModal = false;
    public $editingUser = null;
    public $editingRole = null;
    public $viewingActivity = null;

    // User form properties
    public $userName = '';
    public $userEmail = '';
    public $userPassword = '';
    public $userPasswordConfirmation = '';
    public $userRoleId = '';
    public $userIsActive = true;
    public $forcePasswordChange = false;
    public $userAvatar;

    // Role form properties
    public $roleName = '';
    public $roleDisplayName = '';
    public $roleDescription = '';
    public $rolePermissions = [];
    public $roleIsActive = true;

    // Filters and search
    public $search = '';
    public $roleFilter = '';
    public $statusFilter = '';
    public $perPage = 15;

    // Activity filters
    public $activityUserFilter = '';
    public $activityActionFilter = '';
    public $activityDateFrom = '';
    public $activityDateTo = '';

    protected function userRules()
    {
        $rules = [
            'userName' => 'required|string|max:255',
            'userEmail' => ['required', 'email', 'max:255'],
            'userRoleId' => 'required|exists:roles,id',
            'userIsActive' => 'boolean',
            'forcePasswordChange' => 'boolean',
            'userAvatar' => 'nullable|image|max:2048',
        ];

        if ($this->editingUser) {
            $rules['userEmail'][] = Rule::unique('users', 'email')->ignore($this->editingUser->id);
            $rules['userPassword'] = 'nullable|string|min:8|same:userPasswordConfirmation';
        } else {
            $rules['userEmail'][] = 'unique:users,email';
            $rules['userPassword'] = 'required|string|min:8|same:userPasswordConfirmation';
        }

        $rules['userPasswordConfirmation'] = 'nullable|string|min:8';

        return $rules;
    }

    protected function roleRules()
    {
        return [
            'roleName' => [
                'required',
                'string',
                'max:255',
                $this->editingRole ?
                    Rule::unique('roles', 'name')->ignore($this->editingRole->id) :
                    'unique:roles,name'
            ],
            'roleDisplayName' => 'required|string|max:255',
            'roleDescription' => 'nullable|string',
            'rolePermissions' => 'array',
            'roleIsActive' => 'boolean',
        ];
    }

    public function mount()
    {
        $this->activityDateFrom = now()->subDays(7)->format('Y-m-d');
        $this->activityDateTo = now()->format('Y-m-d');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // User Management Methods
    public function openUserModal()
    {
        $this->showUserModal = true;
        $this->resetUserForm();
    }

    public function closeUserModal()
    {
        $this->showUserModal = false;
        $this->editingUser = null;
        $this->resetValidation();
        $this->resetUserForm();
    }

    public function resetUserForm()
    {
        $this->userName = '';
        $this->userEmail = '';
        $this->userPassword = '';
        $this->userPasswordConfirmation = '';
        $this->userRoleId = '';
        $this->userIsActive = true;
        $this->forcePasswordChange = false;
        $this->userAvatar = null;
    }

    public function editUser($userId)
    {
        $this->editingUser = User::find($userId);

        if ($this->editingUser) {
            $this->userName = $this->editingUser->name;
            $this->userEmail = $this->editingUser->email;
            $this->userRoleId = $this->editingUser->role_id;
            $this->userIsActive = $this->editingUser->is_active;
            $this->forcePasswordChange = $this->editingUser->force_password_change;

            $this->showUserModal = true;
        }
    }

    public function saveUser()
    {
        $this->validate($this->userRules());

        try {
            $avatarPath = null;
            if ($this->userAvatar) {
                $avatarPath = $this->userAvatar->store('avatars', 'public');
            }

            $userData = [
                'name' => $this->userName,
                'email' => $this->userEmail,
                'role_id' => $this->userRoleId,
                'is_active' => $this->userIsActive,
                'force_password_change' => $this->forcePasswordChange,
            ];

            if ($this->userPassword) {
                $userData['password'] = Hash::make($this->userPassword);
                $userData['password_changed_at'] = now();
            }

            if ($avatarPath) {
                $userData['avatar'] = $avatarPath;
            }

            if ($this->editingUser) {
                // Delete old avatar if new one uploaded
                if ($avatarPath && $this->editingUser->avatar) {
                    Storage::delete($this->editingUser->avatar);
                }

                $this->editingUser->update($userData);
                $this->editingUser->logActivity('user_updated');

                $this->success('User Updated!', 'User information has been updated successfully.');
            } else {
                $user = User::create($userData);
                $user->logActivity('user_created');

                $this->success('User Created!', 'New user has been created successfully.');
            }

            $this->closeUserModal();
        } catch (\Exception $e) {
            Log::error('Error saving user: ' . $e->getMessage());
            $this->error('Save Failed!', 'Error: ' . $e->getMessage());
        }
    }

    public function toggleUserStatus($userId)
    {
        try {
            $user = User::find($userId);

            if ($user) {
                $user->update(['is_active' => !$user->is_active]);
                $user->logActivity($user->is_active ? 'user_activated' : 'user_deactivated');

                $status = $user->is_active ? 'activated' : 'deactivated';
                $this->success('User ' . ucfirst($status) . '!', "User has been {$status} successfully.");
            }
        } catch (\Exception $e) {
            Log::error('Error toggling user status: ' . $e->getMessage());
            $this->error('Status Update Failed!', 'Error updating user status.');
        }
    }

    public function resetUserPassword($userId)
    {
        try {
            $user = User::find($userId);

            if ($user) {
                $newPassword = 'password123'; // You might want to generate a random password

                $user->update([
                    'password' => Hash::make($newPassword),
                    'force_password_change' => true,
                    'password_changed_at' => now()
                ]);

                $user->logActivity('password_reset');

                $this->success('Password Reset!', "Password has been reset to: {$newPassword}");
            }
        } catch (\Exception $e) {
            Log::error('Error resetting password: ' . $e->getMessage());
            $this->error('Password Reset Failed!', 'Error resetting user password.');
        }
    }

    // Role Management Methods
    public function openRoleModal()
    {
        $this->showRoleModal = true;
        $this->resetRoleForm();
    }

    public function closeRoleModal()
    {
        $this->showRoleModal = false;
        $this->editingRole = null;
        $this->resetValidation();
        $this->resetRoleForm();
    }

    public function resetRoleForm()
    {
        $this->roleName = '';
        $this->roleDisplayName = '';
        $this->roleDescription = '';
        $this->rolePermissions = [];
        $this->roleIsActive = true;
    }

    public function editRole($roleId)
    {
        $this->editingRole = Role::find($roleId);

        if ($this->editingRole) {
            $this->roleName = $this->editingRole->name;
            $this->roleDisplayName = $this->editingRole->display_name;
            $this->roleDescription = $this->editingRole->description;
            $this->rolePermissions = $this->editingRole->permissions ?? [];
            $this->roleIsActive = $this->editingRole->is_active;

            $this->showRoleModal = true;
        }
    }

    public function saveRole()
    {
        $this->validate($this->roleRules());

        try {
            $roleData = [
                'name' => strtolower(str_replace(' ', '_', $this->roleName)),
                'display_name' => $this->roleDisplayName,
                'description' => $this->roleDescription,
                'permissions' => $this->rolePermissions,
                'is_active' => $this->roleIsActive,
            ];

            if ($this->editingRole) {
                $this->editingRole->update($roleData);
                $this->success('Role Updated!', 'Role has been updated successfully.');
            } else {
                Role::create($roleData);
                $this->success('Role Created!', 'New role has been created successfully.');
            }

            $this->closeRoleModal();
        } catch (\Exception $e) {
            Log::error('Error saving role: ' . $e->getMessage());
            $this->error('Save Failed!', 'Error: ' . $e->getMessage());
        }
    }

    public function deleteRole($roleId)
    {
        try {
            $role = Role::find($roleId);

            if ($role) {
                if ($role->is_system_role) {
                    $this->error('Cannot Delete!', 'System roles cannot be deleted.');
                    return;
                }

                if ($role->users()->count() > 0) {
                    $this->error('Cannot Delete!', 'This role is assigned to users. Please reassign users before deleting.');
                    return;
                }

                $role->delete();
                $this->success('Role Deleted!', 'Role has been deleted successfully.');
            }
        } catch (\Exception $e) {
            Log::error('Error deleting role: ' . $e->getMessage());
            $this->error('Delete Failed!', 'Error deleting role.');
        }
    }

    // Activity Log Methods
    public function viewActivityLogs($userId = null)
    {
        $this->viewingActivity = $userId;
        $this->showActivityModal = true;
    }

    public function closeActivityModal()
    {
        $this->showActivityModal = false;
        $this->viewingActivity = null;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    private function getFilteredUsersQuery()
    {
        $query = User::with('role');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->roleFilter) {
            $query->where('role_id', $this->roleFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('is_active', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc');
    }

    private function getFilteredRolesQuery()
    {
        $query = Role::withCount('users');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('display_name', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    private function getActivityLogsQuery()
    {
        $query = UserActivityLog::with('user');

        if ($this->viewingActivity) {
            $query->where('user_id', $this->viewingActivity);
        }

        if ($this->activityUserFilter) {
            $query->where('user_id', $this->activityUserFilter);
        }

        if ($this->activityActionFilter) {
            $query->where('action', $this->activityActionFilter);
        }

        if ($this->activityDateFrom) {
            $query->where('created_at', '>=', $this->activityDateFrom);
        }

        if ($this->activityDateTo) {
            $query->where('created_at', '<=', $this->activityDateTo . ' 23:59:59');
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function render()
    {
        $users = collect();
        $roles = collect();
        $activityLogs = collect();
        $allRoles = Role::active()->get();
        $availablePermissions = Role::availablePermissions();

        if ($this->activeTab === 'users') {
            $users = $this->getFilteredUsersQuery()->paginate($this->perPage);
        } elseif ($this->activeTab === 'roles') {
            $roles = $this->getFilteredRolesQuery()->paginate($this->perPage);
        } elseif ($this->activeTab === 'activity') {
            $activityLogs = $this->getActivityLogsQuery()->paginate($this->perPage);
        }

        return view('livewire.user-management', [
            'users' => $users,
            'roles' => $roles,
            'activityLogs' => $activityLogs,
            'allRoles' => $allRoles,
            'availablePermissions' => $availablePermissions,
            'allUsers' => User::active()->get(),
        ]);
    }
}
