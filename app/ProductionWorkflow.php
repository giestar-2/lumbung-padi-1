<?php

namespace App;

use App\Models\BatchStage;
use App\Models\CashEntry;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionOutput;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ProductionWorkflow
{
    /** @return list<string> */
    public static function stages(string $type): array
    {
        return match ($type) {
            'Dedek' => ['Belum Diproses', 'Digiling', 'Selesai'],
            'Pupuk' => ['Belum Diproses', 'Pencampuran dengan Bahan Lain', 'Selesai'],
            default => ['Belum Diproses', 'Pengeringan', 'Pemisahan', 'Selesai'],
        };
    }

    public static function available(ProductionBatch $source, string $type): string
    {
        $field = $type === 'Dedek' ? 'dedek_weight' : 'pupuk_weight';

        return bcsub((string) $source->$field, (string) $source->children()->where('type', $type)->sum('raw_material_weight'), 3);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): ProductionBatch
    {
        Gate::authorize('owner');

        return DB::transaction(function () use ($data): ProductionBatch {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            $weight = Decimal::normalize($data['raw_material_weight'], 3, 'raw_material_weight');
            if (bccomp($weight, '0', 3) <= 0) {
                throw ValidationException::withMessages(['raw_material_weight' => 'Berat harus positif.']);
            }
            $cost = Decimal::money(bcmul($weight, Decimal::normalize($data['purchase_price'], 2, 'purchase_price'), 8));
            $inherited = '0.00';
            if ($data['type'] !== 'Beras') {
                $source = ProductionBatch::lockForUpdate()->findOrFail($data['source_batch_id']);
                if ($source->type !== 'Beras' || $source->status !== 'Selesai' || bccomp(self::available($source, $data['type']), $weight, 3) < 0) {
                    throw ValidationException::withMessages(['raw_material_weight' => 'Sisa bahan dari batch asal tidak mencukupi atau belum selesai.']);
                }
                if (! $source->cost_finalized_at && bccomp($this->totalCost($source), '0', 2) > 0) {
                    throw ValidationException::withMessages(['source_batch_id' => 'Alokasi modal sumber perlu difinalkan sebelum sisa bahan digunakan.']);
                }
                $poolCost = (string) ($source->cost_allocation[$data['type']] ?? '0');
                $poolWeight = (string) ($data['type'] === 'Dedek' ? $source->dedek_weight : $source->pupuk_weight);
                $allocatedWeight = (string) $source->children()->where('type', $data['type'])->sum('raw_material_weight');
                $allocatedCost = (string) $source->children()->where('type', $data['type'])->sum('inherited_cost');
                $cumulativeCost = Decimal::money(bcdiv(bcmul($poolCost, bcadd($allocatedWeight, $weight, 3), 8), $poolWeight, 8));
                $inherited = bcsub($cumulativeCost, $allocatedCost, 2);
                $cost = $inherited;
                $data['origin'] = $source->batch_number;
            }
            $batch = ProductionBatch::create([
                'batch_number' => $data['batch_number'], 'name' => $data['name'], 'type' => $data['type'], 'start_date' => $data['start_date'],
                'origin' => $data['origin'], 'status' => 'Proses', 'current_stage' => 'Belum Diproses',
                'raw_material_id' => $data['raw_material_id'] ?: null, 'source_batch_id' => $data['type'] !== 'Beras' ? $data['source_batch_id'] : null,
                'raw_material_weight' => $weight, 'raw_material_cogs' => $cost, 'inherited_cost' => $inherited, 'notes' => $data['notes'],
            ]);
            if ($data['type'] === 'Beras' && bccomp($cost, '0', 2) > 0) {
                $this->addCost($batch, 'Pembelian gabah', 'Pembelian Bahan', $cost, $data['start_date'], $data['purchase_paid'] ? $data['paid_date'] : null, true);
            }

            return $batch;
        }, 3);
    }

    /** @param array<string,mixed> $data */
    public function advance(int $id, string $expectedStage, array $data): void
    {
        Gate::authorize('owner');
        DB::transaction(function () use ($id, $expectedStage, $data): void {
            $batch = ProductionBatch::lockForUpdate()->findOrFail($id);
            if ($batch->current_stage !== $expectedStage) {
                return;
            }
            if ($batch->status === 'Selesai') {
                return;
            }
            Validator::make($data, ['date' => 'required|date_format:Y-m-d', 'notes' => 'nullable|string|max:2000'])->validate();
            if ($data['date'] < substr($batch->start_date, 0, 10)) {
                throw ValidationException::withMessages(['stage_start_date' => 'Tanggal tahap tidak boleh sebelum tanggal penerimaan.']);
            }
            $latestDate = $batch->stages()->max('end_date');
            if ($latestDate && $data['date'] < $latestDate) {
                throw ValidationException::withMessages(['stage_start_date' => 'Tanggal tahap tidak boleh sebelum tahap sebelumnya.']);
            }
            $stages = self::stages($batch->type);
            $position = array_search($expectedStage, $stages, true);
            $next = $stages[$position + 1] ?? 'Selesai';
            $weight = null;
            if ($expectedStage !== 'Belum Diproses') {
                $weight = Decimal::normalize($data['weight'], 3, 'stage_weight_after');
                $input = (string) $batch->raw_material_weight;
                if ($expectedStage === 'Pengeringan') {
                    if (bccomp($weight, $input, 3) > 0) {
                        throw ValidationException::withMessages(['stage_weight_after' => 'Berat kering tidak boleh melebihi berat awal.']);
                    }
                    $batch->dry_weight = $weight;
                } else {
                    $dedek = $pupuk = '0.000';
                    if ($batch->type === 'Beras') {
                        $input = (string) $batch->dry_weight;
                        $dedek = Decimal::normalize($data['dedek'], 3, 'dedek_weight');
                        $pupuk = Decimal::normalize($data['pupuk'], 3, 'pupuk_weight');
                    }
                    if ($batch->type === 'Pupuk') {
                        $mixtures = [];
                        foreach ($data['mixtures'] as $i => $mixture) {
                            Validator::make($mixture, ['name' => 'required|string|max:150', 'paid' => 'boolean'])->validate();
                            $kg = Decimal::normalize($mixture['weight'], 3, 'mixtures.'.$i.'.weight');
                            $mixtureCost = Decimal::normalize($mixture['cost'], 2, 'mixtures.'.$i.'.cost');
                            if (bccomp($kg, '0', 3) <= 0) {
                                throw ValidationException::withMessages(['mixtures' => 'Berat campuran harus positif.']);
                            }
                            $input = bcadd($input, $kg, 3);
                            $mixtures[] = ['name' => $mixture['name'], 'weight' => $kg, 'cost' => $mixtureCost, 'paid' => $mixture['paid']];
                            if (bccomp($mixtureCost, '0', 2) > 0) {
                                $this->addCost($batch, 'Campuran '.$mixture['name'], 'Pembelian Bahan', $mixtureCost, $data['date'], $mixture['paid'] ? $data['date'] : null);
                            }
                        }
                        $batch->mixtures = $mixtures;
                    }
                    if (bccomp(bcadd(bcadd($weight, $dedek, 3), $pupuk, 3), $input, 3) > 0) {
                        throw ValidationException::withMessages(['stage_weight_after' => 'Total hasil dan sisa bahan tidak boleh melebihi bahan masuk beserta campuran.']);
                    }
                    $batch->result_weight = $weight;
                    $batch->dedek_weight = $dedek;
                    $batch->pupuk_weight = $pupuk;
                }
            }
            BatchStage::create(['production_batch_id' => $id, 'stage_name' => $expectedStage, 'start_date' => $batch->stages()->latest('id')->value('end_date') ?: substr($batch->start_date, 0, 10),
                'end_date' => $data['date'], 'weight_after' => $weight, 'notes' => $data['notes']]);
            $batch->current_stage = $next;
            if ($next === 'Selesai') {
                $batch->status = 'Selesai';
                $batch->end_date = $data['date'];
                if (bccomp($this->totalCost($batch), '0', 2) === 0) {
                    $batch->cost_finalized_at = now();
                    $batch->cost_allocation = ['Beras' => '0.00', 'Dedek' => '0.00', 'Pupuk' => '0.00'];
                }
            }
            $batch->save();
        }, 3);
    }

    public function totalCost(ProductionBatch $batch): string
    {
        $costs = (string) DB::table('batch_costs')->where('production_batch_id', $batch->id)->where('is_initial_purchase', false)->sum('amount');

        return bcadd((string) $batch->raw_material_cogs, $costs, 2);
    }

    /** @param array<string,mixed> $amounts */
    public function finalizeCosts(int $id, array $amounts): void
    {
        Gate::authorize('owner');
        DB::transaction(function () use ($id, $amounts): void {
            $batch = ProductionBatch::lockForUpdate()->findOrFail($id);
            if ($batch->cost_finalized_at) {
                return;
            }
            if ($batch->status !== 'Selesai') {
                throw ValidationException::withMessages(['allocation' => 'Selesaikan penimbangan hasil sebelum membagi biaya.']);
            }
            $costs = [];
            $sum = '0.00';
            $weights = $batch->type === 'Beras' ? ['Beras' => $batch->result_weight, 'Dedek' => $batch->dedek_weight, 'Pupuk' => $batch->pupuk_weight] : [$batch->type => $batch->result_weight];
            foreach ($weights as $type => $weight) {
                $costs[$type] = Decimal::normalize($amounts[$type] ?? '0', 2, 'allocation.'.$type);
                if (bccomp((string) $weight, '0', 3) === 0 && bccomp($costs[$type], '0', 2) > 0) {
                    throw ValidationException::withMessages(['allocation.'.$type => 'Hasil dengan berat nol tidak dapat menerima alokasi biaya.']);
                }
                $sum = bcadd($sum, $costs[$type], 2);
            }
            if (bccomp($sum, $this->totalCost($batch), 2) !== 0) {
                throw ValidationException::withMessages(['allocation' => 'Seluruh biaya harus teralokasi tepat sama dengan total biaya batch.']);
            }
            $batch->update(['cost_allocation' => $costs, 'cost_finalized_at' => now()]);
        }, 3);
    }

    public function addCost(ProductionBatch $batch, string $name, string $category, string $amount, string $date, ?string $paidDate, bool $isInitialPurchase = false): void
    {
        if ($batch->cost_finalized_at) {
            throw ValidationException::withMessages(['cost_amount' => 'Biaya batch sudah dikunci.']);
        }
        $id = DB::table('batch_costs')->insertGetId(['production_batch_id' => $batch->id, 'name' => $name, 'category' => $category, 'amount' => $amount, 'cost_date' => $date, 'is_initial_purchase' => $isInitialPurchase, 'created_at' => now(), 'updated_at' => now()]);
        if ($paidDate) {
            $this->payCost($id, $paidDate);
        }
    }

    public function payCost(int $id, string $date): void
    {
        Gate::authorize('owner');
        Validator::make(['date' => $date], ['date' => 'required|date_format:Y-m-d'])->validate();
        DB::transaction(function () use ($id, $date): void {
            $cost = DB::table('batch_costs')->where('id', $id)->lockForUpdate()->first();
            abort_unless($cost, 404);
            if ($cost->paid_at) {
                return;
            }
            $cash = CashEntry::create(['entry_date' => $date, 'type' => 'Pengeluaran', 'name' => $cost->name, 'category' => $cost->category,
                'classification' => 'Produksi', 'production_batch_id' => $cost->production_batch_id, 'amount' => $cost->amount,
                'reference_type' => 'batch_cost', 'reference_id' => $id, 'idempotency_key' => 'batch-cost:'.$id]);
            DB::table('batch_costs')->where('id', $id)->update(['paid_at' => $date, 'cash_entry_id' => $cash->id, 'updated_at' => now()]);
        });
    }

    public function post(int $id, int $productId): ProductionOutput
    {
        Gate::authorize('owner');

        return DB::transaction(function () use ($id, $productId): ProductionOutput {
            $batch = ProductionBatch::lockForUpdate()->findOrFail($id);
            if ($existing = $batch->outputs()->where('is_stocked', true)->first()) {
                return $existing;
            }
            if ($batch->status !== 'Selesai' || bccomp((string) ($batch->result_weight ?? 0), '0', 3) <= 0) {
                throw ValidationException::withMessages(['output_product_id' => 'Produksi harus selesai dengan hasil lebih dari nol.']);
            }
            if (! $batch->cost_finalized_at) {
                throw ValidationException::withMessages(['output_product_id' => 'Finalkan alokasi modal batch sebelum posting hasil.']);
            }
            $product = Product::lockForUpdate()->findOrFail($productId);
            if (! $product->is_active || $product->type !== $batch->type) {
                throw ValidationException::withMessages(['output_product_id' => 'Pilih produk aktif dengan kategori yang sesuai hasil.']);
            }
            $cost = (string) ($batch->cost_allocation[$batch->type] ?? '0');
            $newStock = bcadd((string) $product->stock_kg, (string) $batch->result_weight, 3);
            $newValue = bcadd((string) $product->inventory_value, $cost, 2);
            $output = ProductionOutput::create(['production_batch_id' => $batch->id, 'product_id' => $productId, 'weight' => $batch->result_weight, 'allocated_cost' => $cost, 'is_stocked' => true]);
            DB::table('stock_receipts')->insert(['production_batch_id' => $batch->id, 'production_output_id' => $output->id, 'product_id' => $productId,
                'quantity' => $batch->result_weight, 'cost' => $cost, 'idempotency_key' => 'output:'.$batch->id, 'created_at' => now(), 'updated_at' => now()]);
            $product->update(['stock_kg' => $newStock, 'inventory_value' => $newValue, 'cogs_per_kg' => bcdiv($newValue, $newStock, 6)]);

            return $output;
        }, 3);
    }
}
