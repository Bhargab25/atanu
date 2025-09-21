<div>
    <x-mary-header title="My Profile" subtitle="Manage your account settings" separator />

    {{-- Tabs --}}
    <div class="mb-6">
        <div class="bg-gray-100 rounded-xl p-1 inline-flex">
            <button wire:click="switchTab('profile')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'profile' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Profile Information
            </button>
            <button wire:click="switchTab('password')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'password' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Change Password
            </button>
        </div>
    </div>

    @if($activeTab === 'profile')
        <x-mary-card>
            <x-mary-header title="Profile Information" subtitle="Update your account information" />
            
            <form wire:submit="updateProfile" class="space-y-6">
                <div class="flex items-center gap-6">
                    @if($currentAvatarPath)
                        <img src="{{ Storage::url($currentAvatarPath) }}" alt="Avatar" 
                            class="w-20 h-20 rounded-full object-cover border">
                    @else
                        <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
                            <x-mary-icon name="o-user" class="w-8 h-8 text-gray-400" />
                        </div>
                    @endif
                    
                    <div class="flex-1">
                        <x-mary-file label="Profile Picture" wire:model="avatar" accept="image/*" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-mary-input label="Full Name" wire:model="name" required />
                    <x-mary-input label="Email Address" wire:model="email" type="email" required />
                </div>

                <div class="flex justify-end">
                    <x-mary-button label="Update Profile" type="submit" class="btn-primary" 
                        spinner="updateProfile" />
                </div>
            </form>
        </x-mary-card>

    @else
        <x-mary-card>
            <x-mary-header title="Change Password" subtitle="Update your password" />
            
            <form wire:submit="updatePassword" class="space-y-6">
                <x-mary-input label="Current Password" wire:model="current_password" 
                    type="password" required />

                <x-mary-input label="New Password" wire:model="password" 
                    type="password" required 
                    hint="Password must be at least 8 characters with mixed case, numbers, and symbols" />

                <x-mary-input label="Confirm New Password" wire:model="password_confirmation" 
                    type="password" required />

                <div class="flex justify-end">
                    <x-mary-button label="Update Password" type="submit" class="btn-primary" 
                        spinner="updatePassword" />
                </div>
            </form>
        </x-mary-card>
    @endif
</div>
