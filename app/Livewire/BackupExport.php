<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\BackupLog;
use App\Models\Client;
use App\Models\ProductCategory;
use App\Models\ExpenseCategory;
use App\Services\BackupService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class BackupExport extends Component
{
    use WithPagination, Toast;

    // Tab management
    public $activeTab = 'backups';

    // Backup properties
    public $isCreatingBackup = false;
    public $backupProgress = 0;

    // Export properties
    public $exportType = 'invoices';
    public $exportDateFrom = '';
    public $exportDateTo = '';
    public $exportStatus = '';
    public $exportCategoryId = '';
    public $activeOnly = true;
    public $isExporting = false;

    // Filters
    public $search = '';
    public $typeFilter = '';
    public $statusFilter = '';
    public $perPage = 15;

    // Settings
    public $autoBackupEnabled = true;
    public $backupRetentionDays = 30;

    public function mount()
    {
        $this->exportDateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->exportDateTo = now()->format('Y-m-d');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }
    public function testConnection()
    {
        try {
            $backupService = new BackupService();
            $result = $backupService->testDatabaseConnection();

            if ($result['success']) {
                $this->success('Database Connected!', $result['message'] . " - {$result['tables_count']} tables found");
            } else {
                $this->error('Connection Failed!', $result['message']);
            }
        } catch (\Exception $e) {
            $this->error('Connection Test Failed!', $e->getMessage());
        }
    }

    public function createDatabaseBackup()
    {
        if ($this->isCreatingBackup) return;

        $this->isCreatingBackup = true;
        $this->backupProgress = 0;

        try {
            $backupService = new BackupService();

            // Simulate progress updates
            $this->backupProgress = 25;
            $this->dispatch('backup-progress-updated', ['progress' => $this->backupProgress]);

            $backupLog = $backupService->createDatabaseBackup(auth()->id());

            $this->backupProgress = 100;
            $this->dispatch('backup-progress-updated', ['progress' => $this->backupProgress]);

            $this->success('Database Backup Created!', 'Backup file: ' . $backupLog->file_name);
        } catch (\Exception $e) {
            Log::error('Backup creation failed: ' . $e->getMessage());
            $this->error('Backup Failed!', 'Error: ' . $e->getMessage());
        } finally {
            $this->isCreatingBackup = false;
            $this->backupProgress = 0;
        }
    }

    public function createFullBackup()
    {
        if ($this->isCreatingBackup) return;

        $this->isCreatingBackup = true;
        $this->backupProgress = 0;

        try {
            $backupService = new BackupService();

            $this->backupProgress = 30;
            $this->dispatch('backup-progress-updated', ['progress' => $this->backupProgress]);

            $backupLog = $backupService->createFullBackup(auth()->id());

            $this->backupProgress = 100;
            $this->dispatch('backup-progress-updated', ['progress' => $this->backupProgress]);

            $this->success('Full Backup Created!', 'Backup file: ' . $backupLog->file_name);
        } catch (\Exception $e) {
            Log::error('Full backup creation failed: ' . $e->getMessage());
            $this->error('Backup Failed!', 'Error: ' . $e->getMessage());
        } finally {
            $this->isCreatingBackup = false;
            $this->backupProgress = 0;
        }
    }

    public function exportData()
    {
        if ($this->isExporting) return;

        $this->isExporting = true;

        try {
            $backupService = new BackupService();

            $filters = [];

            // Build filters based on export type
            if ($this->exportType === 'invoices') {
                $filters['date_from'] = $this->exportDateFrom;
                $filters['date_to'] = $this->exportDateTo;
                if ($this->exportStatus) {
                    $filters['status'] = $this->exportStatus;
                }
            } elseif (in_array($this->exportType, ['products', 'clients'])) {
                if ($this->activeOnly) {
                    $filters['active_only'] = true;
                }
                if ($this->exportType === 'products' && $this->exportCategoryId) {
                    $filters['category_id'] = $this->exportCategoryId;
                }
            } elseif ($this->exportType === 'expenses') {
                $filters['date_from'] = $this->exportDateFrom;
                $filters['date_to'] = $this->exportDateTo;
                if ($this->exportCategoryId) {
                    $filters['category_id'] = $this->exportCategoryId;
                }
            }

            $filePath = $backupService->exportData($this->exportType, $filters, auth()->id());

            $this->success('Export Completed!', 'Data exported successfully');

            // Download file
            return response()->download($filePath)->deleteFileAfterSend();
        } catch (\Exception $e) {
            Log::error('Export failed: ' . $e->getMessage());
            $this->error('Export Failed!', 'Error: ' . $e->getMessage());
        } finally {
            $this->isExporting = false;
        }
    }

    public function downloadBackup($backupId)
    {
        try {
            $backup = BackupLog::find($backupId);

            if (!$backup || !$backup->fileExists()) {
                $this->error('File not found', 'Backup file does not exist');
                return;
            }

            $filePath = Storage::disk('local')->path($backup->file_path);

            return response()->download($filePath, $backup->file_name);
        } catch (\Exception $e) {
            Log::error('Download failed: ' . $e->getMessage());
            $this->error('Download Failed!', 'Error: ' . $e->getMessage());
        }
    }

    public function deleteBackup($backupId)
    {
        try {
            $backup = BackupLog::find($backupId);

            if ($backup) {
                $backup->deleteBackupFile();
                $backup->delete();

                $this->success('Backup Deleted!', 'Backup file removed successfully');
            }
        } catch (\Exception $e) {
            Log::error('Backup deletion failed: ' . $e->getMessage());
            $this->error('Deletion Failed!', 'Error: ' . $e->getMessage());
        }
    }

    public function cleanupOldBackups()
    {
        try {
            $backupService = new BackupService();
            $deletedCount = $backupService->cleanupOldBackups($this->backupRetentionDays);

            $this->success('Cleanup Completed!', "Removed {$deletedCount} old backup(s)");
        } catch (\Exception $e) {
            Log::error('Cleanup failed: ' . $e->getMessage());
            $this->error('Cleanup Failed!', 'Error: ' . $e->getMessage());
        }
    }

    public function optimizeDatabase()
    {
        try {
            Artisan::call('optimize:clear');
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            $this->success('Database Optimized!', 'All caches cleared and rebuilt');
        } catch (\Exception $e) {
            Log::error('Database optimization failed: ' . $e->getMessage());
            $this->error('Optimization Failed!', 'Error: ' . $e->getMessage());
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    private function getFilteredQuery()
    {
        $query = BackupLog::with('creator');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('file_name', 'like', '%' . $this->search . '%')
                    ->orWhere('backup_type', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->typeFilter) {
            $query->where('backup_type', $this->typeFilter);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function render()
    {
        $backups = $this->getFilteredQuery()->paginate($this->perPage);

        return view('livewire.backup-export', [
            'backups' => $backups,
            'clients' => Client::where('is_active', true)->get(),
            'productCategories' => ProductCategory::where('is_active', true)->get(),
            'expenseCategories' => ExpenseCategory::where('is_active', true)->get(),
        ]);
    }
}
