<div>
    <x-mary-header title="System Settings" subtitle="Configure application settings and preferences" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                <x-mary-button icon="o-arrow-path" label="Clear Cache" class="btn-warning btn-sm"
                    @click="$wire.clearCache()" />
                <x-mary-button icon="o-bolt" label="Optimize" class="btn-success btn-sm"
                    @click="$wire.optimizeSystem()" />
            </div>
        </x-slot:middle>
    </x-mary-header>

    {{-- Tabs Navigation --}}
    <div class="mb-6">
        <div class="bg-gray-100 rounded-xl p-1 inline-flex flex-wrap gap-1">
            <button wire:click="switchTab('general')"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'general' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                General
            </button>
            <button wire:click="switchTab('email')"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'email' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Email
            </button>
            <button wire:click="switchTab('invoice')"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'invoice' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Invoice
            </button>
            <button wire:click="switchTab('security')"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'security' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Security
            </button>
            <button wire:click="switchTab('backup')"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'backup' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Backup
            </button>
            <button wire:click="switchTab('notifications')"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'notifications' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Notifications
            </button>
            <button wire:click="switchTab('system')"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'system' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                System Info
            </button>
        </div>
    </div>

    {{-- Tab Content --}}
    @if($activeTab === 'general')
    <x-mary-card>
        <x-mary-header title="General Settings" subtitle="Basic application configuration" />

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Application Name *" wire:model="appName"
                    placeholder="Your Company Name" />

                <x-mary-input label="Application URL *" wire:model="appUrl"
                    placeholder="https://yourcompany.com" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-select label="Timezone *" wire:model="appTimezone"
                    :options="[
                            ['value' => 'Asia/Kolkata', 'label' => 'Asia/Kolkata'],
                            ['value' => 'UTC', 'label' => 'UTC'],
                            ['value' => 'America/New_York', 'label' => 'America/New_York'],
                            ['value' => 'Europe/London', 'label' => 'Europe/London']
                        ]"
                    option-value="value" option-label="label" />

                <x-mary-select label="Language *" wire:model="appLocale"
                    :options="[
                            ['value' => 'en', 'label' => 'English'],
                            ['value' => 'hi', 'label' => 'Hindi']
                        ]"
                    option-value="value" option-label="label" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-select label="Default Currency *" wire:model="defaultCurrency"
                    :options="[
                            ['value' => 'INR', 'label' => 'Indian Rupee (INR)'],
                            ['value' => 'USD', 'label' => 'US Dollar (USD)'],
                            ['value' => 'EUR', 'label' => 'Euro (EUR)'],
                            ['value' => 'GBP', 'label' => 'British Pound (GBP)']
                        ]"
                    option-value="value" option-label="label" />

                <x-mary-input label="Currency Symbol *" wire:model="currencySymbol"
                    placeholder="₹" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-select label="Date Format *" wire:model="dateFormat"
                    :options="[
                            ['value' => 'd/m/Y', 'label' => 'DD/MM/YYYY (14/09/2025)'],
                            ['value' => 'm/d/Y', 'label' => 'MM/DD/YYYY (09/14/2025)'],
                            ['value' => 'Y-m-d', 'label' => 'YYYY-MM-DD (2025-09-14)']
                        ]"
                    option-value="value" option-label="label" />

                <x-mary-select label="Time Format *" wire:model="timeFormat"
                    :options="[
                            ['value' => 'H:i', 'label' => '24 Hour (14:30)'],
                            ['value' => 'h:i A', 'label' => '12 Hour (02:30 PM)']
                        ]"
                    option-value="value" option-label="label" />
            </div>

            <div class="flex justify-end">
                <x-mary-button label="Save General Settings" class="btn-primary"
                    spinner="saveGeneralSettings" @click="$wire.saveGeneralSettings()" />
            </div>
        </div>
    </x-mary-card>

    @elseif($activeTab === 'email')
    <x-mary-card>
        <x-mary-header title="Email Settings" subtitle="Configure email delivery settings">
            <x-slot:middle class="!justify-end">
                <x-mary-button label="Test Connection" class="btn-info btn-sm"
                    spinner="testEmailConnection" @click="$wire.testEmailConnection()" />
            </x-slot:middle>
        </x-mary-header>

        <div class="space-y-6">
            <x-mary-checkbox label="Enable Email Functionality" wire:model="emailEnabled" />

            @if($emailEnabled)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-select label="Mail Driver *" wire:model="mailDriver"
                    :options="[
                                ['value' => 'smtp', 'label' => 'SMTP'],
                                ['value' => 'sendmail', 'label' => 'Sendmail'],
                                ['value' => 'log', 'label' => 'Log (Testing)']
                            ]"
                    option-value="value" option-label="label" />

                <x-mary-input label="Mail Host *" wire:model="mailHost"
                    placeholder="smtp.gmail.com" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Mail Port *" wire:model="mailPort"
                    placeholder="587" type="number" />

                <x-mary-select label="Encryption *" wire:model="mailEncryption"
                    :options="[
                                ['value' => 'tls', 'label' => 'TLS'],
                                ['value' => 'ssl', 'label' => 'SSL'],
                                ['value' => '', 'label' => 'None']
                            ]"
                    option-value="value" option-label="label" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Username" wire:model="mailUsername"
                    placeholder="your-email@gmail.com" />

                <x-mary-input label="Password" wire:model="mailPassword"
                    type="password" placeholder="••••••••" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="From Address *" wire:model="mailFromAddress"
                    placeholder="noreply@yourcompany.com" />

                <x-mary-input label="From Name *" wire:model="mailFromName"
                    placeholder="Your Company Name" />
            </div>
            @endif

            <div class="flex justify-end">
                <x-mary-button label="Save Email Settings" class="btn-primary"
                    spinner="saveEmailSettings" @click="$wire.saveEmailSettings()" />
            </div>
        </div>
    </x-mary-card>

    @elseif($activeTab === 'invoice')
    <div class="space-y-6">
        {{-- Settings Header --}}
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl border border-blue-200">
            <h2 class="text-xl font-bold text-gray-800 mb-2">Invoice Configuration</h2>
            <p class="text-gray-600">Customize your invoice settings and choose from professional templates</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            {{-- Settings Panel --}}
            <div class="xl:col-span-4">
                <div class="space-y-6">
                    {{-- Basic Settings Card --}}
                    <x-mary-card class="shadow-lg">
                        <x-mary-header title="Basic Settings" subtitle="Configure invoice generation" class="border-b pb-4 mb-6">
                            <x-slot:actions>
                                <x-mary-icon name="o-cog-6-tooth" class="w-5 h-5 text-gray-400" />
                            </x-slot:actions>
                        </x-mary-header>

                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <x-mary-input label="Invoice Prefix" wire:model.live="invoicePrefix"
                                    placeholder="INV" class="input-bordered" />

                                <x-mary-input label="Number Digits" wire:model.live="invoiceDigits"
                                    type="number" min="3" max="8" placeholder="4" class="input-bordered" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <x-mary-input label="Tax Rate (%)" wire:model.live="defaultTaxRate"
                                    type="number" step="0.01" min="0" max="100" placeholder="18" class="input-bordered" />

                                <div class="flex items-end pb-2">
                                    <x-mary-checkbox label="Enable GST" wire:model.live="enableGst" class="checkbox-primary" />
                                </div>
                            </div>

                            <x-mary-textarea label="Terms & Conditions" wire:model.live="invoiceTerms"
                                rows="2" placeholder="Payment terms..." class="textarea-bordered" />

                            <x-mary-textarea label="Default Notes" wire:model.live="invoiceNotes"
                                rows="2" placeholder="Thank you for your business..." class="textarea-bordered" />
                        </div>
                    </x-mary-card>

                    {{-- Template Selection Card --}}
                    <x-mary-card class="shadow-lg">
                        <x-mary-header title="Template Selection" subtitle="Choose your invoice design" class="border-b pb-4 mb-6">
                            <x-slot:actions>
                                <x-mary-icon name="o-swatch" class="w-5 h-5 text-gray-400" />
                            </x-slot:actions>
                        </x-mary-header>

                        <div class="space-y-3">
                            @foreach($availableTemplates as $key => $template)
                            <div class="relative">
                                <label class="flex items-start p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md
                                {{ $selectedTemplate === $key ? 'border-primary bg-primary/5 shadow-md' : 'border-gray-200 hover:border-gray-300' }}">

                                    <input type="radio" wire:model.live="selectedTemplate" value="{{ $key }}"
                                        class="mt-1 mr-4 text-primary focus:ring-primary w-4 h-4">

                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-gray-800">{{ $template['name'] }}</div>
                                        <div class="text-sm text-gray-600 mt-1">{{ $template['description'] }}</div>
                                    </div>

                                    <x-mary-button
                                        icon="o-eye"
                                        class="btn-sm btn-ghost"
                                        @click.prevent="$wire.previewInvoiceTemplate('{{ $key }}')"
                                        tooltip="Preview Template" />
                                </label>

                                @if($selectedTemplate === $key)
                                <div class="absolute -top-2 -right-2">
                                    <div class="bg-primary text-white rounded-full p-1">
                                        <x-mary-icon name="o-check" class="w-4 h-4" />
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </x-mary-card>

                    {{-- Action Buttons --}}
                    <div class="flex flex-col gap-3">
                        <x-mary-button label="Save All Settings" icon="o-check" class="btn-primary btn-lg"
                            spinner="saveInvoiceSettings" @click="$wire.saveInvoiceSettings()" />

                        <x-mary-button label="Generate Sample PDF" icon="o-document-arrow-down" class="btn-outline btn-lg"
                            spinner="generateSamplePDF" @click="$wire.generateSamplePDF()" />
                    </div>
                </div>
            </div>

            {{-- Live Preview Panel --}}
            <div class="xl:col-span-8">
                <x-mary-card class="shadow-lg h-full">
                    <x-mary-header title="Live Preview" subtitle="See how your invoice will look" class="border-b pb-4 mb-6">
                        <x-slot:actions>
                            <div class="flex gap-2">
                                <x-mary-button icon="o-arrow-path" class="btn-sm btn-ghost"
                                    @click="$wire.$refresh()" tooltip="Refresh Preview" />

                                <x-mary-select
                                    :options="collect($availableTemplates)->map(fn($template, $key) => ['value' => $key, 'label' => $template['name']])->values()->toArray()"
                                    option-value="value"
                                    option-label="label"
                                    wire:model.live="previewTemplate"
                                    placeholder="Quick preview..."
                                    class="select-sm w-40" />

                                <x-mary-button icon="o-arrows-pointing-out" class="btn-sm btn-outline"
                                    @click="openFullPreview()" tooltip="Full Screen" />
                            </div>
                        </x-slot:actions>
                    </x-mary-header>

                    <div class="preview-container">
                        @php
                        $templateToShow = '';
                        $previewData = [];

                        if (!empty($previewTemplate) && isset($availableTemplates[$previewTemplate])) {
                        $templateToShow = $previewTemplate;
                        $previewData = $this->getPreviewData();
                        } elseif (!empty($selectedTemplate) && isset($availableTemplates[$selectedTemplate])) {
                        $templateToShow = $selectedTemplate;
                        $previewData = $this->getPreviewData();
                        }
                        @endphp

                        @if(!empty($templateToShow) && !empty($previewData))
                        <div class="bg-white rounded-lg shadow-inner border-2 border-gray-100 overflow-hidden">
                            {{-- Preview Header --}}
                            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-2 border-b flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                                    <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                                    <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                                    <span class="ml-2 text-xs text-gray-600 font-medium">
                                        {{ $availableTemplates[$templateToShow]['name'] ?? 'Preview' }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Scale: 70%
                                </div>
                            </div>

                            {{-- Preview Content --}}
                            <div class="p-4 bg-gray-50">
                                <div class="bg-white shadow-lg rounded" style="transform: scale(0.7); transform-origin: top left; width: 142.86%; max-height: 500px; overflow-y: auto;">
                                    @include($availableTemplates[$templateToShow]['preview'], $previewData)
                                </div>
                            </div>
                        </div>

                        {{-- Template Actions --}}
                        <div class="flex justify-between items-center mt-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                <span class="text-sm text-gray-700">
                                    Currently previewing
                                    <span class="font-semibold text-blue-600">{{ $availableTemplates[$templateToShow]['name'] }}</span>
                                </span>
                            </div>

                            <div class="flex gap-2">
                                @if($previewTemplate && $previewTemplate !== $selectedTemplate)
                                <x-mary-button label="Use This Template" icon="o-check" class="btn-primary btn-sm"
                                    @click="$wire.selectedTemplate = '{{ $previewTemplate }}'; $wire.saveInvoiceSettings()" />
                                @endif
                                <x-mary-button label="Full Screen" icon="o-arrows-pointing-out" class="btn-outline btn-sm"
                                    @click="openFullPreview()" />
                            </div>
                        </div>
                        @else
                        <div class="flex flex-col items-center justify-center h-96 text-gray-500">
                            <div class="text-center">
                                <x-mary-icon name="o-document-text" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                                <h3 class="text-lg font-medium text-gray-600 mb-2">No Preview Available</h3>
                                <p class="text-sm">Select a template to see the preview</p>

                                @if(!empty($availableTemplates))
                                <div class="mt-4">
                                    <x-mary-button label="Preview Default Template" class="btn-primary btn-sm"
                                        @click="$wire.previewInvoiceTemplate('{{ array_key_first($availableTemplates) }}')" />
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </x-mary-card>
            </div>
        </div>
    </div>

    {{-- Full Screen Preview Modal (Fixed) --}}
    <div x-data="{ 
    showFullPreview: false,
    templateName: '',
    closeModal() {
        this.showFullPreview = false;
    },
    openModal() {
        this.showFullPreview = true;
        @this.dispatch('refresh-preview');
    }
}"
        @keydown.escape.window="closeModal()"
        @open-full-preview.window="openModal()">

        <div x-show="showFullPreview"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-75 backdrop-blur-sm"
            style="display: none;">

            <div class="flex min-h-screen items-center justify-center p-4">
                <div x-show="showFullPreview"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="w-full max-w-6xl bg-white rounded-xl shadow-2xl"
                    @click.away="closeModal()">

                    {{-- Modal Header --}}
                    <div class="flex justify-between items-center p-6 border-b bg-gradient-to-r from-blue-50 to-indigo-50 rounded-t-xl">
                        <div class="flex items-center gap-3">
                            <x-mary-icon name="o-document-text" class="w-6 h-6 text-blue-600" />
                            <h3 class="text-xl font-bold text-gray-800">
                                Full Screen Preview
                            </h3>
                            @php
                            $fullScreenTemplate = $previewTemplate ?: $selectedTemplate;
                            $templateName = '';
                            if (!empty($fullScreenTemplate) && isset($availableTemplates[$fullScreenTemplate])) {
                            $templateName = $availableTemplates[$fullScreenTemplate]['name'];
                            }
                            @endphp
                            @if($templateName)
                            <span class="text-sm text-gray-600">- {{ $templateName }}</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <x-mary-button icon="o-printer" class="btn-sm btn-outline" tooltip="Print Preview" />
                            <x-mary-button icon="o-document-arrow-down" class="btn-sm btn-primary"
                                @click="$wire.generateSamplePDF()" tooltip="Download PDF" />
                            <button @click="closeModal()"
                                class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <x-mary-icon name="o-x-mark" class="w-6 h-6" />
                            </button>
                        </div>
                    </div>

                    {{-- Modal Content --}}
                    <div class="p-6 max-h-[80vh] overflow-y-auto bg-gray-50">
                        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
                            @if(!empty($fullScreenTemplate) && isset($availableTemplates[$fullScreenTemplate]))
                            @include($availableTemplates[$fullScreenTemplate]['preview'], $this->getPreviewData())
                            @else
                            <div class="flex items-center justify-center h-64">
                                <p class="text-gray-500">No template selected for preview</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Fixed JavaScript --}}
    <script>
        function openFullPreview() {
            // Dispatch custom event to Alpine.js component
            window.dispatchEvent(new CustomEvent('open-full-preview'));
        }

        // Add smooth scrolling for better UX
        document.addEventListener('livewire:navigated', () => {
            // Smooth scroll behavior
            document.documentElement.style.scrollBehavior = 'smooth';
        });
    </script>

    {{-- Custom CSS for better preview --}}
    <style>
        .preview-container {
            min-height: 500px;
        }

        .preview-container .bg-white {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Custom scrollbar for preview */
        .preview-container *::-webkit-scrollbar {
            width: 6px;
        }

        .preview-container *::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .preview-container *::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .preview-container *::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Animation for template selection */
        .template-card {
            transition: all 0.3s ease;
        }

        .template-card:hover {
            transform: translateY(-2px);
        }
    </style>


    @elseif($activeTab === 'security')
    <x-mary-card>
        <x-mary-header title="Security Settings" subtitle="Configure security and authentication settings" />

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Session Lifetime (minutes)" wire:model="sessionLifetime"
                    type="number" min="5" max="1440" placeholder="120" />

                <x-mary-input label="Password Minimum Length" wire:model="passwordMinLength"
                    type="number" min="6" max="32" placeholder="8" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Max Login Attempts" wire:model="maxLoginAttempts"
                    type="number" min="3" max="20" placeholder="5" />

                <x-mary-input label="Lockout Duration (minutes)" wire:model="lockoutDuration"
                    type="number" min="1" max="60" placeholder="15" />
            </div>

            <div class="space-y-3">
                <x-mary-checkbox label="Require Strong Passwords" wire:model="requireStrongPassword"
                    hint="Passwords must contain uppercase, lowercase, numbers, and symbols" />

                <x-mary-checkbox label="Enable Two-Factor Authentication" wire:model="enableTwoFactor"
                    hint="Users can enable 2FA on their accounts" />
            </div>

            <div class="flex justify-end">
                <x-mary-button label="Save Security Settings" class="btn-primary"
                    spinner="saveSecuritySettings" @click="$wire.saveSecuritySettings()" />
            </div>
        </div>
    </x-mary-card>

    @elseif($activeTab === 'backup')
    <x-mary-card>
        <x-mary-header title="Backup Settings" subtitle="Configure automatic backup settings" />

        <div class="space-y-6">
            <x-mary-checkbox label="Enable Automatic Backups" wire:model="autoBackupEnabled" />

            @if($autoBackupEnabled)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-select label="Backup Frequency *" wire:model="backupFrequency"
                    :options="[
                                ['value' => 'daily', 'label' => 'Daily'],
                                ['value' => 'weekly', 'label' => 'Weekly'],
                                ['value' => 'monthly', 'label' => 'Monthly']
                            ]"
                    option-value="value" option-label="label" />

                <x-mary-input label="Retention Period (days)" wire:model="backupRetention"
                    type="number" min="1" max="365" placeholder="30"
                    hint="How long to keep backup files" />
            </div>
            @endif

            <div class="flex justify-end">
                <x-mary-button label="Save Backup Settings" class="btn-primary"
                    spinner="saveBackupSettings" @click="$wire.saveBackupSettings()" />
            </div>
        </div>
    </x-mary-card>

    @elseif($activeTab === 'notifications')
    <x-mary-card>
        <x-mary-header title="Notification Settings" subtitle="Configure system notifications" />

        <div class="space-y-6">
            <div class="space-y-3">
                <x-mary-checkbox label="Enable System Notifications" wire:model="enableNotifications" />
                <x-mary-checkbox label="Enable Email Notifications" wire:model="emailNotifications" />
            </div>

            <x-mary-input label="Low Stock Alert Threshold" wire:model="lowStockThreshold"
                type="number" min="0" max="1000" placeholder="10"
                hint="Alert when product stock falls below this quantity" />

            <div class="flex justify-end">
                <x-mary-button label="Save Notification Settings" class="btn-primary"
                    spinner="saveNotificationSettings" @click="$wire.saveNotificationSettings()" />
            </div>
        </div>
    </x-mary-card>

    @else
    <x-mary-card>
        <x-mary-header title="System Information" subtitle="View system and server information" />

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($systemInfo as $key => $value)
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-600 mb-1">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
                <div class="font-semibold text-gray-900">{{ $value }}</div>
            </div>
            @endforeach
        </div>
    </x-mary-card>
    @endif
</div>