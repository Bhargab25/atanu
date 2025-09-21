<?php
// app/Services/BackupService.php - Complete version with all methods

namespace App\Services;

use App\Models\BackupLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Exception;
use Illuminate\Support\Facades\Log;

class BackupService
{
    protected $backupDisk = 'local';
    protected $backupPath = 'backups';

    public function __construct()
    {
        // Ensure backup directory exists
        Storage::disk($this->backupDisk)->makeDirectory($this->backupPath);
    }

    public function createDatabaseBackup($userId)
    {
        $fileName = 'database_backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $filePath = $this->backupPath . '/' . $fileName;

        // Create backup log entry
        $backupLog = BackupLog::create([
            'backup_type' => 'database',
            'file_name' => $fileName,
            'file_path' => $filePath,
            'status' => 'processing',
            'started_at' => now(),
            'created_by' => $userId,
            'backup_info' => [
                'database' => config('database.connections.mysql.database'),
                'tables_count' => $this->getTablesCount(),
            ]
        ]);

        try {
            $this->dumpDatabase($filePath);

            // Check if file was created and get its size
            if (Storage::disk($this->backupDisk)->exists($filePath)) {
                $fileSize = Storage::disk($this->backupDisk)->size($filePath);

                $backupLog->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'file_size' => $fileSize,
                ]);
            } else {
                throw new Exception('Backup file was not created successfully');
            }

