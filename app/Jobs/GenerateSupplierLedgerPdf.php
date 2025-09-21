<?php
// app/Jobs/GenerateSupplierLedgerPdf.php

namespace App\Jobs;

use App\Models\Supplier;
use App\Models\User;
use App\Notifications\LedgerPdfReady;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GenerateSupplierLedgerPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $supplierId;
    protected $userId;

    public function __construct($supplierId, $userId)
    {
        $this->supplierId = $supplierId;
        $this->userId = $userId;
    }

    public function handle()
    {
        try {
            Log::info('Starting PDF generation for supplier: ' . $this->supplierId);

            // Get supplier with transactions
            $supplier = Supplier::with(['ledger.transactions' => function($query) {
                $query->where('date', '>=', now()->subMonths(6))
                      ->orderBy('date', 'desc');
            }])->find($this->supplierId);

            if (!$supplier) {
                Log::error('Supplier not found: ' . $this->supplierId);
                return;
            }

            if (!$supplier->ledger) {
                Log::error('Supplier has no ledger: ' . $this->supplierId);
                return;
            }

            // Calculate running balance
            $transactions = $supplier->ledger->transactions;
            $runningBalance = $supplier->ledger->opening_balance ?? 0;
            
            $transactionsWithBalance = $transactions->map(function($transaction) use (&$runningBalance) {
                $runningBalance += ($transaction->debit_amount - $transaction->credit_amount);
                $transaction->running_balance = $runningBalance;
                return $transaction;
            });

            Log::info('Generating PDF with ' . $transactions->count() . ' transactions');

            // Generate PDF
            $pdf = Pdf::loadView('pdfs.supplier-ledger', [
                'supplier' => $supplier,
                'transactions' => $transactionsWithBalance,
                'generatedAt' => now()->format('d/m/Y H:i'),
            ]);

            // Save PDF
            $filename = 'ledger-' . str_replace([' ', '/'], '-', $supplier->name) . '-' . now()->format('Y-m-d-H-i-s') . '.pdf';
            $path = 'ledgers/' . $filename;
            
            // Ensure directory exists
            Storage::makeDirectory('ledgers');
            Storage::put($path, $pdf->output());

            Log::info('PDF saved: ' . $path);

            // Send notification
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new LedgerPdfReady($supplier, $path, $filename, 'supplier'));
                Log::info('Notification sent to user: ' . $user->id);
            }

        } catch (\Exception $e) {
            Log::error('PDF generation failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
