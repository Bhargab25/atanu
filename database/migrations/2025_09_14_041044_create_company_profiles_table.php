<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile', 15)->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('India');
            $table->string('postal_code')->nullable();

            // Tax & Legal Information
            $table->string('pan_number')->nullable();
            $table->string('gstin')->nullable();
            $table->string('cin')->nullable();
            $table->string('tan_number')->nullable();
            $table->string('fssai_number')->nullable();
            $table->string('msme_number')->nullable();

            // Banking Information
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_ifsc_code')->nullable();
            $table->string('bank_branch')->nullable();

            // Branding
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('letterhead_path')->nullable();
            $table->string('signature_path')->nullable();

            // Company Details
            $table->date('established_date')->nullable();
            $table->enum('business_type', ['proprietorship', 'partnership', 'llp', 'private_limited', 'public_limited', 'other'])->nullable();
            $table->text('business_description')->nullable();
            $table->string('industry')->nullable();
            $table->integer('employee_count')->nullable();

            // Social Media
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('instagram_url')->nullable();

            // Settings
            $table->string('financial_year_start')->default('04-01');
            $table->string('currency', 3)->default('INR');
            $table->string('timezone')->default('Asia/Kolkata');

            // Multi-company support
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Mark default company
            $table->unsignedBigInteger('created_by')->nullable(); // User who created
            $table->timestamps();

            // Indexes
            $table->index(['is_active']);
            $table->index(['is_default']);
            
            // Foreign key if you have users table
            // $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
