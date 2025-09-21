<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            
            // Client Information
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('set null');
            $table->string('client_name')->nullable();
            $table->string('client_phone')->nullable();
            $table->text('client_address')->nullable();
            
            // Financial Details
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            
            // Status and Tracking
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'overdue'])->default('unpaid');
            
            // Additional Information
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->string('created_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['invoice_date', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
