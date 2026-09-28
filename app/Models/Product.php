<?php

namespace App\Models;

use App\Decimal;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $product): void {
            $product->inventory_value ??= Decimal::money(bcmul((string) ($product->stock_kg ?? 0), (string) ($product->cogs_per_kg ?? 0), 8));
        });
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProductionOutput::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class, 'raw_material_id');
    }
}
