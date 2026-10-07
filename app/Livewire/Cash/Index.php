<?php

namespace App\Livewire\Cash;

use App\Decimal;
use App\Models\CashEntry;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\SummaryCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $direction = 'Pemasukan';

    public $entry_date = '';

    public $type = 'Pemasukan';

    public $name = '';

    public $category = '';

    public $amount = '';

    public $description = '';

    public $classification = 'Operasional';

    public $production_batch_id = null;

    public $search = '';

    public $from = '';

    public $to = '';

    public $categoryFilter = '';

    public $source = '';

    #[Locked]
    public ?int $entryId = null;

    public function mount(): void
    {
        $this->entry_date = Decimal::today();
        $this->type = $this->direction === 'Pengeluaran' ? 'Pengeluaran' : 'Pemasukan';
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['direction', 'search', 'from', 'to', 'categoryFilter', 'source'])) {
            $this->resetPage();
        }
    }

    public function edit(int $id): void
    {
        $entry = CashEntry::findOrFail($id);
        abort_if($entry->reference_type, 403);
        $this->entryId = $id;
        $this->fill($entry->only('entry_date', 'type', 'name', 'category', 'amount', 'description', 'classification', 'production_batch_id'));
        $this->amount = Decimal::input($this->amount);
    }

    public function resetForm(): void
    {
        $this->reset('entryId', 'name', 'category', 'amount', 'description', 'classification', 'production_batch_id');
        $this->entry_date = Decimal::today();
        $this->type = $this->direction;
    }

    public function save(): void
    {
        $this->amount = Decimal::normalize($this->amount, 2, 'amount');
        $entryType = $this->direction === 'Pengeluaran' ? 'Pengeluaran' : 'Pemasukan';
        $this->type = $entryType;
        $data = $this->validate(['entry_date' => 'required|date_format:Y-m-d', 'name' => 'required|string|max:150',
            'category' => 'required|string|max:100', 'amount' => 'required|numeric|gt:0', 'description' => 'nullable|string|max:2000',
            'classification' => 'required|in:Operasional,Produksi', 'production_batch_id' => 'nullable|exists:production_batches,id']);
        $data['type'] = $entryType;
        if ($entryType === 'Pemasukan') {
            $data['classification'] = 'Operasional';
            $data['production_batch_id'] = null;
        }
        if ($data['classification'] === 'Produksi' && ! $data['production_batch_id']) {
            $this->addError('production_batch_id', 'Pilih batch untuk biaya produksi.');

            return;
        }
        if ($entryType === 'Pemasukan' && in_array($data['category'], ['Penjualan Langsung', 'Penerimaan Piutang', 'Penjualan'])) {
            $this->addError('category', 'Kategori ini khusus transaksi otomatis. Gunakan Modal Pemilik atau Pemasukan Lainnya.');

            return;
        }
        DB::transaction(function () use ($data): void {
            $entry = $this->entryId ? CashEntry::lockForUpdate()->findOrFail($this->entryId) : null;
            abort_if($entry?->reference_type, 403);
            foreach (array_unique(array_filter([$entry?->production_batch_id, $data['production_batch_id']])) as $batchId) {
                $batch = ProductionBatch::lockForUpdate()->findOrFail($batchId);
                if ($batch->cost_finalized_at || $batch->outputs()->where('is_stocked', true)->exists() || $batch->children()->exists()) {
                    throw ValidationException::withMessages(['production_batch_id' => 'Biaya batch sudah dikunci karena dipakai hasil atau alokasi.']);
                }
            }
            $entry = $entry ? tap($entry)->update($data) : CashEntry::create($data);
            DB::table('batch_costs')->where('cash_entry_id', $entry->id)->delete();
            if ($data['classification'] === 'Produksi') {
                DB::table('batch_costs')->insert(['production_batch_id' => $data['production_batch_id'], 'name' => $data['name'], 'category' => $data['category'],
                    'amount' => $data['amount'], 'cost_date' => $data['entry_date'], 'paid_at' => $data['entry_date'], 'cash_entry_id' => $entry->id, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
        $this->resetForm();
        session()->flash('message', 'Catatan kas tersimpan.');
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $entry = CashEntry::lockForUpdate()->findOrFail($id);
            abort_if($entry->reference_type, 403);
            if (DB::table('batch_costs')->where('cash_entry_id', $id)->exists()) {
                throw ValidationException::withMessages(['delete' => 'Pengeluaran terhubung biaya batch dan tidak dapat dihapus.']);
            }
            $entry->delete();
        });
        session()->flash('message', 'Catatan manual dihapus.');
    }

    public function render(): View
    {
        $query = CashEntry::query()->where(fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('description', 'like', '%'.$this->search.'%')->orWhere('category', 'like', '%'.$this->search.'%'))
            ->when($this->from, fn ($q) => $q->whereDate('entry_date', '>=', $this->from))->when($this->to, fn ($q) => $q->whereDate('entry_date', '<=', $this->to))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->source === 'manual', fn ($q) => $q->whereNull('reference_type'))->when($this->source === 'automatic', fn ($q) => $q->whereNotNull('reference_type'))
            ->when($this->source === 'pos', fn ($q) => $q->where('category', 'Penjualan Langsung')->whereNotNull('reference_type'))
            ->when($this->source === 'receivable', fn ($q) => $q->where('category', 'Penerimaan Piutang')->whereNotNull('reference_type'))
            ->when($this->source === 'payroll', fn ($q) => $q->where('reference_type', Payroll::class))
            ->when($this->source === 'product', fn ($q) => $q->where('reference_type', Product::class))
            ->when($this->source === 'production', fn ($q) => $q->where('reference_type', 'batch_cost'));
        [$totalPemasukan, $totalPengeluaran, $penjualanLangsung, $penerimaanPiutang, $pemasukanLainnya, $pembelianBahan, $biayaPengolahan, $gajiOperasional, $saldo] = SummaryCache::remember('cash', $this->only('search', 'from', 'to', 'categoryFilter', 'source'), function () use ($query): array {
            $incoming = (clone $query)->where('type', 'Pemasukan');
            $outgoing = (clone $query)->where('type', 'Pengeluaran');
            $totalPemasukan = (clone $incoming)->sum('amount');
            $totalPengeluaran = (clone $outgoing)->sum('amount');
            $penjualanLangsung = (clone $incoming)->where('category', 'Penjualan Langsung')->sum('amount');
            $penerimaanPiutang = (clone $incoming)->where('category', 'Penerimaan Piutang')->sum('amount');
            $pemasukanLainnya = bcsub(bcsub((string) $totalPemasukan, (string) $penjualanLangsung, 2), (string) $penerimaanPiutang, 2);
            $pembelianBahan = (clone $outgoing)->whereIn('category', ['Pembelian Bahan', 'Pembelian Produk'])->sum('amount');
            $biayaPengolahan = (clone $outgoing)->where('category', 'Biaya Pengolahan')->sum('amount');
            $gajiOperasional = bcsub(bcsub((string) $totalPengeluaran, (string) $pembelianBahan, 2), (string) $biayaPengolahan, 2);
            $saldo = bcsub((string) $totalPemasukan, (string) $totalPengeluaran, 2);

            return [$totalPemasukan, $totalPengeluaran, $penjualanLangsung, $penerimaanPiutang, $pemasukanLainnya, $pembelianBahan, $biayaPengolahan, $gajiOperasional, $saldo];
        }, 30);
        $entries = $query->where('type', $this->direction === 'Pengeluaran' ? 'Pengeluaran' : 'Pemasukan')->orderByDesc('entry_date')->orderByDesc('id')->paginate(25);
        $paymentSales = Payment::whereIn('id', $entries->where('reference_type', Payment::class)->pluck('reference_id'))->pluck('sale_id', 'id');
        $linkedEntries = DB::table('batch_costs')->whereIn('cash_entry_id', $entries->pluck('id'))->pluck('cash_entry_id')->all();
        $categories = CashEntry::where('type', $this->direction)->distinct()->orderBy('category')->pluck('category');
        $batches = ProductionBatch::whereNull('cost_finalized_at')->orderByDesc('id')->limit(50)->get();

        return view('livewire.cash.index', compact('entries', 'totalPemasukan', 'totalPengeluaran', 'penjualanLangsung', 'penerimaanPiutang', 'pemasukanLainnya', 'pembelianBahan', 'biayaPengolahan', 'gajiOperasional', 'saldo', 'categories', 'batches', 'paymentSales', 'linkedEntries'));
    }
}
