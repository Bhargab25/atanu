{{-- resources/views/livewire/financial-reports.blade.php --}}
<div>
    <x-mary-header title="Financial Reports" subtitle="Comprehensive financial analysis and reporting" separator>
        <x-slot:middle class="!justify-end">
            <div class="flex gap-2 items-center">
                {{-- Company Selection --}}
                @if(count($companyOptions) > 1)
                    <x-mary-select 
                        wire:model.live="selectedCompanyId"
                        :options="$companyOptions"
                        option-value="id" 
                        option-label="name"
                        placeholder="Select Company..."
                        icon="o-building-office"
                        class="w-48" />
                @endif
                
                {{-- Period Selection --}}
                <x-mary-select 
                    wire:model.live="period"
                    :options="collect($periodOptions)->map(fn($label, $value) => ['id' => $value, 'name' => $label])"
                    option-value="id" 
                    option-label="name"
                    placeholder="Select Period..."
                    class="w-40" />
            </div>
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button icon="o-document-arrow-down" label="Export PDF" class="btn-outline btn-sm" 
                wire:click="exportReport('pdf')" />
            <x-mary-button icon="o-table-cells" label="Export CSV" class="btn-outline btn-sm" 
                wire:click="exportReport('csv')" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Company Info Banner --}}
    @if($selectedCompanyId)
        @php
            $selectedCompany = collect($companyOptions)->firstWhere('id', $selectedCompanyId);
        @endphp
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <x-mary-icon name="o-building-office" class="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-blue-900">{{ $selectedCompany['name'] ?? 'Company' }}</h3>
                        <p class="text-sm text-blue-600">
                            Report Period: {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
                        </p>
                    </div>
                </div>
                
                {{-- Custom Date Range --}}
                @if($period === 'custom')
                    <div class="flex gap-2">
                        <x-mary-input type="date" wire:model.live="dateFrom" class="w-40" />
                        <x-mary-input type="date" wire:model.live="dateTo" class="w-40" />
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Report Type Navigation --}}
    <div class="mb-6">
        <div class="bg-gray-100 rounded-xl p-1 inline-flex flex-wrap gap-1">
            @foreach($reportTypes as $type => $label)
                <button wire:click="switchReport('{{ $type }}')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 
                    {{ $activeReport === $type ? 'bg-primary text-white shadow-lg' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-200' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Report Content --}}
    @if($selectedCompanyId && !empty($reportData))
        {{-- Financial Summary Report --}}
        @if($activeReport === 'summary')
            @include('livewire.reports.financial-summary')
        @endif

        {{-- Profit & Loss Report --}}
        @if($activeReport === 'profit_loss')
            @include('livewire.reports.profit-loss')
        @endif

        {{-- Balance Sheet Report --}}
        @if($activeReport === 'balance_sheet')
            @include('livewire.reports.balance-sheet')
        @endif

        {{-- Cash Flow Report --}}
        @if($activeReport === 'cash_flow')
            @include('livewire.reports.cash-flow')
        @endif

        {{-- Expense Report --}}
        @if($activeReport === 'expenses')
            @include('livewire.reports.expense-report')
        @endif

        {{-- Employee Report --}}
        @if($activeReport === 'employees')
            @include('livewire.reports.employee-report')
        @endif

        {{-- Client Report --}}
        @if($activeReport === 'clients')
            @include('livewire.reports.client-report')
        @endif

        {{-- Ledger Report --}}
        @if($activeReport === 'ledger')
            @include('livewire.reports.ledger-report')
        @endif
    @else
        {{-- No Data State --}}
        <x-mary-card>
            <div class="text-center py-12">
                <x-mary-icon name="o-document-chart-bar" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <h3 class="text-lg font-medium text-gray-600 mb-2">No Data Available</h3>
                <p class="text-gray-500">
                    @if(!$selectedCompanyId)
                        Please select a company to view financial reports.
                    @else
                        No financial data found for the selected period.
                    @endif
                </p>
            </div>
        </x-mary-card>
    @endif
</div>
