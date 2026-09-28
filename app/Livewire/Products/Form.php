<?php

namespace App\Livewire\Products;

use App\Decimal;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public $cogs_per_kg = '0';

    public $selling_price = '0';

    public $origin = '';

    public $notes = '';

    public $is_active = true;

    #[Locked]
    public string $oldCost = '0';

    #[Locked]
    public string $currentStock = '0';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $product = Product::findOrFail($id);
            $this->productId = $id;
            $this->fill($product->only('code', 'name', 'type', 'stock_kg', 'stock_minimum', 'cogs_per_kg', 'selling_price', 'origin', 'notes', 'is_active'));
            $this->oldCost = (string) $product->cogs_per_kg;
            $this->currentStock = (string) $product->stock_kg;
        } else {
            $this->code = 'PRD-'.Str::upper(Str::random(6));
        }
    }

    public function save(): mixed
    {
        foreach (['stock_kg' => 3, 'stock_minimum' => 3, 'cogs_per_kg' => 6, 'selling_price' => 2] as $field => $scale) {
            $this->$field = Decimal::normalize($this->$field, $scale, $field);
        }
        $data = $this->validate([
            'code' => 'required|string|max:50|unique:products,code,'.$this->productId,
            'name' => 'required|string|max:150', 'type' => 'required|in:Beras,Dedek,Pupuk,Lainnya,Bahan Baku',
            'stock_kg' => 'required|numeric|min:0', 'stock_minimum' => 'required|numeric|min:0',
            'cogs_per_kg' => 'required|numeric|min:0', 'selling_price' => 'required|numeric|min:0',
            'origin' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:2000', 'is_active' => 'boolean',
        ]);
        DB::transaction(function () use ($data): void {
            if ($this->productId) {
                $product = Product::lockForUpdate()->findOrFail($this->productId);
                unset($data['stock_kg']);
                $data['inventory_value'] = Decimal::money(bcmul((string) $product->stock_kg, $data['cogs_per_kg'], 8));
                $product->update($data);
            } else {
                Product::create($data);
            }
        });
        session()->flash('message', 'Data produk tersimpan.');

        return redirect()->route('products.index');
    }

    public function render(): View
    {
        return view('livewire.products.form');
    }
}
