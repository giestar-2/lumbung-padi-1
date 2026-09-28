<?php

namespace App\Livewire\Batches;

use App\Decimal;
use App\Models\ProductionBatch;
use App\ProductionWorkflow;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    #[Url]
    public string $type = 'Beras';

    public $raw_material_id = null;

    public $raw_material_weight = '';

    public $start_date = '';

    public $notes = '';

    public $name = '';

    public $origin = '';

    public $batch_number = '';

    public $source_batch_id = null;

    public string $sourceSearch = '';

    public $purchase_price = '0';

    public $purchase_paid = false;

    public $paid_date = '';

    public function mount(): void
    {
        abort_unless(in_array($this->type, ['Beras', 'Dedek', 'Pupuk']), 404);
        $this->start_date = $this->paid_date = Decimal::today();
        $this->batch_number = 'B-'.now('Asia/Jakarta')->format('ymd').'-'.Str::upper(Str::random(6));
    }

    public function save(ProductionWorkflow $workflow): mixed
    {
        $this->validate(['type' => 'required|in:Beras,Dedek,Pupuk', 'batch_number' => 'required|string|max:50|unique:production_batches,batch_number',
            'name' => 'nullable|string|max:150', 'origin' => 'nullable|string|max:255', 'start_date' => 'required|date_format:Y-m-d',
            'source_batch_id' => 'required_unless:type,Beras|nullable|exists:production_batches,id', 'raw_material_id' => 'nullable|exists:products,id',
            'notes' => 'nullable|string|max:2000', 'purchase_paid' => 'boolean', 'paid_date' => 'required_if:purchase_paid,true|nullable|date_format:Y-m-d']);
        $batch = $workflow->create($this->only('type', 'batch_number', 'name', 'origin', 'start_date', 'source_batch_id', 'raw_material_id', 'notes', 'purchase_paid', 'paid_date', 'raw_material_weight', 'purchase_price'));
        session()->flash('message', 'Batch diterima. Stok produk siap jual tidak berubah.');

        return redirect()->route('batches.show', ['id' => $batch->id, 'type' => $batch->type]);
    }

    public function render(): View
    {
        $sources = ProductionBatch::where('type', 'Beras')->where('status', 'Selesai')->where(fn ($q) => $q->where('batch_number', 'like', '%'.$this->sourceSearch.'%')->orWhere('name', 'like', '%'.$this->sourceSearch.'%')->orWhere('id', $this->source_batch_id))->withSum(['children as used_dedek' => fn ($q) => $q->where('type', 'Dedek')], 'raw_material_weight')
            ->withSum(['children as used_pupuk' => fn ($q) => $q->where('type', 'Pupuk')], 'raw_material_weight')->orderByDesc('id')->limit(50)->get();
        $selectedSource = $sources->firstWhere('id', $this->source_batch_id);

        return view('livewire.batches.form', compact('sources', 'selectedSource'));
    }
}
