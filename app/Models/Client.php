<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'company_name',
        'email',
        'phone',
        'alternate_phone',
        'address',
        'city',
        'state',
        'postal_code',
        'gstin',
        'services_items',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'services_items' => 'array',
        'is_active' => 'boolean',
    ];

    // Accessors
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

    // Get services from JSON
    public function getServicesAttribute()
    {
        return collect($this->services_items ?? [])->map(function ($serviceData) {
            return (object) $serviceData;
        });
    }

    // Calculate total amount from services and items
    public function getTotalAmountAttribute()
    {
        $total = 0;

        if ($this->services_items) {
            foreach ($this->services_items as $serviceData) {
                if (isset($serviceData['items'])) {
                    foreach ($serviceData['items'] as $item) {
                        $total += ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
                    }
                }
            }
        }

        return $total;
    }

    // Get service names for display
    public function getServiceNamesAttribute()
    {
        if (!$this->services_items) {
            return collect([]);
        }

        $serviceIds = array_keys($this->services_items);
        return \App\Models\ProductCategory::whereIn('id', $serviceIds)->pluck('name');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Generate unique client ID
    public static function generateClientId()
    {
        $lastClient = self::withTrashed()->orderBy('id', 'desc')->first();
        $nextNumber = $lastClient ? (int) substr($lastClient->client_id, 2) + 1 : 1;
        return 'CL' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    // Helper method to get services with items for invoice generation
    public function getServicesForInvoice()
    {
        if (!$this->services_items) {
            return collect([]);
        }

        $serviceIds = array_keys($this->services_items);
        $services = \App\Models\ProductCategory::whereIn('id', $serviceIds)->get();

        return $services->map(function ($service) {
            $clientServiceData = $this->services_items[$service->id] ?? [];
            return [
                'service' => $service,
                'items' => collect($clientServiceData['items'] ?? [])->map(function ($item) {
                    return (object) $item;
                }),
                'total' => collect($clientServiceData['items'] ?? [])->sum(function ($item) {
                    return ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
                })
            ];
        });
    }
}
