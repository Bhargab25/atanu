<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'legal_name',
        'email',
        'phone',
        'mobile',
        'website',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'pan_number',
        'gstin',
        'cin',
        'tan_number',
        'fssai_number',
        'msme_number',
        'bank_name',
        'bank_account_number',
        'bank_ifsc_code',
        'bank_branch',
        'logo_path',
        'favicon_path',
        'letterhead_path',
        'signature_path',
        'established_date',
        'business_type',
        'business_description',
        'industry',
        'employee_count',
        'facebook_url',
        'twitter_url',
        'linkedin_url',
        'instagram_url',
        'financial_year_start',
        'currency',
        'timezone',
        'is_active',
        'is_default',
        'created_by'
    ];

    protected $casts = [
        'established_date' => 'date',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'employee_count' => 'integer',
    ];

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'company_profile_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'company_profile_id');
    }

    // Static methods for backward compatibility and convenience
    public static function current()
    {
        $company = static::active()->default()->first();

        if (!$company) {
            $company = static::active()->first();
        }

        // Create a default company if none exists
        if (!$company) {
            $company = static::create([
                'name' => config('app.name', 'My Company'),
                'country' => 'India',
                'currency' => 'INR',
                'timezone' => 'Asia/Kolkata',
                'is_active' => true,
                'is_default' => true,
            ]);
        }

        return $company;
    }


    public static function getAll()
    {
        return static::active()->orderBy('name')->get();
    }

    public static function setDefault($companyId)
    {
        // Remove default from all companies
        static::where('is_default', true)->update(['is_default' => false]);

        // Set new default
        return static::where('id', $companyId)->update(['is_default' => true]);
    }

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helper methods
    public function getLogoUrl()
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    public function getFaviconUrl()
    {
        return $this->favicon_path ? asset('storage/' . $this->favicon_path) : null;
    }

    public function getLetterheadUrl()
    {
        return $this->letterhead_path ? asset('storage/' . $this->letterhead_path) : null;
    }

    public function getSignatureUrl()
    {
        return $this->signature_path ? asset('storage/' . $this->signature_path) : null;
    }
}
