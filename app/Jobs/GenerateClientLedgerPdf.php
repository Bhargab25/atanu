<?php

namespace App\Jobs;

use App\Models\Client;
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

class GenerateClientLedgerPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $clientId;
    protected $userId;

    public function __construct($clientId, $userId)
    {
        $this->clientId = $clientId;
        $this->userId = $userId;
    }

    public function handle()
    {
        try {
            Log::info('Starting PDF generation for client: ' . $this->clientId);

            // Get client with transactions
            $client = Client::with(['ledger.transactions' => function ($query) {
                $query->where('date', '>=', now()->subMonths(6))
                    ->orderBy('date', 'desc');
            }])->find($this->clientId);

            if (!$client) {
                Log::error('Client not found: ' . $this->clientId);
                return;
            }

            if (!$client->ledger) {
                Log::error('Client has no ledger: ' . $this->clientId);
                return;
            }

            // Calculate running balance
            $transactions = $client->ledger->transactions;
            $runningBalance = $client->ledger->opening_balance ?? 0;

            $transactionsWithBalance = $transactions->map(function ($transaction) use (&$runningBalance) {
                $runningBalance += ($transaction->debit_amount - $transaction->credit_amount);
                $transaction->running_balance = $runningBalance;
                return $transaction;
            });

            Log::info('Generating PDF with ' . $transactions->count() . ' transactions');

            // Generate PDF
            $pdf = Pdf::loadView('pdfs.client-ledger', [
                'client' => $client,
                'transactions' => $transactionsWithBalance,
                'generatedAt' => now()->format('d/m/Y H:i'),
            ]);

            // Save PDF
            $filename = 'client-ledger-' . str_replace([' ', '/'], '-', $client->name) . '-' . now()->format('Y-m-d-H-i-s') . '.pdf';
            $path = 'ledgers/' . $filename;

            // Ensure directory exists
            Storage::makeDirectory('ledgers');
            Storage::put($path, $pdf->output());

            Log::info('PDF saved: ' . $path);

            // Send notification
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new LedgerPdfReady($client, $path, $filename, 'client'));
                Log::info('Notification sent to user: ' . $user->id);
            }
        } catch (\Exception $e) {
            Log::error('Client PDF generation failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
