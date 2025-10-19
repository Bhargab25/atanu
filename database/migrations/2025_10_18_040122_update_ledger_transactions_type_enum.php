<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new transaction types to the enum
        DB::statement("ALTER TABLE ledger_transactions MODIFY COLUMN type ENUM(
            'purchase', 
            'payment', 
            'return', 
            'adjustment', 
            'opening', 
            'sale', 
            'receipt', 
            'income',
            'expense', 
            'transfer', 
            'salary', 
            'accrual', 
            'advance', 
            'settlement', 
            'other'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE ledger_transactions MODIFY COLUMN type ENUM(
            'purchase', 
            'payment', 
            'return', 
            'adjustment', 
            'opening', 
            'sale', 
            'receipt',
            'income',
            'expense', 
            'transfer', 
            'salary', 
            'other'
        ) NOT NULL");
    }
};
