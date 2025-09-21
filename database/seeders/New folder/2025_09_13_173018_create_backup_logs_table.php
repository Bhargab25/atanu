<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('backup_type'); // database, files, full
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size')->nullable(); // in bytes
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->json('backup_info')->nullable(); // additional metadata
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['backup_type', 'status']);
            $table->index(['created_at', 'backup_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
