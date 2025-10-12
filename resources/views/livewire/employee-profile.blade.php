<div>
    <x-mary-header title="Employee Profile" subtitle="{{ $employee->name }} - {{ $employee->employee_id }}" separator>
        <x-slot:actions>
            {{-- Company Info Badge --}}
            @if($employee->company)
            <div class="flex items-center gap-2 px-3 py-1 bg-blue-100 text-blue-800 rounded-lg text-sm">
                <x-mary-icon name="o-building-office" class="w-4 h-4" />
                {{ $employee->company->name }}
            </div>
            @endif

            <x-mary-button icon="o-pencil" label="Edit Employee" class="btn-outline btn-sm"
                link="/employees" />
            <x-mary-button icon="o-credit-card" label="Pay Salary" class="btn-primary btn-sm"
                @click="$wire.openPaymentModal()" />
        </x-slot:actions>
    </x-mary-header>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Employee Information Card --}}
        <div class="xl:col-span-1">
            <x-mary-card class="h-fit">
                {{-- Employee Photo & Basic Info --}}
                <div class="text-center mb-6">
                    <div class="avatar mb-4">
                        <div class="w-24 h-24 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                            <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" class="object-cover w-full h-full rounded-full" />
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">{{ $employee->name }}</h3>
                    <p class="text-gray-600">{{ $employee->employee_id }}</p>
                    <x-mary-badge
                        :value="$employee->is_active ? 'Active' : 'Inactive'"
                        :class="$employee->is_active ? 'badge-success mt-2' : 'badge-error mt-2'" />
                </div>

                {{-- Company Information --}}
                @if($employee->company)
                <div class="space-y-4 mb-6">
                    <h4 class="font-semibold text-gray-800 border-b pb-2">Company Information</h4>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <x-mary-icon name="o-building-office" class="w-5 h-5 text-blue-600" />
                            </div>
                            <div>
                                <p class="font-medium text-blue-900">{{ $employee->company->name }}</p>
                                @if($employee->company->legal_name)
                                <p class="text-sm text-blue-600">{{ $employee->company->legal_name }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Contact Information --}}
                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-800 border-b pb-2">Contact Information</h4>

                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <x-mary-icon name="o-phone" class="w-4 h-4 text-gray-500" />
                            <div>
                                <div class="text-sm font-medium">{{ $employee->phone }}</div>
                                @if($employee->alternate_phone)
                                <div class="text-xs text-gray-500">{{ $employee->alternate_phone }}</div>
                                @endif
                            </div>
                        </div>

                        @if($employee->email)
                        <div class="flex items-center gap-3">
                            <x-mary-icon name="o-envelope" class="w-4 h-4 text-gray-500" />
                            <div class="text-sm">{{ $employee->email }}</div>
                        </div>
                        @endif

                        @if($employee->full_address)
                        <div class="flex items-start gap-3">
                            <x-mary-icon name="o-map-pin" class="w-4 h-4 text-gray-500 mt-0.5" />
                            <div class="text-sm">{{ $employee->full_address }}</div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Employment Details --}}
                <div class="space-y-4 mt-6">
                    <h4 class="font-semibold text-gray-800 border-b pb-2">Employment Details</h4>

                    <div class="space-y-3">
                        @if($employee->position)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Position:</span>
                            <span class="text-sm font-medium">{{ $employee->position }}</span>
                        </div>
                        @endif

                        @if($employee->department)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Department:</span>
                            <x-mary-badge :value="$employee->department" class="badge-outline badge-sm" />
                        </div>
                        @endif

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Joining Date:</span>
                            <span class="text-sm font-medium">{{ $employee->joining_date->format('d M, Y') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Experience:</span>
                            <span class="text-sm font-medium">{{ $employee->joining_date->diffForHumans(null, true) }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Monthly Salary:</span>
                            <span class="text-sm font-bold text-primary">₹{{ number_format($employee->salary_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Documents --}}
                @if($employee->document_path)
                <div class="space-y-4 mt-6">
                    <h4 class="font-semibold text-gray-800 border-b pb-2">Documents</h4>
                    <div>
                        <a href="{{ $employee->document_url }}" target="_blank"
                            class="flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 transition-colors">
                            <x-mary-icon name="o-document" class="w-4 h-4" />
                            View ID Document
                        </a>
                    </div>
                </div>
                @endif

                {{-- Notes --}}
                @if($employee->notes)
                <div class="space-y-4 mt-6">
                    <h4 class="font-semibold text-gray-800 border-b pb-2">Notes</h4>
                    <div class="text-sm text-gray-600">{{ $employee->notes }}</div>
                </div>
                @endif
            </x-mary-card>
        </div>

        {{-- Payment History & Stats --}}
        <div class="xl:col-span-2">
            {{-- Account Ledger Summary --}}
            <x-mary-card class="mb-6">
                <x-mary-header title="Account Ledger Summary" subtitle="Employee account balance and financial overview" />

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <h3 class="text-sm font-medium text-blue-600">Current Balance</h3>
                        <p class="text-2xl font-bold {{ $ledgerBalance < 0 ? 'text-red-600' : 'text-green-600' }}">
                            ₹{{ number_format(abs($ledgerBalance), 2) }}
                        </p>
                        <p class="text-xs text-gray-600">{{ $ledgerBalance < 0 ? 'Amount Outstanding' : 'Advance Paid' }}</p>
                    </div>

                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <h3 class="text-sm font-medium text-green-600">Total Paid</h3>
                        <p class="text-2xl font-bold text-green-600">₹{{ number_format($totalPaid, 2) }}</p>
                        <p class="text-xs text-gray-600">All time payments</p>
                    </div>

                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <h3 class="text-sm font-medium text-purple-600">This Year</h3>
                        <p class="text-2xl font-bold text-purple-600">₹{{ number_format($paymentsThisYear, 2) }}</p>
                        <p class="text-xs text-gray-600">{{ date('Y') }} payments</p>
                    </div>

                    <div class="text-center p-4 bg-orange-50 rounded-lg">
                        <h3 class="text-sm font-medium text-orange-600">Last Payment</h3>
                        <p class="text-lg font-bold text-orange-600">
                            @if($lastPayment)
                            {{ $lastPayment->payment_date->format('d M, Y') }}
                            @else
                            No payments
                            @endif
                        </p>
                        <p class="text-xs text-gray-600">Latest transaction</p>
                    </div>
                </div>
            </x-mary-card>

            {{-- Recent Ledger Transactions --}}
            @if(count($ledgerTransactions) > 0)
            <x-mary-card class="mb-6">
                <x-mary-header title="Recent Ledger Transactions" subtitle="Latest account movements and adjustments" />

                <div class="space-y-3">
                    @foreach($ledgerTransactions as $transaction)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium">{{ $transaction->description }}</p>
                            <p class="text-sm text-gray-600">{{ $transaction->date->format('d/m/Y') }} • {{ ucfirst($transaction->type) }}</p>
                            @if($transaction->reference)
                            <p class="text-xs text-blue-600">Ref: {{ $transaction->reference }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="font-bold {{ $transaction->debit_amount > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction->debit_amount > 0 ? '+' : '-' }}₹{{ number_format($transaction->amount, 2) }}
                            </p>
                            <p class="text-xs text-gray-500">{{ $transaction->transaction_type }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-mary-card>
            @endif

            {{-- Payment History --}}
            <x-mary-card>
                <x-mary-header title="Payment History" subtitle="All salary payments for this employee">
                    <x-slot:actions>
                        <x-mary-button icon="o-plus" label="Add Payment" class="btn-primary btn-sm"
                            @click="$wire.openPaymentModal()" />
                    </x-slot:actions>
                </x-mary-header>

                @if($payments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Date</th>
                                <th>Month/Year</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Status</th>
                                <th>Processed By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                            <tr>
                                <td>
                                    <x-mary-badge :value="$payment->payment_id" class="badge-outline badge-sm font-mono" />
                                </td>
                                <td>{{ $payment->payment_date->format('d M, Y') }}</td>
                                <td>{{ Carbon\Carbon::createFromFormat('Y-m', $payment->month_year)->format('M Y') }}</td>
                                <td class="font-mono font-semibold">₹{{ number_format($payment->amount, 2) }}</td>
                                <td>
                                    <x-mary-badge
                                        :value="ucfirst(str_replace('_', ' ', $payment->payment_method))"
                                        class="badge-info badge-sm" />
                                </td>
                                <td>{{ $payment->reference_number ?: 'N/A' }}</td>
                                <td>
                                    <x-mary-badge
                                        :value="ucfirst($payment->status)"
                                        :class="$payment->status === 'paid' ? 'badge-success' : ($payment->status === 'pending' ? 'badge-warning' : 'badge-error')" />
                                </td>
                                <td>{{ $payment->creator->name ?? 'System' }}</td>
                                <td>
                                    <div class="flex gap-1 justify-center">
                                        {{-- Download Receipt --}}
                                        <x-mary-button
                                            icon="o-document-arrow-down"
                                            class="btn-circle btn-ghost btn-xs"
                                            tooltip="Download Receipt"
                                            wire:click="downloadReceipt({{ $payment->id }})" />

                                        {{-- Regenerate Receipt --}}
                                        <x-mary-button
                                            icon="o-arrow-path"
                                            class="btn-circle btn-ghost btn-xs"
                                            tooltip="Regenerate Receipt"
                                            wire:click="regenerateReceipt({{ $payment->id }})" />

                                        {{-- Send via WhatsApp --}}
                                        <x-mary-button
                                            icon="o-chat-bubble-left-right"
                                            class="btn-circle btn-ghost btn-xs text-green-600"
                                            tooltip="Send via WhatsApp"
                                            wire:click="openWhatsAppModal({{ $payment->id }})" />
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $payments->links() }}
                </div>
                @else
                <div class="text-center py-8">
                    <x-mary-icon name="o-credit-card" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                    <h3 class="text-lg font-medium text-gray-600 mb-2">No payments recorded</h3>
                    <p class="text-gray-500 mb-4">This employee hasn't received any salary payments yet.</p>
                    <x-mary-button icon="o-plus" label="Record First Payment" class="btn-primary"
                        @click="$wire.openPaymentModal()" />
                </div>
                @endif
            </x-mary-card>
        </div>
    </div>

    {{-- Payment Modal --}}
    <x-mary-modal wire:model="showPaymentModal"
        title="Process Salary Payment"
        subtitle="Record a new salary payment for {{ $employee->name }}"
        size="lg" box-class="border w-full max-w-4xl">

        <x-mary-form no-separator>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Payment Details</h3>

                    <x-mary-select
                        label="Payment Type *"
                        icon="o-tag"
                        wire:model="payment_type"
                        :options="$paymentTypes"
                        option-value="value"
                        option-label="label"
                        hint="Select the type of payment" />

                    <x-mary-input
                        label="Payment Amount *"
                        type="number"
                        step="0.01"
                        icon="o-currency-dollar"
                        wire:model="amount"
                        placeholder="0.00" />

                    <x-mary-input
                        label="Payment Date *"
                        type="date"
                        icon="o-calendar"
                        wire:model="payment_date" />

                    <x-mary-input
                        label="Month/Year *"
                        type="month"
                        icon="o-calendar-days"
                        wire:model="month_year"
                        hint="Select the month and year this payment is for" />
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Payment Method</h3>

                    <x-mary-select
                        label="Payment Method *"
                        icon="o-credit-card"
                        wire:model="payment_method"
                        :options="$paymentMethods"
                        option-value="value"
                        option-label="label" />

                    <x-mary-input
                        label="Reference Number"
                        icon="o-hashtag"
                        wire:model="reference_number"
                        placeholder="Transaction/Check number" />
                </div>
            </div>

            <div class="mt-6">
                <x-mary-textarea
                    label="Payment Notes"
                    icon="o-pencil-square"
                    wire:model="payment_notes"
                    placeholder="Additional notes about this payment..."
                    rows="3" />
            </div>

            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <div class="flex items-center gap-3">
                    <x-mary-icon name="o-information-circle" class="w-5 h-5 text-blue-500" />
                    <div class="text-sm text-blue-800">
                        <strong>Employee:</strong> {{ $employee->name }} ({{ $employee->employee_id }})
                        <br>
                        <strong>Regular Salary:</strong> ₹{{ number_format($employee->salary_amount, 2) }}
                        <br>
                        <strong>Current Balance:</strong>
                        <span class="{{ $ledgerBalance < 0 ? 'text-red-600' : 'text-green-600' }}">
                            ₹{{ number_format(abs($ledgerBalance), 2) }} {{ $ledgerBalance < 0 ? 'Outstanding' : 'Advance' }}
                        </span>
                    </div>
                </div>
            </div>

            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.showPaymentModal = false" />
                <x-mary-button
                    label="Process Payment"
                    class="btn-primary"
                    @click="$wire.processPayment"
                    spinner="processPayment" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    {{-- WhatsApp Modal --}}
    <x-mary-modal wire:model="showWhatsAppModal" title="Send Payment Receipt via WhatsApp" size="lg">
        @if($selectedPaymentForWhatsApp)
        <div class="space-y-4">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center gap-3 mb-3">
                    <x-mary-icon name="o-chat-bubble-left-right" class="w-6 h-6 text-green-600" />
                    <h4 class="font-semibold text-green-800">WhatsApp Message Preview</h4>
                </div>

                <div class="bg-white border border-green-200 rounded p-3 text-sm">
                    <div class="font-medium mb-2">To: {{ $employee->name }} ({{ $employee->phone }})</div>
                    <div class="whitespace-pre-line text-gray-700">
                        {{ (new App\Services\PaymentReceiptService())->generateWhatsAppMessage($selectedPaymentForWhatsApp) }}
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center gap-2 text-blue-800">
                    <x-mary-icon name="o-information-circle" class="w-5 h-5" />
                    <span class="font-medium">How it works:</span>
                </div>
                <ul class="mt-2 text-sm text-blue-700 space-y-1">
                    <li>• WhatsApp will open with a pre-filled message</li>
                    <li>• You can edit the message before sending</li>
                    <li>• The employee will receive the payment details</li>
                    <li>• You can manually attach the PDF receipt if needed</li>
                </ul>
            </div>
        </div>
        @endif

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showWhatsAppModal = false" />

            <div x-data="{ 
        openWhatsApp() {
            // Call the Livewire method and get the URL
            $wire.generateWhatsAppUrl().then((url) => {
                if (url) {
                    console.log('Opening WhatsApp:', url);
                    window.open(url, '_blank');
                    $wire.showWhatsAppModal = false;
                    $wire.call('showWhatsAppSuccess');
                }
            });
        }
    }">
                <x-mary-button
                    label="Open WhatsApp"
                    class="btn-success"
                    icon="o-chat-bubble-left-right"
                    @click="openWhatsApp()" />
            </div>
        </x-slot:actions>
    </x-mary-modal>

    {{-- JavaScript for WhatsApp --}}
    <script>
        document.addEventListener('livewire:init', function() {
            Livewire.on('open-whatsapp', (event) => {
                window.open(event.url, '_blank');
            });
        });
    </script>
</div>