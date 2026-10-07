<?php

namespace App\Livewire\Products;

use App\Decimal;
use App\Models\CashEntry;
use App\Models\Product;
use App\SummaryCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $status = '';

    #[Url]
    public bool $lowStock = false;

    #[Locked]
    public ?int $adjustId = null;

    #[Locked]
    public string $previousStock = '0';

    #[Locked]
    public string $previousValue = '0';

    #[Locked]
    public string $previousCost = '0';

    #[Locked]
    public string $stockProductName = '';

    #[Locked]
    public string $purchaseKey = '';

    public $incomingStock = '';

    public $purchaseTotal = '';

    public int $perPage = 25;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'category', 'status', 'lowStock', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function toggle(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $product = Product::lockForUpdate()->findOrFail($id);
            $product->update(['is_active' => ! $product->is_active]);
        });
    }

    public function adjust(int $id): void
    {
        $product = Product::findOrFail($id);
        $this->adjustId = $id;
        $this->previousStock = (string) $product->stock_kg;
        $this->previousValue = (string) $product->inventory_value;
        $this->previousCost = (string) $product->cogs_per_kg;
        $this->stockProductName = $product->name;
        $this->purchaseKey = (string) Str::uuid();
        $this->reset('incomingStock', 'purchaseTotal');
        $this->resetValidation();
    }

    public function cancelAdjustment(): void
    {
        $this->adjustId = null;
        $this->resetValidation();
    }

    public function saveAdjustment(): void
    {
        if (! $this->adjustId) {
            return;
        }
        $stock = Decimal::normalize($this->incomingStock, 3, 'incomingStock');
        if (bccomp($stock, '0', 3) <= 0) {
            throw ValidationException::withMessages(['incomingStock' => 'Stok masuk harus lebih dari 0 kg.']);
        }
        $total = trim((string) $this->purchaseTotal) === '' ? Decimal::money(bcmul($stock, $this->previousCost, 8)) : Decimal::normalize($this->purchaseTotal, 2, 'purchaseTotal');
        DB::transaction(function () use ($stock, $total): void {
            $product = Product::lockForUpdate()->findOrFail($this->adjustId);
            if (CashEntry::where('idempotency_key', $this->purchaseKey)->exists()) {
                return;
            }
            if (bccomp((string) $product->stock_kg, $this->previousStock, 3) !== 0 || bccomp((string) $product->inventory_value, $this->previousValue, 2) !== 0 || bccomp((string) $product->cogs_per_kg, $this->previousCost, 6) !== 0) {
                throw ValidationException::withMessages(['incomingStock' => 'Stok atau modal berubah. Tutup lalu buka kembali form stok masuk.']);
            }
            $newStock = bcadd((string) $product->stock_kg, $stock, 3);
            $newValue = bcadd((string) $product->inventory_value, $total, 2);
            $product->update(['stock_kg' => $newStock, 'inventory_value' => $newValue, 'cogs_per_kg' => bcdiv($newValue, $newStock, 6)]);
            if (bccomp($total, '0', 2) > 0) {
                CashEntry::create([
                    'entry_date' => Decimal::today(), 'type' => 'Pengeluaran',
                    'name' => 'Pembelian '.$product->name, 'category' => 'Pembelian Produk',
                    'classification' => 'Persediaan', 'amount' => $total,
                    'description' => 'Pembelian stok masuk '.$stock.' kg.',
                    'reference_type' => Product::class, 'reference_id' => $product->id,
                    'idempotency_key' => $this->purchaseKey,
                ]);
            }
        });
        $this->adjustId = null;
        session()->flash('message', 'Stok masuk tersimpan dan modal rata-rata diperbarui. Pengeluaran dicatat sesuai total belanja.');
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $product = Product::lockForUpdate()->findOrFail($id);
            if ($product->saleItems()->exists() || $product->outputs()->exists() || $product->batches()->exists() || $product->cashEntries()->exists()) {
                throw ValidationException::withMessages(['delete' => 'Produk memiliki riwayat penjualan, produksi, atau pengeluaran. Gunakan Nonaktifkan.']);
            }
            $product->delete();
        });
        session()->flash('message', 'Produk dihapus.');
    }

    public function render(): View
    {
        $query = Product::where(fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('code', 'like', '%'.$this->search.'%'))
            ->when($this->category, fn ($q) => $q->where('type', $this->category))
            ->when($this->status !== '', fn ($q) => $q->where('is_active', $this->status === 'active'))
            ->when($this->lowStock, fn ($q) => $q->where('is_active', true)->whereColumn('stock_kg', '<=', 'stock_minimum'));
        [$totalHargaProduk, $totalHargaModal, $jumlahProduk, $produkStokMenipis] = SummaryCache::remember('products', $this->only('search', 'category', 'status', 'lowStock'), function () use ($query): array {
            $totalHargaProduk = (clone $query)->sum(DB::raw('stock_kg * selling_price'));
            $totalHargaModal = (clone $query)->sum('inventory_value');
            $jumlahProduk = (clone $query)->count();
            $produkStokMenipis = (clone $query)->where('is_active', true)->whereColumn('stock_kg', '<=', 'stock_minimum')->count();

            return [$totalHargaProduk, $totalHargaModal, $jumlahProduk, $produkStokMenipis];
        }, 30);
        $products = $query->withCount(['saleItems', 'outputs', 'batches', 'cashEntries'])->orderByDesc('id')->paginate(in_array($this->perPage, [10, 25, 50]) ? $this->perPage : 25);

        $purchasePreview = null;
        if ($this->adjustId) {
            try {
                $incoming = Decimal::normalize($this->incomingStock, 3, 'incomingStock');
                if (bccomp($incoming, '0', 3) > 0) {
                    $total = trim((string) $this->purchaseTotal) === '' ? Decimal::money(bcmul($incoming, $this->previousCost, 8)) : Decimal::normalize($this->purchaseTotal, 2, 'purchaseTotal');
                    $stock = bcadd($this->previousStock, $incoming, 3);
                    $average = bcdiv(bcadd($this->previousValue, $total, 2), $stock, 6);
                    $purchasePreview = compact('total', 'stock', 'average');
                }
            } catch (ValidationException) {
                $purchasePreview = null;
            }
        }

        return view('livewire.products.index', compact('products', 'totalHargaProduk', 'totalHargaModal', 'jumlahProduk', 'produkStokMenipis', 'purchasePreview'));
    }
}
