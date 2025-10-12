<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_ledgers', function (Blueprint $table) {
            $table->id();
            // Company association
            $table->foreignId('company_profile_id')->constrained('company_profiles')->cascadeOnDelete();
            // Ledger name (Supplier, Client, Cash, Bank, etc.)
            $table->string('ledger_name');
            // Ledger category
            $table->enum('ledger_type', ['employee', 'expenses', 'client', 'cash', 'bank', 'other']);
            // Link to supplier or client (polymorphic relation)
            $table->nullableMorphs('ledgerable');  
            // => ledgerable_id, ledgerable_type
            // Opening balance (can be debit or credit)
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->enum('opening_balance_type', ['debit', 'credit'])->default('debit');
            // Running balance (updated with transactions)
            $table->decimal('current_balance', 15, 2)->default(0);
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for performance
            $table->index(['company_profile_id', 'ledger_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_ledgers');
    }
};
