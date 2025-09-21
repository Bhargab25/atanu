<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserProfile extends Component
{
    use WithFileUploads, Toast;

    public $activeTab = 'profile';
    
    // Profile fields
    public $name = '';
    public $email = '';
    public $avatar;
    public $currentAvatarPath = '';
    
    // Password fields
    public $current_password = '';
    public $password = '';
    public $password_confirmation = '';

    public function mount()
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->currentAvatarPath = $user->avatar;
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'avatar' => 'nullable|image|max:2048',
        ]);

        $user = auth()->user();
        $userData = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->avatar) {
            // Delete old avatar
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }
            
            $userData['avatar'] = $this->avatar->store('avatars', 'public');
            $this->currentAvatarPath = $userData['avatar'];
            $this->avatar = null;
        }

        $user->update($userData);
        $this->success('Profile Updated!', 'Your profile has been updated successfully.');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required|current_password',
            'password' => [
                'required',
                'string',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
                'confirmed'
            ],
            'password_confirmation' => 'required',
        ]);

        $user = auth()->user();
        $user->update([
            'password' => Hash::make($this->password),
            'password_changed_at' => now(),
        ]);

        // Log activity
        if (method_exists($user, 'logActivity')) {
            $user->logActivity('password_changed');
        }

        $this->success('Password Updated!', 'Your password has been changed successfully.');
        $this->reset(['current_password', 'password', 'password_confirmation']);
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.user-profile');
    }
}
