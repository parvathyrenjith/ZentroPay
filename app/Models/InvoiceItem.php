<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'details',
        'quantity',
        'unit_price',
        'tax_rate',
        'discount_rate',
        'line_total',
        'tax_amount',
        'discount_amount',
        'final_amount',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Get the invoice that owns the item
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Boot method to calculate totals
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($item) {
            $item->calculateTotals();
        });
        
        static::saved(function ($item) {
            $item->invoice->calculateTotals();
        });
        
        static::deleted(function ($item) {
            $item->invoice->calculateTotals();
        });
    }

    /**
     * Calculate line totals
     */
    public function calculateTotals(): void
    {
        // Calculate line total
        $this->line_total = $this->quantity * $this->unit_price;
        
        // Calculate discount amount
        $this->discount_amount = $this->line_total * ($this->discount_rate / 100);
        
        // Calculate taxable amount (after discount)
        $taxableAmount = $this->line_total - $this->discount_amount;
        
        // Calculate tax amount
        $this->tax_amount = $taxableAmount * ($this->tax_rate / 100);
        
        // Calculate final amount
        $this->final_amount = $taxableAmount + $this->tax_amount;
    }

    /**
     * Get formatted unit price
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2);
    }

    /**
     * Get formatted line total
     */
    public function getFormattedLineTotalAttribute(): string
    {
        return number_format($this->line_total, 2);
    }

    /**
     * Get formatted final amount
     */
    public function getFormattedFinalAmountAttribute(): string
    {
        return number_format($this->final_amount, 2);
    }
}
