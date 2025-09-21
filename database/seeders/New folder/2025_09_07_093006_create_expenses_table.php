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
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_ref')->unique();
            $table->string('expense_title');
            $table->foreignId('category_id')->constrained('expense_categories')->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->date('expense_date');
            $table->enum('payment_method', ['cash', 'bank', 'upi', 'card', 'cheque'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->boolean('is_business_expense')->default(true);
            $table->boolean('is_reimbursable')->default(false);
            $table->string('reimbursed_to')->nullable();
            $table->boolean('is_reimbursed')->default(false);
            $table->date('reimbursed_date')->nullable();
            $table->string('receipt_path')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['expense_date', 'category_id']);
            $table->index(['approval_status', 'is_business_expense']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
