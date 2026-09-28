<?php

namespace App\Livewire\Batches;

use App\Decimal;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\ProductionWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    #[Locked]
    public int $batchId;

    #[Locked]
    public string $expectedStage;

    public $stage_start_date = '';

    public $stage_weight_after = '';

    public $stage_notes = '';

    public $dedek_weight = '0';

    public $pupuk_weight = '0';

    public $output_product_id = '';

    public $productSearch = '';

    public $new_product_name = '';

    public $new_product_price = '0';

    public $cost_name = '';

    public $cost_amount = '';

    public $cost_category = 'Biaya Pengolahan';

    public $cost_paid = false;

    public $cost_date = '';

    public $batch_name = '';

    public $batch_notes = '';

    public array $mixtures = [];

    public array $allocation = ['Beras' => '0', 'Dedek' => '0', 'Pupuk' => '0'];

    public ProductionBatch $batch;

    public function mount(int $id): void
    {
        $this->batchId = $id;
        $this->reload();
        $this->stage_start_date = $this->cost_date = Decimal::today();
        $this->batch_name = $this->batch->name;
        $this->batch_notes = $this->batch->notes;
    }

    private function reload(): void
    {
        $this->batch = ProductionBatch::with('stages', 'outputs.product', 'costs', 'children')->findOrFail($this->batchId);
        $this->expectedStage = $this->batch->current_stage;
    }

    public function saveMetadata(): void
    {
        $this->validate(['batch_name' => 'nullable|string|max:150', 'batch_notes' => 'nullable|string|max:2000']);
        DB::transaction(fn () => ProductionBatch::findOrFail($this->batchId)->update(['name' => $this->batch_name, 'notes' => $this->batch_notes]));
        $this->reload();
        session()->flash('message', 'Identitas batch diperbarui.');
    }

    public function addMixture(): void
    {
        $this->mixtures[] = ['name' => '', 'weight' => '', 'cost' => '0', 'paid' => false];
    }

    public function removeMixture(int $index): void
    {
        unset($this->mixtures[$index]);
        $this->mixtures = array_values($this->mixtures);
    }

    public function addStage(ProductionWorkflow $workflow): void
    {
        $this->resetValidation();
        $workflow->advance($this->batchId, $this->expectedStage, ['date' => $this->stage_start_date, 'weight' => $this->stage_weight_after,
            'notes' => $this->stage_notes, 'dedek' => $this->dedek_weight, 'pupuk' => $this->pupuk_weight, 'mixtures' => $this->mixtures]);
        $this->reload();
        $this->reset('stage_weight_after', 'stage_notes', 'mixtures');
        session()->flash('message', 'Tahap produksi diperbarui. Stok siap jual belum berubah.');
    }

    public function saveCost(ProductionWorkflow $workflow): void
    {
        $amount = Decimal::normalize($this->cost_amount, 2, 'cost_amount');
        $this->validate(['cost_name' => 'required|string|max:150', 'cost_category' => 'required|in:Pembelian Bahan,Biaya Pengolahan',
            'cost_date' => 'required|date_format:Y-m-d', 'cost_paid' => 'boolean']);
        if (bccomp($amount, '0', 2) <= 0) {
            $this->addError('cost_amount', 'Nominal harus positif.');

            return;
        }
        DB::transaction(function () use ($workflow, $amount): void {
            $batch = ProductionBatch::lockForUpdate()->findOrFail($this->batchId);
            $workflow->addCost($batch, $this->cost_name, $this->cost_category, $amount, $this->cost_date, $this->cost_paid ? $this->cost_date : null);
        });
        $this->reset('cost_name', 'cost_amount', 'cost_paid');
        $this->reload();
        session()->flash('message', 'Komponen biaya tercatat.');
    }

    public function payCost(int $id, ProductionWorkflow $workflow): void
    {
        abort_unless(DB::table('batch_costs')->where('id', $id)->where('production_batch_id', $this->batchId)->exists(), 404);
        $workflow->payCost($id, $this->cost_date);
        $this->reload();
        session()->flash('message', 'Pembayaran biaya tercatat satu kali di pengeluaran.');
    }

    public function createProduct(): void
    {
        $price = Decimal::normalize($this->new_product_price, 2, 'new_product_price');
        $this->validate(['new_product_name' => 'required|string|max:150']);
        $batch = ProductionBatch::findOrFail($this->batchId);
        $this->output_product_id = DB::transaction(fn () => Product::create(['code' => 'PRD-'.Str::upper(Str::random(8)), 'name' => $this->new_product_name,
            'type' => $batch->type, 'stock_kg' => '0', 'selling_price' => $price, 'cogs_per_kg' => '0', 'is_active' => true]))->id;
        $this->reset('new_product_name', 'new_product_price', 'productSearch');
        session()->flash('message', 'Produk dibuat dengan stok nol. Konfirmasi hasil untuk menambah stok.');
    }

    public function addOutput(ProductionWorkflow $workflow): void
    {
        $this->validate(['output_product_id' => 'required|exists:products,id']);
        $workflow->post($this->batchId, (int) $this->output_product_id);
        $this->reload();
        session()->flash('message', 'Hasil produksi sudah masuk stok.');
    }

    public function finalizeCosts(ProductionWorkflow $workflow): void
    {
        $this->resetValidation();
        $workflow->finalizeCosts($this->batchId, $this->allocation);
        $this->reload();
        session()->flash('message', 'Alokasi biaya difinalkan. Hasil dan sisa bahan siap digunakan.');
    }

    public function render(): View
    {
        $this->batch = ProductionBatch::with('stages', 'outputs.product', 'costs', 'children')->findOrFail($this->batchId);
        $outputProducts = Product::where('is_active', true)->where('type', $this->batch->type)
            ->where(fn ($q) => $q->where('name', 'like', '%'.$this->productSearch.'%')->orWhere('id', $this->output_product_id))->orderBy('name')->limit(30)->get();
        $selectedProduct = $outputProducts->firstWhere('id', $this->output_product_id);
        $totalCost = (new ProductionWorkflow)->totalCost($this->batch);

        return view('livewire.batches.show', compact('outputProducts', 'selectedProduct', 'totalCost'));
    }
}