            return $backupLog;
        } catch (Exception $e) {
            Log::error('Database backup failed: ' . $e->getMessage());

            $backupLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // Add this missing method
    private function dumpDatabase($filePath)
    {
        try {
            $absolutePath = Storage::disk($this->backupDisk)->path($filePath);
            File::makeDirectory(dirname($absolutePath), 0755, true, true);

            $file = fopen($absolutePath, 'w');

            // Write SQL header
            fwrite($file, "-- MySQL Database Backup\n");
            fwrite($file, "-- Generated: " . now()->format('Y-m-d H:i:s') . "\n");
            fwrite($file, "-- Database: " . config('database.connections.mysql.database') . "\n\n");
            fwrite($file, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($file, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
            fwrite($file, "SET AUTOCOMMIT = 0;\n");
            fwrite($file, "START TRANSACTION;\n");
            fwrite($file, "SET time_zone = \"+00:00\";\n\n");

            // Get all tables
            $tables = $this->getAllTables();

            Log::info("Found " . count($tables) . " tables to backup");

            foreach ($tables as $table) {
                $this->dumpTable($file, $table);
            }

            fwrite($file, "\nCOMMIT;\n");
            fwrite($file, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($file);

            Log::info("Database backup completed successfully: " . $absolutePath);
        } catch (Exception $e) {
            if (isset($file) && is_resource($file)) {
                fclose($file);
            }
            Log::error("Database backup failed: " . $e->getMessage());
            throw new Exception('Database backup failed: ' . $e->getMessage());
        }
    }

    // Add this missing method
    private function getAllTables()
    {
        try {
            $database = config('database.connections.mysql.database');
            $tables = DB::select("SHOW TABLES");

            $tableNames = [];
            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                $tableNames[] = $tableName;
            }

            Log::info("Retrieved " . count($tableNames) . " tables: " . implode(', ', $tableNames));
            return $tableNames;
        } catch (Exception $e) {
            Log::error("Error getting table list: " . $e->getMessage());
            throw new Exception('Failed to get table list: ' . $e->getMessage());
        }
    }

    // Add this missing method
    private function dumpTable($file, $tableName)
    {
        try {
            Log::info("Dumping table: " . $tableName);

            // Write table structure
            fwrite($file, "\n-- Table structure for table `{$tableName}`\n");
            fwrite($file, "DROP TABLE IF EXISTS `{$tableName}`;\n");

            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            if (!empty($createTable)) {
                $createTableSql = $createTable[0]->{'Create Table'};
                fwrite($file, $createTableSql . ";\n\n");
            }

            // Write table data
            $rowCount = DB::table($tableName)->count();

            if ($rowCount > 0) {
                fwrite($file, "-- Dumping data for table `{$tableName}` ({$rowCount} rows)\n");

                // Process in chunks to handle large tables
                $chunkSize = 500;
                $processedRows = 0;

                // Check if table has 'id' column, if not use the first column for ordering
                $columns = DB::select("SHOW COLUMNS FROM `{$tableName}`");
                $firstColumn = $columns[0]->Field;

                DB::table($tableName)->orderBy($firstColumn)->chunk($chunkSize, function ($rows) use ($file, $tableName, &$processedRows) {
                    $this->writeTableData($file, $tableName, $rows);
                    $processedRows += $rows->count();
                    Log::info("Processed {$processedRows} rows for table {$tableName}");
                });
            } else {
                fwrite($file, "-- No data found for table `{$tableName}`\n");
            }

            fwrite($file, "\n");
        } catch (Exception $e) {
            Log::error("Error dumping table {$tableName}: " . $e->getMessage());
            fwrite($file, "-- Error dumping table {$tableName}: " . $e->getMessage() . "\n");
        }
    }

    // Add this missing method
    private function writeTableData($file, $tableName, $rows)
    {
        if ($rows->isEmpty()) return;

        $columns = array_keys((array) $rows->first());
        $columnsList = '`' . implode('`, `', $columns) . '`';

        $values = [];
        foreach ($rows as $row) {
            $rowValues = [];
            foreach ($columns as $column) {
                $value = $row->$column;
                if (is_null($value)) {
                    $rowValues[] = 'NULL';
                } elseif (is_numeric($value)) {
                    $rowValues[] = $value;
                } else {
                    $escapedValue = str_replace(
                        ["\\", "'", "\n", "\r", "\t"],
                        ["\\\\", "\\'", "\\n", "\\r", "\\t"],
                        $value
                    );
                    $rowValues[] = "'" . $escapedValue . "'";
                }
            }
            $values[] = '(' . implode(', ', $rowValues) . ')';
        }

        if (!empty($values)) {
            // Split large INSERT statements
            $batchSize = 100;
            $batches = array_chunk($values, $batchSize);

            foreach ($batches as $batch) {
                $insertSql = "INSERT INTO `{$tableName}` ({$columnsList}) VALUES\n" . implode(",\n", $batch) . ";\n";
                fwrite($file, $insertSql);
            }
        }
    }

    // Add this missing method for connection testing
    public function testDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            $database = config('database.connections.mysql.database');
            $tables = DB::select("SHOW TABLES");

            return [
                'success' => true,
                'database' => $database,
                'tables_count' => count($tables),
                'message' => 'Database connection successful'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Database connection failed'
            ];
        }
    }

    public function createFullBackup($userId)
    {
        $fileName = 'full_backup_' . now()->format('Y-m-d_H-i-s') . '.zip';
        $filePath = $this->backupPath . '/' . $fileName;

        $backupLog = BackupLog::create([
            'backup_type' => 'full',
            'file_name' => $fileName,
            'file_path' => $filePath,
            'status' => 'processing',
            'started_at' => now(),
            'created_by' => $userId,
            'backup_info' => [
                'includes' => ['database', 'storage', 'uploads'],
            ]
        ]);

        try {
            $this->createFullBackupArchive($filePath);

            $fileSize = Storage::disk($this->backupDisk)->size($filePath);

            $backupLog->update([
                'status' => 'completed',
                'completed_at' => now(),
                'file_size' => $fileSize,
            ]);

            return $backupLog;
        } catch (Exception $e) {
            $backupLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function exportData($type, $filters = [], $userId = null)
    {
        $fileName = $type . '_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filePath = 'exports/' . $fileName;

        Storage::disk($this->backupDisk)->makeDirectory('exports');

        switch ($type) {
            case 'invoices':
                $this->exportInvoices($filePath, $filters);
                break;
            case 'products':
                $this->exportProducts($filePath, $filters);
                break;
            case 'clients':
                $this->exportClients($filePath, $filters);
                break;
            case 'expenses':
                $this->exportExpenses($filePath, $filters);
                break;
            default:
                throw new Exception("Export type '{$type}' not supported");
        }

        // Create backup log entry for export
        if ($userId) {
            BackupLog::create([
                'backup_type' => 'export_' . $type,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'status' => 'completed',
                'file_size' => Storage::disk($this->backupDisk)->size($filePath),
                'started_at' => now(),
                'completed_at' => now(),
                'created_by' => $userId,
                'backup_info' => [
                    'export_type' => $type,
                    'filters' => $filters,
                ]
            ]);
        }

        return Storage::disk($this->backupDisk)->path($filePath);
    }

    private function createFullBackupArchive($filePath)
    {
        $absolutePath = Storage::disk($this->backupDisk)->path($filePath);
        $zip = new ZipArchive();

        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception('Cannot create zip file');
        }

        // Add database dump
        $dbFileName = 'database_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $dbPath = storage_path('app/temp/' . $dbFileName);

        File::makeDirectory(dirname($dbPath), 0755, true, true);
        $this->dumpDatabase('temp/' . $dbFileName);
        $zip->addFile(Storage::disk($this->backupDisk)->path('temp/' . $dbFileName), 'database.sql');

        // Add storage files
        $this->addDirectoryToZip($zip, storage_path('app/public'), 'storage');

        $zip->close();

        // Clean up temp database file
        Storage::disk($this->backupDisk)->delete('temp/' . $dbFileName);
    }

    private function addDirectoryToZip($zip, $directory, $localPath = '')
    {
        if (!is_dir($directory)) return;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $localPath . '/' . substr($filePath, strlen($directory) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    private function exportInvoices($filePath, $filters)
    {
        $query = DB::table('invoices')
            ->leftJoin('clients', 'invoices.client_id', '=', 'clients.id')
            ->select([
                'invoices.invoice_number',
                'invoices.invoice_date',
                'invoices.invoice_type',
                'clients.name as client_name',
                'invoices.client_name as cash_client_name',
                'invoices.total_amount',
                'invoices.paid_amount',
                'invoices.balance_amount',
                'invoices.payment_status',
                'invoices.created_at'
            ]);

        if (isset($filters['date_from'])) {
            $query->where('invoices.invoice_date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('invoices.invoice_date', '<=', $filters['date_to']);
        }
        if (isset($filters['status'])) {
            $query->where('invoices.payment_status', $filters['status']);
        }

        $invoices = $query->get();
        $this->writeCsv($filePath, $invoices->toArray(), [
            'Invoice Number',
            'Date',
            'Type',
            'Client Name',
            'Cash Client',
            'Total Amount',
            'Paid Amount',
            'Balance',
            'Status',
            'Created At'
        ]);
    }

    private function exportProducts($filePath, $filters)
    {
        $query = DB::table('products')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->select([
                'products.name',
                'product_categories.name as category_name',
                'products.unit',
                'products.stock_quantity',
                'products.min_stock_quantity',
                'products.unit_price',
                'products.is_active',
                'products.created_at'
            ]);

        if (isset($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }
        if (isset($filters['active_only']) && $filters['active_only']) {
            $query->where('products.is_active', true);
        }

        $products = $query->get();
        $this->writeCsv($filePath, $products->toArray(), [
            'Product Name',
            'Category',
            'Unit',
            'Stock Qty',
            'Min Stock',
            'Unit Price',
            'Active',
            'Created At'
        ]);
    }

    private function exportClients($filePath, $filters)
    {
        $query = DB::table('clients')->select([
            'name',
            'company',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'gstin',
            'is_active',
            'created_at'
        ]);

        if (isset($filters['active_only']) && $filters['active_only']) {
            $query->where('is_active', true);
        }

        $clients = $query->get();
        $this->writeCsv($filePath, $clients->toArray(), [
            'Name',
            'Company',
            'Email',
            'Phone',
            'Address',
            'City',
            'State',
            'GSTIN',
            'Active',
            'Created At'
        ]);
    }

    private function exportExpenses($filePath, $filters)
    {
        $query = DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->leftJoin('users', 'expenses.created_by', '=', 'users.id')
            ->select([
                'expenses.expense_ref',
                'expenses.expense_title',
                'expense_categories.name as category_name',
                'expenses.amount',
                'expenses.expense_date',
                'expenses.payment_method',
                'expenses.approval_status',
                'users.name as created_by_name',
                'expenses.created_at'
            ]);

        if (isset($filters['date_from'])) {
            $query->where('expenses.expense_date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('expenses.expense_date', '<=', $filters['date_to']);
        }
        if (isset($filters['category_id'])) {
            $query->where('expenses.category_id', $filters['category_id']);
        }

        $expenses = $query->get();
        $this->writeCsv($filePath, $expenses->toArray(), [
            'Reference',
            'Title',
            'Category',
            'Amount',
            'Date',
            'Payment Method',
            'Status',
            'Created By',
            'Created At'
        ]);
    }

    private function writeCsv($filePath, $data, $headers)
    {
        $absolutePath = Storage::disk($this->backupDisk)->path($filePath);
        File::makeDirectory(dirname($absolutePath), 0755, true, true);

        $file = fopen($absolutePath, 'w');

        // Write headers
        fputcsv($file, $headers);

        // Write data
        foreach ($data as $row) {
            fputcsv($file, (array) $row);
        }

        fclose($file);
    }

    private function getTablesCount()
    {
        try {
            $database = config('database.connections.mysql.database');
            $result = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?", [$database]);
            return $result[0]->count ?? 0;
        } catch (Exception $e) {
            Log::error("Error getting tables count: " . $e->getMessage());
            return 0;
        }
    }

    public function cleanupOldBackups($days = 1)
    {
        $cutoffDate = now()->subDays($days);
        Log::info("Cleaning up backups older than " . $cutoffDate->toDateTimeString());

        $oldBackups = BackupLog::where('created_at', '<', $cutoffDate)->get();

        foreach ($oldBackups as $backup) {
            $backup->deleteBackupFile();
            $backup->delete();
        }

        return $oldBackups->count();
    }
}
