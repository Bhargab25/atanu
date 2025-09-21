<?php

use App\Livewire\ProductCategory;
use App\Livewire\Product;
use App\Livewire\Employee;
use App\Livewire\EmployeeProfile;
use App\Livewire\EmployeePayments;
use App\Livewire\ClientManagement;
use App\Livewire\SupplierManagement;
use App\Livewire\InvoiceManagement;
use App\Livewire\SalesReports;
use App\Livewire\SalesAnalytics;
use App\Livewire\ExpenseManagement;
use App\Livewire\SystemSettings;
use App\Livewire\BackupExport;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Models\Invoice;
use App\Models\MonthlyBill;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Livewire\UserProfile;

Route::view('/', 'welcome');

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth')->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', UserProfile::class)
        ->name('profile.edit');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/categories', ProductCategory::class)
    ->middleware(['auth', 'verified'])
    ->name('categories.index');

Route::get('/products', Product::class)
    ->middleware(['auth', 'verified'])
    ->name('products.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/employees', Employee::class)->name('employees.index');
    Route::get('/employees/{employeeId}/profile', EmployeeProfile::class)->name('employees.profile');
    Route::get('/employee-payments', EmployeePayments::class)->name('employee-payments.index');
});

// Payment Receipt Download Route
Route::middleware(['auth'])->group(function () {
    Route::get('/payment-receipt/{paymentId}/download', function ($paymentId) {
        $payment = \App\Models\EmployeePayment::findOrFail($paymentId);
        $receiptService = new \App\Services\PaymentReceiptService();

        // Generate receipt if it doesn't exist
        if (!$receiptService->receiptExists($payment)) {
            $receiptService->generatePaymentReceipt($payment);
        }

        $filePath = $receiptService->getReceiptPath($payment);
        $fullPath = Storage::disk('public')->path($filePath);

        if (file_exists($fullPath)) {
            return response()->download($fullPath, "payment-receipt-{$payment->payment_id}.pdf");
        }

        abort(404, 'Receipt not found');
    })->name('payment-receipt.download');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/clients', ClientManagement::class)->name('clients.index');
    Route::get('/clients/{clientId}/profile', \App\Livewire\ClientProfile::class)->name('clients.profile');
});

Route::get('/invoices', InvoiceManagement::class)
    ->middleware(['auth', 'verified'])
    ->name('invoices.index');

Route::get('/sales-reports', SalesReports::class)
    ->middleware(['auth', 'verified'])
    ->name('sales-reports.index');

Route::get('/sales-analytics', SalesAnalytics::class)
    ->middleware(['auth', 'verified'])
    ->name('sales-analytics.index');

Route::get('/expenses', ExpenseManagement::class)
    ->middleware(['auth', 'verified'])
    ->name('expenses.index');

Route::get('/settings/backup', BackupExport::class)
    ->middleware(['auth', 'verified'])
    ->name('backup-export.index');

Route::get('/settings/system', SystemSettings::class)
    ->middleware(['auth', 'verified'])
    ->name('system-settings.index');

Route::get('/settings/company', \App\Livewire\CompanyProfile::class)
    ->middleware(['auth', 'verified'])
    ->name('company-profile.index');

Route::get('/settings/users', \App\Livewire\UserManagement::class)
    ->middleware(['auth', 'verified'])
    ->name('user-management.index');

Route::middleware(['auth'])->group(function () {
    Route::get('/monthly-bill/download/{monthlyBill}', function (MonthlyBill $monthlyBill) {
        $pdfService = new \App\Services\InvoicePdfService();
        $pdfPath = $pdfService->generateMonthlyBillPdf($monthlyBill);
        return response()->download($pdfPath)->deleteFileAfterSend();
    })->name('monthly-bill.download');

    Route::get('/invoice/download/{invoice}', function (Invoice $invoice) {
        $pdfService = new \App\Services\InvoicePdfService();
        $pdfPath = $pdfService->generateInvoicePdf($invoice);
        return response()->download($pdfPath)->deleteFileAfterSend();
    })->name('invoice.download');
});

Route::get('/download/ledger-pdf/{path}', function ($path) {
    $decodedPath = base64_decode($path);

    if (!Storage::exists($decodedPath)) {
        abort(404, 'File not found');
    }

    return Storage::download($decodedPath);
})->name('download.ledger.pdf')->middleware('auth');


require __DIR__ . '/auth.php';
