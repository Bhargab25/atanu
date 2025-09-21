<?php

namespace App\Jobs;

use App\Models\User;
use App\Exports\StockReportExport;
use App\Exports\CategoryReportExport;
use App\Notifications\ReportGenerationCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class GenerateStockReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $reportData;
    protected $reportType;
    protected $filters;
    public $timeout = 300;


    public function __construct($userId, $reportData, $reportType = 'stock', $filters = [])
    {
        $this->userId = $userId;
        $this->reportData = $reportData;
        $this->reportType = $reportType;
        $this->filters = $filters;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting {$this->reportType} report generation for user: " . $this->userId);

            $user = User::find($this->userId);
            if (!$user) {
                Log::error('User not found: ' . $this->userId);
                return;
            }

            // Generate filename with timestamp
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "{$this->reportType}-report-{$timestamp}.xlsx";
            $filePath = "reports/{$filename}";

            // Generate the report based on type
            switch ($this->reportType) {
                case 'stock':
                    Log::info('Creating StockReportExport with data structure: ' . json_encode(array_keys($this->reportData)));
                    Excel::store(new StockReportExport($this->reportData), $filePath, 'public');
                    break;
                case 'category':
                    Log::info('Creating CategoryReportExport with ' . count($this->reportData) . ' categories');
                    Excel::store(new CategoryReportExport($this->reportData), $filePath, 'public');
                    break;
                default:
                    throw new \Exception('Unknown report type: ' . $this->reportType);
            }

            $fileUrl = Storage::disk('public')->url($filePath);
            $fileSize = Storage::disk('public')->size($filePath);

            // Send success notification
            $user->notify(new ReportGenerationCompleted([
                'report_type' => $this->reportType,
                'filename' => $filename,
                'file_path' => $filePath,
                'file_url' => $fileUrl,
                'file_size' => $this->formatFileSize($fileSize),
                'generated_at' => now()->toDateTimeString(),
                'filters_applied' => $this->filters,
            ], true));

            Log::info("{$this->reportType} report generated successfully: {$filename}");
        } catch (\Exception $e) {
            Log::error("{$this->reportType} report generation failed: " . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new ReportGenerationCompleted([
                    'report_type' => $this->reportType,
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toDateTimeString(),
                ], false));
            }
        }
    }

    private function formatFileSize($bytes)
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
