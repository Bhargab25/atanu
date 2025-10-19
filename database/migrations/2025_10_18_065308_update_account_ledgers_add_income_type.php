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
        // Add 'income' to the ledger_type enum
        DB::statement("ALTER TABLE account_ledgers MODIFY COLUMN ledger_type ENUM(
            'cash', 
            'bank', 
            'client', 
            'employee', 
            'expenses', 
            'income'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'income' from enum (be careful - this might cause data loss)
        DB::statement("ALTER TABLE account_ledgers MODIFY COLUMN ledger_type ENUM(
            'cash', 
            'bank', 
            'client', 
            'employee', 
            'expenses'
        ) NOT NULL");
    }
};
