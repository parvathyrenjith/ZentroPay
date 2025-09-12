<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'company_name',
        'tax_id',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Check if user has admin role
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has accountant role
     */
    public function isAccountant(): bool
    {
        return $this->role === 'accountant';
    }

    /**
     * Check if user has client role
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Check if user can manage users
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin() || $this->isAccountant();
    }

    /**
     * Check if user can manage invoices
     */
    public function canManageInvoices(): bool
    {
        return $this->isAdmin() || $this->isAccountant();
    }

    /**
     * Check if user can view reports
     */
    public function canViewReports(): bool
    {
        return $this->isAdmin() || $this->isAccountant();
    }

    /**
     * Get all invoices for this user (if client) or all invoices (if admin/accountant)
     */
    public function invoices(): HasMany
    {
        if ($this->isClient()) {
            return $this->hasMany(Invoice::class, 'client_id');
        }
        
        return $this->hasMany(Invoice::class, 'created_by');
    }

    /**
     * Get all clients (only for admin/accountant)
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'created_by');
    }

    /**
     * Get all payments for this user
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'created_by');
    }
}
