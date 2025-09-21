<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Challan extends Model
{
    protected $fillable = ['challan_number', 'challan_date', 'supplier_id', 'remarks'];

    protected $casts = [
        'challan_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(ChallanItem::class);
    }
}
