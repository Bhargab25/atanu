<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'item_master';

    protected $fillable = [
        'name',
        'service_id',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationship with service/category
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'service_id');
    }

    // Alternative method name for clarity
    public function service()
    {
        return $this->belongsTo(ProductCategory::class, 'service_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
