<?php

namespace App\Livewire\Products;

use App\Decimal;
use App\Models\Product;
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

    public $actualStock = '0';

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
        $this->actualStock = $this->previousStock = (string) $product->stock_kg;
        $this->resetValidation();
    }

    public function saveAdjustment(): void
    {
        $stock = Decimal::normalize($this->actualStock, 3, 'actualStock');
        DB::transaction(function () use ($stock): void {
            $product = Product::lockForUpdate()->findOrFail($this->adjustId);
            if (bccomp((string) $product->stock_kg, $this->previousStock, 3) !== 0) {
                throw ValidationException::withMessages(['actualStock' => 'Stok berubah sejak pratinjau dibuka. Buka ulang penyesuaian.']);
            }
            $product->update(['stock_kg' => $stock, 'inventory_value' => Decimal::money(bcmul($stock, (string) $product->cogs_per_kg, 8))]);
        });
        $this->adjustId = null;
        session()->flash('message', 'Stok diperbarui ke jumlah aktual. Tidak ada perubahan kas.');
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $product = Product::lockForUpdate()->findOrFail($id);
            if ($product->saleItems()->exists() || $product->outputs()->exists() || $product->batches()->exists()) {
                throw ValidationException::withMessages(['delete' => 'Produk dipakai dalam penjualan atau produksi. Gunakan Nonaktifkan.']);
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
        $products = $query->withCount(['saleItems', 'outputs', 'batches'])->orderByDesc('id')->paginate(in_array($this->perPage, [10, 25, 50]) ? $this->perPage : 25);

        return view('livewire.products.index', compact('products', 'totalHargaProduk', 'totalHargaModal', 'jumlahProduk', 'produkStokMenipis'));
    }
}
