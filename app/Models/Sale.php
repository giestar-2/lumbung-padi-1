<?php

namespace App\Models;

use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    protected $guarded = [];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getBalanceAttribute(): string
    {
        return bcsub((string) $this->total, (string) $this->paid_amount, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return bccomp($this->balance, '0', 2) <= 0 ? 'Lunas' : (bccomp((string) $this->paid_amount, '0', 2) > 0 ? 'Sebagian' : 'Belum Dibayar');
    }
}
