<div>
    <x-mary-header title="User Management" subtitle="Manage users, roles, and permissions" separator>
        <x-slot:middle class="!justify-end">
            @if($activeTab === 'users')
            <x-mary-button icon="o-plus" label="Add User" class="btn-primary"
                @click="$wire.openUserModal()" />
            @elseif($activeTab === 'roles')
            <x-mary-button icon="o-plus" label="Add Role" class="btn-primary"
                @click="$wire.openRoleModal()" />
            @endif
        </x-slot:middle>
    </x-mary-header>

    {{-- Tabs Navigation --}}
    <div class="mb-6">
        <div class="bg-gray-100 rounded-xl p-1 inline-flex">
            <button wire:click="switchTab('users')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'users' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Users
            </button>
            <button wire:click="switchTab('roles')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'roles' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Roles & Permissions
            </button>
            <button wire:click="switchTab('activity')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'activity' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Activity Logs
            </button>
        </div>
    </div>

    {{-- Tab Content --}}
    @if($activeTab === 'users')
    <x-mary-card>
        <x-mary-header title="Users" subtitle="Manage system users">
            <x-slot:middle class="!justify-end">
                <div class="flex gap-2 items-center">
                    <x-mary-input wire:model.live.debounce.300ms="search"
                        placeholder="Search users..." icon="o-magnifying-glass" />

                    <x-mary-select wire:model.live="roleFilter"
                        :options="$allRoles"
                        option-value="id" option-label="display_name"
                        placeholder="All Roles" />

                    <x-mary-select wire:model.live="statusFilter"
                        :options="[
                                ['value' => '', 'label' => 'All Status'],
                                ['value' => '1', 'label' => 'Active'],
                                ['value' => '0', 'label' => 'Inactive']
                            ]"
                        option-value="value" option-label="label" />
                </div>
            </x-slot:middle>
        </x-mary-header>

        <x-mary-table
            :headers="[
                    ['label' => '#', 'key' => 'sl_no'],
                    ['label' => 'User', 'key' => 'user'],
                    ['label' => 'Email', 'key' => 'email'],
                    ['label' => 'Role', 'key' => 'role'],
                    ['label' => 'Status', 'key' => 'status'],
                    ['label' => 'Last Login', 'key' => 'last_login'],
                    ['label' => 'Actions', 'key' => 'actions']
                ]"
            :rows="$users"
            striped
            with-pagination>

            @scope('cell_sl_no', $user)
            <span class="font-medium">{{ $loop->iteration }}</span>
            @endscope

            @scope('cell_user', $user)
            <div class="flex items-center gap-3">
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                    class="w-10 h-10 rounded-full object-cover">
                <div>
                    <div class="font-medium">{{ $user->name }}</div>
                    @if($user->force_password_change)
                    <div class="text-xs text-orange-600">Must change password</div>
                    @endif
                </div>
            </div>
            @endscope

            @scope('cell_email', $user)
            <div class="text-sm">{{ $user->email }}</div>
            @endscope

            @scope('cell_role', $user)
            <x-mary-badge :value="$user->role_name" class="badge-info" />
            @endscope

            @scope('cell_status', $user)
            <x-mary-badge
                :value="$user->is_active ? 'Active' : 'Inactive'"
                :class="$user->status_badge_class" />
            @endscope

            @scope('cell_last_login', $user)
            <div class="text-sm">
                {{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Never' }}
            </div>
            @endscope

            @scope('cell_actions', $user)
            <div class="flex gap-1">
                <x-mary-button icon="o-pencil"
                    class="btn-circle btn-ghost btn-xs text-primary"
                    tooltip="Edit User"
                    @click="$wire.editUser({{ $user->id }})" />

                <x-mary-button icon="o-eye"
                    class="btn-circle btn-ghost btn-xs text-info"
                    tooltip="Activity Logs"
                    @click="$wire.viewActivityLogs({{ $user->id }})" />

                <x-mary-button icon="o-key"
                    class="btn-circle btn-ghost btn-xs text-warning"
                    tooltip="Reset Password"
                    @click="$wire.resetUserPassword({{ $user->id }})" />

                <x-mary-button
                    :icon="$user->is_active ? 'o-pause' : 'o-play'"
                    class="btn-circle btn-ghost btn-xs {{ $user->is_active ? 'text-error' : 'text-success' }}"
                    :tooltip="$user->is_active ? 'Deactivate' : 'Activate'"
                    @click="$wire.toggleUserStatus({{ $user->id }})" />
            </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    @elseif($activeTab === 'roles')
    <x-mary-card>
        <x-mary-header title="Roles & Permissions" subtitle="Manage user roles and permissions">
            <x-slot:middle class="!justify-end">
                <x-mary-input wire:model.live.debounce.300ms="search"
                    placeholder="Search roles..." icon="o-magnifying-glass" />
            </x-slot:middle>
        </x-mary-header>

        <x-mary-table
            :headers="[
                    ['label' => '#', 'key' => 'sl_no'],
                    ['label' => 'Role Name', 'key' => 'name'],
                    ['label' => 'Description', 'key' => 'description'],
                    ['label' => 'Users', 'key' => 'users_count'],
                    ['label' => 'Permissions', 'key' => 'permissions'],
                    ['label' => 'Status', 'key' => 'status'],
                    ['label' => 'Actions', 'key' => 'actions']
                ]"
            :rows="$roles"
            striped
            with-pagination>

            @scope('cell_sl_no', $role)
            <span class="font-medium">{{ $loop->iteration }}</span>
            @endscope

            @scope('cell_name', $role)
            <div>
                <div class="font-medium">{{ $role->display_name }}</div>
                <div class="text-xs text-gray-500">{{ $role->name }}</div>
                @if($role->is_system_role)
                <x-mary-badge value="System Role" class="badge-warning badge-xs mt-1" />
                @endif
            </div>
            @endscope

            @scope('cell_description', $role)
            <div class="text-sm">{{ $role->description ?: 'No description' }}</div>
            @endscope

            @scope('cell_users_count', $role)
            <x-mary-badge :value="$role->users_count . ' users'" class="badge-info" />
            @endscope

            @scope('cell_permissions', $role)
            <div class="text-sm">
                {{ count($role->permissions ?? []) }} permissions
            </div>
            @endscope

            @scope('cell_status', $role)
            <x-mary-badge
                :value="$role->is_active ? 'Active' : 'Inactive'"
                :class="$role->is_active ? 'badge-success' : 'badge-error'" />
            @endscope

            @scope('cell_actions', $role)
            <div class="flex gap-1">
                <x-mary-button icon="o-pencil"
                    class="btn-circle btn-ghost btn-xs text-primary"
                    tooltip="Edit Role"
                    @click="$wire.editRole({{ $role->id }})" />

                @if(!$role->is_system_role)
                <x-mary-button icon="o-trash"
                    class="btn-circle btn-ghost btn-xs text-error"
                    tooltip="Delete Role"
                    @click="$wire.deleteRole({{ $role->id }})" />
                @endif
            </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    @else
    <x-mary-card>
        <x-mary-header title="Activity Logs" subtitle="User activity and system logs">
            <x-slot:middle class="!justify-end">
                <div class="flex gap-2 items-center">
                    <x-mary-input wire:model.live.debounce.300ms="activityDateFrom"
                        type="date" label="From" />

                    <x-mary-input wire:model.live.debounce.300ms="activityDateTo"
                        type="date" label="To" />

                    <x-mary-select wire:model.live="activityUserFilter"
                        :options="$allUsers"
                        option-value="id" option-label="name"
                        placeholder="All Users" />
                </div>
            </x-slot:middle>
        </x-mary-header>

        <x-mary-table
            :headers="[
                    ['label' => 'User', 'key' => 'user'],
                    ['label' => 'Action', 'key' => 'action'],
                    ['label' => 'Model', 'key' => 'model'],
                    ['label' => 'IP Address', 'key' => 'ip'],
                    ['label' => 'Date', 'key' => 'date']
                ]"
            :rows="$activityLogs"
            striped
            with-pagination>

            @scope('cell_user', $log)
            <div class="flex items-center gap-2">
                <img src="{{ $log->user->avatar_url }}" alt="{{ $log->user->name }}"
                    class="w-8 h-8 rounded-full object-cover">
                <div class="text-sm">{{ $log->user->name }}</div>
            </div>
            @endscope

            @scope('cell_action', $log)
            <x-mary-badge :value="$log->formatted_action" class="badge-info badge-sm" />
            @endscope

            @scope('cell_model', $log)
            <div class="text-sm">
                {{ $log->model_type ? class_basename($log->model_type) : '-' }}
                @if($log->model_id)
                <span class="text-gray-500">#{{ $log->model_id }}</span>
                @endif
            </div>
            @endscope

            @scope('cell_ip', $log)
            <div class="text-sm font-mono">{{ $log->ip_address }}</div>
            @endscope

            @scope('cell_date', $log)
            <div class="text-sm">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
            @endscope
        </x-mary-table>
    </x-mary-card>
    @endif

    {{-- User Modal --}}
    <x-mary-modal wire:model="showUserModal" title="{{ $editingUser ? 'Edit User' : 'Add New User' }}"
        box-class="backdrop-blur max-w-2xl">

        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Full Name *" wire:model="userName" />
                <x-mary-input label="Email Address *" wire:model="userEmail" type="email" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Password {{ $editingUser ? '' : '*' }}" wire:model="userPassword" type="password" />
                <x-mary-input label="Confirm Password {{ $editingUser ? '' : '*' }}" wire:model="userPasswordConfirmation" type="password" />
            </div>

            <x-mary-select label="Role *" wire:model="userRoleId"
                :options="$allRoles" option-value="id" option-label="display_name" />

            <x-mary-file label="Profile Picture" wire:model="userAvatar" accept="image/*" />

            <div class="space-y-2">
                <x-mary-checkbox label="User is Active" wire:model="userIsActive" />
                <x-mary-checkbox label="Force Password Change on Next Login" wire:model="forcePasswordChange" />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.closeUserModal()" />
            <x-mary-button label="{{ $editingUser ? 'Update User' : 'Create User' }}" class="btn-primary"
                spinner="saveUser" @click="$wire.saveUser()" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- Role Modal --}}
    <x-mary-modal wire:model="showRoleModal" title="{{ $editingRole ? 'Edit Role' : 'Add New Role' }}"
        box-class="backdrop-blur max-w-4xl">

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Role Name *" wire:model="roleName"
                    hint="System name (lowercase, underscores)" />
                <x-mary-input label="Display Name *" wire:model="roleDisplayName"
                    hint="Human readable name" />
            </div>

            <x-mary-textarea label="Description" wire:model="roleDescription" rows="3" />

            <x-mary-checkbox label="Role is Active" wire:model="roleIsActive" />

            {{-- Permissions --}}
            <div>
                <h3 class="text-lg font-semibold mb-4">Permissions</h3>
                <div class="space-y-4">
                    @foreach($availablePermissions as $group => $permissions)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-medium mb-3 capitalize">{{ str_replace('_', ' ', $group) }}</h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            @foreach($permissions as $permission => $label)
                            <x-mary-checkbox
                                :label="$label"
                                :value="$permission"
                                wire:model="rolePermissions" />
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.closeRoleModal()" />
            <x-mary-button label="{{ $editingRole ? 'Update Role' : 'Create Role' }}" class="btn-primary"
                spinner="saveRole" @click="$wire.saveRole()" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- Activity Modal --}}
    <x-mary-modal wire:model="showActivityModal" title="User Activity Logs"
        box-class="backdrop-blur max-w-4xl">

        @if($viewingActivity)
        @php $user = App\Models\User::find($viewingActivity) @endphp
        <div class="mb-4 flex items-center gap-3">
            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                class="w-12 h-12 rounded-full object-cover">
            <div>
                <h3 class="font-semibold">{{ $user->name }}</h3>
                <p class="text-sm text-gray-600">{{ $user->email }}</p>
            </div>
        </div>
        @endif

        <div class="max-h-96 overflow-y-auto">
            <!-- Activity logs content here -->
            <div class="space-y-2">
                @forelse($activityLogs as $log)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div>
                        <div class="font-medium">{{ $log->formatted_action }}</div>
                        <div class="text-sm text-gray-600">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                    </div>
                    <div class="text-sm text-gray-500">{{ $log->ip_address }}</div>
                </div>
                @empty
                <div class="text-center py-8 text-gray-500">No activity logs found</div>
                @endforelse
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Close" @click="$wire.closeActivityModal()" />
        </x-slot:actions>
    </x-mary-modal>
</div>