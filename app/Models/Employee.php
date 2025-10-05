<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'company_profile_id',
        'name',
        'email',
        'phone',
        'alternate_phone',
        'address',
        'city',
        'state',
        'postal_code',
        'position',
        'department',
        'joining_date',
        'salary_amount',
        'photo_path',
        'document_path',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'salary_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(EmployeePayment::class);
    }

    // Accessors
    public function getPhotoUrlAttribute()
    {
        if ($this->photo_path && Storage::disk('public')->exists($this->photo_path)) {
            return Storage::disk('public')->url($this->photo_path);
        }
        return asset('images/default-avatar.png');
    }

    public function getDocumentUrlAttribute()
    {
        if ($this->document_path && Storage::disk('public')->exists($this->document_path)) {
            return Storage::disk('public')->url($this->document_path);
        }
        return null;
    }

    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code
        ]);
        return implode(', ', $parts);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeForCompany($query, $companyId = null)
    {
        if ($companyId) {
            return $query->where('company_profile_id', $companyId);
        }
        return $query;
    }

    // Helper methods
    public function getTotalPaidAmount()
    {
        return $this->payments()->where('status', 'paid')->sum('amount');
    }

    public function getLastPayment()
    {
        return $this->payments()->where('status', 'paid')->latest()->first();
    }

    public function hasPaymentForMonth($monthYear)
    {
        return $this->payments()
            ->where('month_year', $monthYear)
            ->where('status', 'paid')
            ->exists();
    }

    // Generate unique employee ID per company
    public static function generateEmployeeId($companyId)
    {
        $lastEmployee = self::withTrashed()
            ->where('company_profile_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastEmployee ? (int) substr($lastEmployee->employee_id, 3) + 1 : 1;
        return 'EMP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
