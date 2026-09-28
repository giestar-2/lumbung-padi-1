<?php

namespace App\Models;

use Database\Factories\ProductionBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionBatch extends Model
{
    /** @use HasFactory<ProductionBatchFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['mixtures' => 'array', 'cost_allocation' => 'array', 'cost_finalized_at' => 'datetime'];
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'source_batch_id');
    }

    public function costs(): HasMany
    {
        return $this->hasMany(BatchCost::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'raw_material_id');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(BatchStage::class);
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProductionOutput::class);
    }
}
