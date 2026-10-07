<?php

namespace App\Livewire\Products;

use App\Decimal;
use App\Models\CashEntry;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    #[Locked]
    public ?int $productId = null;

    public $code = '';

    public $name = '';

    public $type = 'Beras';

    public $stock_kg = '0';

    public $stock_minimum = '0';

    #[Locked]
    public $cogs_per_kg = '0';

    public $total_cost = '0';

    public $selling_price = '0';

    public $origin = '';

    public $notes = '';

    public $is_active = true;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $product = Product::findOrFail($id);
            $this->productId = $id;
            $this->fill($product->only('code', 'name', 'type', 'stock_kg', 'stock_minimum', 'cogs_per_kg', 'selling_price', 'origin', 'notes', 'is_active'));
            foreach (['stock_kg', 'stock_minimum', 'selling_price'] as $field) {
                $this->$field = Decimal::input($this->$field);
            }
        } else {
            $this->code = 'PRD-'.Str::upper(Str::random(6));
        }
    }

    public function save(): mixed
    {
        foreach (['stock_kg' => 3, 'stock_minimum' => 3, 'selling_price' => 2] as $field => $scale) {
            $this->$field = Decimal::normalize($this->$field, $scale, $field);
        }
        if (! $this->productId) {
            $this->total_cost = Decimal::normalize($this->total_cost, 2, 'total_cost');
            if (bccomp($this->stock_kg, '0', 3) === 0 && bccomp($this->total_cost, '0', 2) > 0) {
                throw ValidationException::withMessages(['stock_kg' => 'Isi stok awal jika ada total belanja.']);
            }
        }
        $data = $this->validate([
            'code' => 'required|string|max:50|unique:products,code,'.$this->productId,
            'name' => 'required|string|max:150', 'type' => 'required|in:Beras,Dedek,Pupuk,Lainnya,Bahan Baku',
            'stock_kg' => 'required|numeric|min:0', 'stock_minimum' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'origin' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:2000', 'is_active' => 'boolean',
        ]);
        DB::transaction(function () use ($data): void {
            if ($this->productId) {
                $product = Product::lockForUpdate()->findOrFail($this->productId);
                unset($data['stock_kg']);
                $product->update($data);
            } else {
                $data['inventory_value'] = $this->total_cost;
                $data['cogs_per_kg'] = bccomp($data['stock_kg'], '0', 3) > 0 ? bcdiv($this->total_cost, $data['stock_kg'], 6) : '0.000000';
                $product = Product::create($data);
                if (bccomp((string) $product->inventory_value, '0', 2) > 0) {
                    CashEntry::create([
                        'entry_date' => Decimal::today(),
                        'type' => 'Pengeluaran',
                        'name' => 'Pembelian '.$product->name,
                        'category' => 'Pembelian Produk',
                        'classification' => 'Persediaan',
                        'amount' => $product->inventory_value,
                        'description' => 'Pembelian stok awal '.$data['stock_kg'].' kg.',
                        'reference_type' => Product::class,
                        'reference_id' => $product->id,
                        'idempotency_key' => 'product-purchase:'.$product->id,
                    ]);
                }
            }
        });
        session()->flash('message', 'Data produk tersimpan.');

        return redirect()->route('products.index');
    }

    public function render(): View
    {
        $initialCostPerKg = null;
        try {
            $stock = Decimal::normalize($this->stock_kg, 3, 'stock_kg');
            $total = Decimal::normalize($this->total_cost, 2, 'total_cost');
            if (bccomp($stock, '0', 3) > 0) {
                $initialCostPerKg = bcdiv($total, $stock, 6);
            }
        } catch (ValidationException) {
            $initialCostPerKg = null;
        }

        return view('livewire.products.form', compact('initialCostPerKg'));
    }
}
