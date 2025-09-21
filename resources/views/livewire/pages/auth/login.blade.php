<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;
    public bool $showPassword = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        try {
            $this->form->authenticate();

            Session::regenerate();

            // Check if user needs to change password
            $user = auth()->user();
            if ($user->force_password_change) {
                $this->redirectRoute('password.change', navigate: true);
                return;
            }

            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('login', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Toggle password visibility
     */
    public function togglePasswordVisibility(): void
    {
        $this->showPassword = !$this->showPassword;
    }
}; ?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        {{-- Header --}}
        <div class="text-center">
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-primary/10">
                <x-mary-icon name="o-building-office" class="h-8 w-8 text-primary" />
            </div>
            <h2 class="mt-6 text-3xl font-bold text-gray-900">
                Sign in to your account
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Enter your credentials to access the ERP system
            </p>
        </div>

        {{-- Session Status --}}
        <x-auth-session-status class="mb-4" :status="session('status')" />

        {{-- Login Form --}}
        <div class="bg-white py-8 px-6 shadow-lg rounded-lg">
            <form wire:submit="login" class="space-y-6">
                {{-- Email Address --}}
                <div>
                    <x-mary-input
                        label="Email Address"
                        wire:model="form.email"
                        type="email"
                        placeholder="Enter your email"
                        icon="o-envelope"
                        required />
                </div>

                {{-- Password --}}
                <div>
                    <x-mary-input
                        label="Password"
                        wire:model="form.password"
                        :type="$showPassword ? 'text' : 'password'"
                        placeholder="Enter your password"
                        icon="o-lock-closed"
                        required>
                        <x-slot:append>
                            <x-mary-button
                                :icon="$showPassword ? 'o-eye-slash' : 'o-eye'"
                                class="btn-ghost btn-sm"
                                @click="$wire.togglePasswordVisibility()" />
                        </x-slot:append>
                    </x-mary-input>
                </div>

                {{-- Remember Me --}}
                <div class="flex items-center justify-between">
                    <x-mary-checkbox label="Remember me" wire:model="form.remember" />

                    @if (Route::has('password.request'))
                    <a class="text-sm text-primary hover:text-primary-focus"
                        href="{{ route('password.request') }}"
                        wire:navigate>
                        Forgot your password?
                    </a>
                    @endif
                </div>

                {{-- Submit Button --}}
                <div>
                    <x-mary-button
                        label="Sign In"
                        type="submit"
                        icon="o-arrow-right-on-rectangle"
                        class="btn-primary w-full"
                        spinner="login" />
                </div>
            </form>
        </div>

        {{-- Footer --}}
        <div class="text-center">
            <p class="text-xs text-gray-500">
                &copy; {{ date('Y') }} ERP System. All rights reserved.
            </p>
        </div>
    </div>
</div>