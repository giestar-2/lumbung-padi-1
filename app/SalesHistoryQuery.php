<?php

namespace App;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;

final class SalesHistoryQuery
{
    /** @param array{search?: string, customer?: mixed, product?: mixed, from?: string, to?: string, status?: string} $filters */
    public function build(array $filters): Builder
    {
        return Sale::query()
            ->when($filters['search'] ?? '', fn (Builder $q, string $value) => $q->where('invoice_number', 'like', '%'.$value.'%'))
            ->when($filters['customer'] ?? '', fn (Builder $q, mixed $value) => $q->where('customer_id', $value))
            ->when($filters['product'] ?? '', fn (Builder $q, mixed $value) => $q->whereHas('items', fn (Builder $items) => $items->where('product_id', $value)))
            ->when($filters['from'] ?? '', fn (Builder $q, string $value) => $q->where('sale_date', '>=', $value))
            ->when($filters['to'] ?? '', fn (Builder $q, string $value) => $q->where('sale_date', '<=', $value))
            ->when(($filters['status'] ?? '') === 'Lunas', fn (Builder $q) => $q->whereColumn('paid_amount', '>=', 'total'))
            ->when(($filters['status'] ?? '') === 'Sebagian', fn (Builder $q) => $q->where('paid_amount', '>', 0)->whereColumn('paid_amount', '<', 'total'))
            ->when(($filters['status'] ?? '') === 'Belum Dibayar', fn (Builder $q) => $q->where('paid_amount', 0)->where('total', '>', 0));
    }
}
