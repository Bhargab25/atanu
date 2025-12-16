<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->foreignId('invoice_id')
                  ->nullable()
                  ->after('company_profile_id')
                  ->constrained('invoices')
                  ->onDelete('cascade');
            
            // Add index for performance
            $table->index(['invoice_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });
    }
};
