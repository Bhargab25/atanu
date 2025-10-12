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
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_profile_id')->constrained('company_profiles')->cascadeOnDelete();
            $table->foreignId('ledger_id')->constrained('account_ledgers')->onDelete('cascade');
            $table->date('date');
            $table->enum('type', ['purchase', 'payment', 'return', 'adjustment', 'opening', 'sale', 'receipt', 'expense', 'transfer', 'salary', 'other']);
            $table->string('description');
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index(['company_profile_id', 'date']);
            $table->index(['company_profile_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_transactions');
    }
};
