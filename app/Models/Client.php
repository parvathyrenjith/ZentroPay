<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'company_name',
        'tax_id',
        'website',
        'notes',
        'credit_limit',
        'outstanding_balance',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created this client
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all invoices for this client
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all payments for this client
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get paid invoices
     */
    public function paidInvoices(): HasMany
    {
        return $this->invoices()->where('status', 'paid');
    }

    /**
     * Get pending invoices
     */
    public function pendingInvoices(): HasMany
    {
        return $this->invoices()->where('status', 'pending');
    }

    /**
     * Get overdue invoices
     */
    public function overdueInvoices(): HasMany
    {
        return $this->invoices()->where('status', 'overdue');
    }

    /**
     * Calculate total amount of all invoices
     */
    public function getTotalInvoicedAttribute(): float
    {
        return $this->invoices()->sum('total_amount');
    }

    /**
     * Calculate total amount paid
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Calculate outstanding balance
     */
    public function getOutstandingBalanceAttribute(): float
    {
        return $this->invoices()
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('total_amount');
    }

    /**
     * Check if client has exceeded credit limit
     */
    public function hasExceededCreditLimit(): bool
    {
        return $this->outstanding_balance > $this->credit_limit;
    }

    /**
     * Get available credit
     */
    public function getAvailableCreditAttribute(): float
    {
        return max(0, $this->credit_limit - $this->outstanding_balance);
    }

    /**
     * Scope for active clients
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for clients with outstanding balance
     */
    public function scopeWithOutstandingBalance($query)
    {
        return $query->where('outstanding_balance', '>', 0);
    }

    /**
     * Scope for clients over credit limit
     */
    public function scopeOverCreditLimit($query)
    {
        return $query->whereColumn('outstanding_balance', '>', 'credit_limit');
    }

    /**
     * Update outstanding balance
     */
    public function updateOutstandingBalance(): void
    {
        $this->outstanding_balance = $this->getOutstandingBalanceAttribute();
        $this->save();
    }

    /**
     * Get full address as a single string
     */
    public function getFullAddressAttribute(): string
    {
        $addressParts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $addressParts);
    }
}
