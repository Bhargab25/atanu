<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_profile_id',
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, $companyId = null)
    {
        if ($companyId) {
            return $query->where('company_profile_id', $companyId);
        }
        return $query;
    }

    // Helper methods
    public function getTotalExpenses($startDate = null, $endDate = null)
    {
        $query = $this->expenses()->approved();

        if ($startDate && $endDate) {
            $query->forDateRange($startDate, $endDate);
        }

        return $query->sum('amount');
    }

    public function getExpenseCount($startDate = null, $endDate = null)
    {
        $query = $this->expenses()->approved();

        if ($startDate && $endDate) {
            $query->forDateRange($startDate, $endDate);
        }

        return $query->count();
    }
}
