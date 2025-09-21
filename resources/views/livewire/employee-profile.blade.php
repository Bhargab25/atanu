<div>
    <x-mary-header title="Employee Profile" subtitle="{{ $employee->name }} - {{ $employee->employee_id }}" separator>
        <x-slot:actions>
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
            {{-- Statistics Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <x-mary-card class="bg-gradient-to-r from-blue-50 to-blue-100">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                            <x-mary-icon name="o-currency-dollar" class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <div class="text-xs text-gray-600 uppercase tracking-wide">Total Paid</div>
                            <div class="text-xl font-bold text-gray-800">₹{{ number_format($totalPaid, 2) }}</div>
                        </div>
                    </div>
                </x-mary-card>

                <x-mary-card class="bg-gradient-to-r from-green-50 to-green-100">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                            <x-mary-icon name="o-calendar" class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <div class="text-xs text-gray-600 uppercase tracking-wide">This Year</div>
                            <div class="text-xl font-bold text-gray-800">₹{{ number_format($paymentsThisYear, 2) }}</div>
                        </div>
                    </div>
                </x-mary-card>

                <x-mary-card class="bg-gradient-to-r from-purple-50 to-purple-100">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                            <x-mary-icon name="o-clock" class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <div class="text-xs text-gray-600 uppercase tracking-wide">Last Payment</div>
                            <div class="text-lg font-bold text-gray-800">
                                @if($lastPayment)
                                {{ $lastPayment->payment_date->format('d M, Y') }}
                                @else
                                No payments
                                @endif
                            </div>
                        </div>
                    </div>
                </x-mary-card>
            </div>

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
        size="lg">

        <x-mary-form no-separator>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Payment Details</h3>

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