<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;

class AutoBackup extends Command
{
    protected $signature = 'backup:auto';
    protected $description = 'Create automatic database backup';

    public function handle()
    {
        try {
            $backupService = new BackupService();
            $backup = $backupService->createDatabaseBackup(1); // System user
            
            $this->info('Backup created successfully: ' . $backup->file_name);
            
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
        }
    }
}
