<div>
    <x-mary-header title="Backup & Export" subtitle="System backup and data export management" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                <x-mary-button
                    label="Test Database Connection"
                    class="btn-success"
                    icon="o-signal"
                    @click="$wire.testConnection()" />
                <x-mary-button icon="o-cog-6-tooth" label="Optimize DB" class="btn-info"
                    @click="$wire.optimizeDatabase()" />
                <x-mary-button icon="o-trash" label="Cleanup Old" class="btn-warning"
                    @click="$wire.cleanupOldBackups()" />
            </div>
        </x-slot:middle>
    </x-mary-header>

    {{-- Tabs Navigation --}}
    <div class="mb-6">
        <div class="bg-gray-100 rounded-xl p-1 inline-flex">
            <button wire:click="switchTab('backups')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'backups' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Backups
            </button>
            <button wire:click="switchTab('export')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'export' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Data Export
            </button>
            <button wire:click="switchTab('settings')"
                class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 
                {{ $activeTab === 'settings' ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                Settings
            </button>
        </div>
    </div>

    {{-- Tab Content --}}
    @if($activeTab === 'backups')
    {{-- Backup Creation Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <x-mary-card>
            <div class="text-center p-6">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-mary-icon name="o-circle-stack" class="w-8 h-8 text-blue-600" />
                </div>
                <h3 class="text-lg font-semibold mb-2">Database Backup</h3>
                <p class="text-gray-600 mb-4">Create a backup of your database including all tables and data</p>

                @if($isCreatingBackup)
                <div class="mb-4">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                            style="width: {{ $backupProgress }}%"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">Creating backup... {{ $backupProgress }}%</p>
                </div>
                @endif

                <x-mary-button
                    label="Create Database Backup"
                    class="btn-primary w-full"
                    icon="o-arrow-down-tray"
                    :disabled="$isCreatingBackup"
                    spinner="createDatabaseBackup"
                    @click="$wire.createDatabaseBackup()" />
            </div>
        </x-mary-card>

        <x-mary-card>
            <div class="text-center p-6">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-mary-icon name="o-archive-box" class="w-8 h-8 text-green-600" />
                </div>
                <h3 class="text-lg font-semibold mb-2">Full System Backup</h3>
                <p class="text-gray-600 mb-4">Create a complete backup including database and uploaded files</p>

                @if($isCreatingBackup)
                <div class="mb-4">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full transition-all duration-300"
                            style="width: {{ $backupProgress }}%"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">Creating backup... {{ $backupProgress }}%</p>
                </div>
                @endif

                <x-mary-button
                    label="Create Full Backup"
                    class="btn-success w-full"
                    icon="o-archive-box"
                    :disabled="$isCreatingBackup"
                    spinner="createFullBackup"
                    @click="$wire.createFullBackup()" />
            </div>
        </x-mary-card>
    </div>

    {{-- Backup History --}}
    <x-mary-card>
        <x-mary-header title="Backup History" subtitle="View and manage your backup files">
            <x-slot:middle class="!justify-end">
                <div class="flex gap-2 items-center">
                    <x-mary-input wire:model.live.debounce.300ms="search"
                        placeholder="Search backups..." icon="o-magnifying-glass" />

                    <x-mary-select wire:model.live="typeFilter"
                        :options="[
                                ['value' => '', 'label' => 'All Types'],
                                ['value' => 'database', 'label' => 'Database'],
                                ['value' => 'full', 'label' => 'Full Backup'],
                                ['value' => 'export_invoices', 'label' => 'Invoice Export'],
                                ['value' => 'export_products', 'label' => 'Product Export']
                            ]"
                        option-value="value" option-label="label" />

                    <x-mary-select wire:model.live="statusFilter"
                        :options="[
                                ['value' => '', 'label' => 'All Status'],
                                ['value' => 'completed', 'label' => 'Completed'],
                                ['value' => 'failed', 'label' => 'Failed'],
                                ['value' => 'processing', 'label' => 'Processing']
                            ]"
                        option-value="value" option-label="label" />
                </div>
            </x-slot:middle>
        </x-mary-header>

        <x-mary-table
            :headers="[
                    ['label' => '#', 'key' => 'sl_no'],
                    ['label' => 'File Name', 'key' => 'file_name'],
                    ['label' => 'Type', 'key' => 'type'],
                    ['label' => 'Size', 'key' => 'size'],
                    ['label' => 'Status', 'key' => 'status'],
                    ['label' => 'Created By', 'key' => 'creator'],
                    ['label' => 'Date', 'key' => 'date'],
                    ['label' => 'Actions', 'key' => 'actions']
                ]"
            :rows="$backups"
            striped
            with-pagination>

            @scope('cell_sl_no', $backup)
            <span class="font-medium">{{ $loop->iteration }}</span>
            @endscope

            @scope('cell_file_name', $backup)
            <div class="font-medium">{{ $backup->file_name }}</div>
            @if($backup->duration)
            <div class="text-xs text-gray-500">Duration: {{ $backup->duration }}</div>
            @endif
            @endscope

            @scope('cell_type', $backup)
            <x-mary-badge :value="ucwords(str_replace('_', ' ', $backup->backup_type))"
                :class="match($backup->backup_type) {
                            'database' => 'badge-info',
                            'full' => 'badge-success',
                            default => 'badge-secondary'
                        }" />
            @endscope

            @scope('cell_size', $backup)
            <div class="font-mono text-sm">{{ $backup->formatted_file_size }}</div>
            @endscope

            @scope('cell_status', $backup)
            <x-mary-badge :value="ucfirst($backup->status)"
                :class="$backup->status_badge_class" />
            @endscope

            @scope('cell_creator', $backup)
            <div class="text-sm">{{ $backup->creator->name }}</div>
            @endscope

            @scope('cell_date', $backup)
            <div class="text-sm">{{ $backup->created_at->format('d/m/Y H:i') }}</div>
            @endscope

            @scope('cell_actions', $backup)
            <div class="flex gap-1">
                @if($backup->status === 'completed' && $backup->fileExists())
                <x-mary-button icon="o-arrow-down-tray"
                    class="btn-circle btn-ghost btn-xs text-primary"
                    tooltip="Download"
                    @click="$wire.downloadBackup({{ $backup->id }})" />
                @endif

                <x-mary-button icon="o-trash"
                    class="btn-circle btn-ghost btn-xs text-error"
                    tooltip="Delete"
                    @click="$wire.deleteBackup({{ $backup->id }})" />
            </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    @elseif($activeTab === 'export')
    {{-- Data Export --}}
    <x-mary-card>
        <x-mary-header title="Data Export" subtitle="Export your business data to CSV format" />

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-mary-select label="Export Type *" wire:model.live="exportType"
                    :options="[
                            ['value' => 'invoices', 'label' => 'Invoices'],
                            ['value' => 'products', 'label' => 'Products'],
                            ['value' => 'clients', 'label' => 'Clients'],
                            ['value' => 'expenses', 'label' => 'Expenses']
                        ]"
                    option-value="value" option-label="label" />

                @if(in_array($exportType, ['invoices', 'expenses']))
                <x-mary-input label="From Date" wire:model="exportDateFrom" type="date" />
                <x-mary-input label="To Date" wire:model="exportDateTo" type="date" />
                @else
                <div></div>
                <div></div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @if($exportType === 'invoices')
                <x-mary-select label="Payment Status" wire:model="exportStatus"
                    :options="[
                                ['value' => '', 'label' => 'All Status'],
                                ['value' => 'paid', 'label' => 'Paid'],
                                ['value' => 'unpaid', 'label' => 'Unpaid'],
                                ['value' => 'partial', 'label' => 'Partial']
                            ]"
                    option-value="value" option-label="label" />
                @elseif($exportType === 'products')
                <x-mary-select label="Category" wire:model="exportCategoryId"
                    :options="$productCategories"
                    option-value="id" option-label="name"
                    placeholder="All Categories" />
                <div class="flex items-end">
                    <x-mary-checkbox label="Active Products Only" wire:model="activeOnly" />
                </div>
                @elseif($exportType === 'clients')
                <div class="flex items-end">
                    <x-mary-checkbox label="Active Clients Only" wire:model="activeOnly" />
                </div>
                @elseif($exportType === 'expenses')
                <x-mary-select label="Category" wire:model="exportCategoryId"
                    :options="$expenseCategories"
                    option-value="id" option-label="name"
                    placeholder="All Categories" />
                @endif
            </div>

            <div class="flex justify-end">
                <x-mary-button
                    label="Export Data"
                    class="btn-success"
                    icon="o-arrow-down-tray"
                    :disabled="$isExporting"
                    spinner="exportData"
                    @click="$wire.exportData()" />
            </div>
        </div>
    </x-mary-card>

    @else
    {{-- Settings --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-mary-card>
            <x-mary-header title="Backup Settings" />

            <div class="space-y-4">
                <x-mary-checkbox label="Enable Automatic Backups" wire:model="autoBackupEnabled" />

                <x-mary-input label="Backup Retention (days)" wire:model="backupRetentionDays"
                    type="number" min="1" max="365"
                    hint="Backups older than this will be automatically deleted" />

                <div class="flex justify-end">
                    <x-mary-button label="Save Settings" class="btn-primary" />
                </div>
            </div>
        </x-mary-card>

        <x-mary-card>
            <x-mary-header title="System Information" />

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Laravel Version:</span>
                        <span class="font-medium">{{ app()->version() }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">PHP Version:</span>
                        <span class="font-medium">{{ PHP_VERSION }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Database:</span>
                        <span class="font-medium">{{ config('database.default') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Storage:</span>
                        <span class="font-medium">{{ round(disk_free_space('/') / 1024 / 1024 / 1024, 2) }}GB Free</span>
                    </div>
                </div>

                <div class="pt-4 border-t">
                    <x-mary-button label="Clear All Caches" class="btn-warning btn-sm"
                        @click="$wire.optimizeDatabase()" />
                </div>
            </div>
        </x-mary-card>
    </div>
    @endif

    {{-- Dynamic Progress Modal --}}
    <div wire:loading.delay.longest class="fixed inset-0 z-[9999] overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black bg-opacity-60 backdrop-blur-sm"></div>

            {{-- Modal Container --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-lg transform transition-all">
                    <div class="text-center">
                        {{-- Header --}}
                        <div class="mb-6">
                            <div class="mx-auto w-16 h-16 bg-gradient-to-br from-primary to-primary/70 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-white animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Creating Backup</h3>
                            <p class="text-gray-600">Backing up your database and files...</p>
                        </div>

                        {{-- Progress Section --}}
                        <div class="mb-6">
                            <div class="flex justify-between text-sm text-gray-600 mb-2">
                                <span>Progress</span>
                                <span>{{ $backupProgress ?? 0 }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                <div class="bg-gradient-to-r from-primary to-primary/80 h-3 rounded-full transition-all duration-500 ease-out"
                                    style="width: {{ $backupProgress ?? 0 }}%"></div>
                            </div>
                        </div>

                        {{-- Status Messages --}}
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-center space-x-2 text-gray-600">
                                <div class="flex space-x-1">
                                    <div class="w-1.5 h-1.5 bg-primary rounded-full animate-pulse"></div>
                                    <div class="w-1.5 h-1.5 bg-primary rounded-full animate-pulse" style="animation-delay: 0.2s"></div>
                                    <div class="w-1.5 h-1.5 bg-primary rounded-full animate-pulse" style="animation-delay: 0.4s"></div>
                                </div>
                                <span>Please wait, this may take a few minutes</span>
                            </div>

                            <p class="text-xs text-gray-500">
                                Do not refresh or close this page during the backup process
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>