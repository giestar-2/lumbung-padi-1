<?php

namespace App\Livewire\Batches;

use App\Models\ProductionBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $type = 'Beras';

    public string $search = '';

    public string $from = '';

    public string $to = '';

    public string $stage = '';

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $batch = ProductionBatch::lockForUpdate()->findOrFail($id);
            if ($batch->source_batch_id || $batch->children()->exists() || $batch->outputs()->exists() || $batch->stages()->exists() ||
                DB::table('batch_costs')->where('production_batch_id', $id)->exists()) {
                throw ValidationException::withMessages(['delete' => 'Batch memiliki tahap, biaya, hasil, atau alokasi bahan dan tidak dapat dihapus.']);
            }
            $batch->delete();
        });
        session()->flash('message', 'Batch dihapus.');
    }

    public function render(): View
    {
        $batches = ProductionBatch::where('type', in_array($this->type, ['Beras', 'Dedek', 'Pupuk']) ? $this->type : 'Beras')
            ->where(fn ($q) => $q->where('batch_number', 'like', '%'.$this->search.'%')->orWhere('name', 'like', '%'.$this->search.'%'))
            ->when($this->from, fn ($q) => $q->whereDate('start_date', '>=', $this->from))->when($this->to, fn ($q) => $q->whereDate('start_date', '<=', $this->to))
            ->when($this->stage, fn ($q) => $q->where('current_stage', $this->stage))
            ->withCount(['outputs', 'children', 'stages'])->withExists('costs')->orderByDesc('start_date')->orderByDesc('id')->paginate(25);

        return view('livewire.batches.index', compact('batches'));
    }
}
