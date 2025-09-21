<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'product_id', 'product_name', 'product_unit', 'invoice_unit',
        'unit_conversion_factor', 'quantity', 'unit_price', 'discount_percentage',
        'discount_amount', 'taxable_amount', 'cgst_rate', 'sgst_rate', 'igst_rate',
        'cgst_amount', 'sgst_amount', 'igst_amount', 'total_amount'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getDisplayUnitAttribute()
    {
        return $this->invoice_unit ?: $this->product_unit;
    }

    public function getConvertedQuantityAttribute()
    {
        return $this->quantity * $this->unit_conversion_factor;
    }
}
