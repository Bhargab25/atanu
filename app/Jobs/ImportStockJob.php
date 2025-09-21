<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Notifications\StockImportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class ImportStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $userId;
    public $timeout = 300; // 5 minutes timeout

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting stock import from file: ' . $this->filePath);

            $user = User::find($this->userId);
            if (!$user) {
                Log::error('User not found: ' . $this->userId);
                return;
            }

            if (!Storage::disk('local')->exists($this->filePath)) {
                Log::error('Import file not found: ' . $this->filePath);
                return;
            }

            $csv = Reader::createFromPath(Storage::disk('local')->path($this->filePath), 'r');
            $csv->setHeaderOffset(0);

            $records = $csv->getRecords();
            $importResults = [
                'total_rows' => 0,
                'successful' => 0,
                'failed' => 0,
                'errors' => [],
                'imported_products' => [],
            ];

            DB::beginTransaction();

            foreach ($records as $offset => $record) {
                $importResults['total_rows']++;

                try {
                    $productData = $this->processStockRecord($record, $offset + 2);
                    $importResults['successful']++;
                    $importResults['imported_products'][] = $productData;
                } catch (\Exception $e) {
                    $importResults['failed']++;
                    $importResults['errors'][] = "Row " . ($offset + 2) . ": " . $e->getMessage();
                    Log::warning("Stock import error on row " . ($offset + 2) . ": " . $e->getMessage());
                }
            }

            DB::commit();

            // Clean up the uploaded file
            Storage::disk('local')->delete($this->filePath);

            // Send success notification
            $user->notify(new StockImportCompleted($importResults, true));

            Log::info('Stock import completed successfully', $importResults);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Stock import failed: ' . $e->getMessage());

            // Send failure notification
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new StockImportCompleted([
                    'error' => $e->getMessage()
                ], false));
            }

            // Clean up the uploaded file
            if (Storage::disk('local')->exists($this->filePath)) {
                Storage::disk('local')->delete($this->filePath);
            }
        }
    }

    private function processStockRecord($record, $rowNumber)
    {
        $requiredFields = ['product_name', 'category_name', 'quantity', 'unit'];

        foreach ($requiredFields as $field) {
            if (empty($record[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }

        if (!in_array(strtolower($record['unit']), ['kg', 'g', 'box', 'pcs'])) {
            throw new \Exception("Invalid unit. Must be one of: kg, g, box, pcs");
        }

        // Find or create category
        $category = ProductCategory::firstOrCreate(
            ['name' => trim($record['category_name'])],
            ['description' => 'Auto-created from stock import', 'is_active' => true]
        );

        // Find or create product
        $product = Product::firstOrCreate(
            [
                'name' => trim($record['product_name']),
                'category_id' => $category->id
            ],
            [
                'unit' => strtolower($record['unit']),
                'stock_quantity' => 0,
                'min_stock_quantity' => isset($record['min_stock_quantity']) ? floatval($record['min_stock_quantity']) : 10,
                'is_active' => true
            ]
        );

        $quantity = floatval($record['quantity']);
        if ($quantity <= 0) {
            throw new \Exception("Quantity must be greater than 0");
        }

        $oldQuantity = $product->stock_quantity;
        $product->stock_quantity += $quantity;

        if (isset($record['min_stock_quantity']) && !empty($record['min_stock_quantity'])) {
            $product->min_stock_quantity = floatval($record['min_stock_quantity']);
        }

        $product->save();

        // Create stock movement record
        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => $quantity,
            'reason' => 'Stock Import',
            'reference_type' => 'App\Jobs\ImportStockJob',
            'reference_id' => $this->job->getJobId() ?? 0,
        ]);

        return [
            'product_name' => $product->name,
            'category_name' => $category->name,
            'quantity_added' => $quantity,
            'old_stock' => $oldQuantity,
            'new_stock' => $product->stock_quantity,
        ];
    }
}
