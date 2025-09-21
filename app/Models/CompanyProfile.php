<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

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
        'is_active'
    ];

    protected $casts = [
        'established_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Accessors for file URLs
    public function getLogoUrlAttribute()
    {
        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }

    public function getFaviconUrlAttribute()
    {
        return $this->favicon_path ? Storage::url($this->favicon_path) : null;
    }

    public function getLetterheadUrlAttribute()
    {
        return $this->letterhead_path ? Storage::url($this->letterhead_path) : null;
    }

    public function getSignatureUrlAttribute()
    {
        return $this->signature_path ? Storage::url($this->signature_path) : null;
    }

    // Get formatted address
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->country,
            $this->postal_code
        ]);

        return implode(', ', $parts);
    }

    // Get business type label
    public function getBusinessTypeLabelAttribute()
    {
        $types = [
            'proprietorship' => 'Proprietorship',
            'partnership' => 'Partnership',
            'llp' => 'Limited Liability Partnership (LLP)',
            'private_limited' => 'Private Limited Company',
            'public_limited' => 'Public Limited Company',
            'other' => 'Other'
        ];

        return $types[$this->business_type] ?? 'Not Specified';
    }

    // Static method to get company profile (singleton pattern)
    public static function current()
    {
        return static::where('is_active', true)->first() ?? new static();
    }

    // Delete associated files when model is deleted
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($company) {
            $files = [
                $company->logo_path,
                $company->favicon_path,
                $company->letterhead_path,
                $company->signature_path
            ];

            foreach ($files as $file) {
                if ($file && Storage::exists($file)) {
                    Storage::delete($file);
                }
            }
        });
    }
}
