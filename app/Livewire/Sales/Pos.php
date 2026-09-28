<?php

namespace App\Livewire\Sales;

use App\Decimal;
use App\Models\Customer;
use App\Models\Product;
use App\SalesLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Pos extends Component
{
    use WithPagination;

    public $search = '';

    public $category = '';

    public $cart = [];

    public $customer_id = null;

    public $payment_status = 'Lunas';

    public $discount = 0;

    public $discount_type = 'Rupiah';

    public $paid_amount = 0;

    public $due_date = '';

    public $notes = '';

    public $customer_name = '';

    public $customer_phone = '';

    #[Locked]
    public string $idempotencyKey;

    public function mount(): void
    {
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function addToCart(int $productId): void
    {
        $product = Product::where('is_active', true)->findOrFail($productId);
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['qty'] = bcadd((string) $this->cart[$productId]['qty'], '1', 3);
        } else {
            $this->cart[$productId] = ['id' => $product->id, 'name' => $product->name, 'price' => $product->selling_price, 'qty' => '1.000', 'subtotal' => $product->selling_price];
        }
        $this->calculateCart();
    }

    public function updateQty(int $productId, mixed $qty): void
    {
        if ((string) $qty === '0') {
            unset($this->cart[$productId]);
        } elseif (isset($this->cart[$productId])) {
            $this->cart[$productId]['qty'] = Decimal::normalize($qty, 3, 'cart');
        }
        $this->calculateCart();
    }

    public function removeItem(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function calculateCart(): void
    {
        $products = Product::whereIn('id', array_keys($this->cart))->get()->keyBy('id');
        foreach ($this->cart as $id => $item) {
            if (! isset($products[$id])) {
                unset($this->cart[$id]);

                continue;
            }
            $qty = Decimal::normalize($item['qty'], 3, 'cart');
            $this->cart[$id] = ['id' => $id, 'name' => $products[$id]->name, 'price' => $products[$id]->selling_price, 'qty' => $qty,
                'subtotal' => Decimal::money(bcmul($qty, (string) $products[$id]->selling_price, 8))];
        }
    }

    public function getSubtotalProperty(): string
    {
        return array_reduce($this->cart, fn (string $sum, array $item): string => bcadd($sum, (string) ($item['subtotal'] ?? 0), 2), '0.00');
    }

    public function getTotalProperty(): string
    {
        $input = str_replace(',', '.', (string) $this->discount);
        $discount = preg_match('/^\d{1,12}(?:\.\d{0,2})?$/D', $input) ? $input : '0';
        $value = $this->discount_type === 'Tidak Ada' ? '0' : ($this->discount_type === 'Persen' ? bcdiv(bcmul($this->subtotal, $discount, 8), '100', 8) : $discount);
        $total = bcsub($this->subtotal, Decimal::money($value), 2);

        return bccomp($total, '0', 2) < 0 ? '0.00' : $total;
    }

    public function addCustomer(): void
    {
        $this->validate(['customer_name' => 'required|string|max:150', 'customer_phone' => 'nullable|string|max:30']);
        $this->customer_id = DB::transaction(fn () => Customer::create(['name' => $this->customer_name, 'phone' => $this->customer_phone]))->id;
        $this->reset('customer_name', 'customer_phone');
    }

    public function updatedPaymentStatus(): void
    {
        $this->paid_amount = $this->payment_status === 'Lunas' ? $this->total : '0';
    }

    public function updatedPaidAmount(): void
    {
        $input = str_replace(',', '.', (string) $this->paid_amount);
        if (preg_match('/^\d{1,12}(?:\.\d{0,2})?$/D', $input) !== 1) {
            return;
        }

        if (bccomp($input, $this->total, 2) >= 0) {
            $this->payment_status = 'Lunas';
        } else {
            $this->payment_status = 'Belum Lunas';
        }
    }

    public function setPaymentAmount(mixed $amount): void
    {
        $this->paid_amount = Decimal::normalize($amount, 2, 'paid_amount');
        $this->updatedPaidAmount();
    }

    public function checkout(SalesLedger $ledger): mixed
    {
        $this->resetValidation();
        $sale = $ledger->confirm($this->only(['cart', 'customer_id', 'discount', 'discount_type', 'paid_amount', 'due_date', 'notes']), $this->idempotencyKey);
        session()->flash('message', 'Transaksi '.$sale->invoice_number.' tersimpan. Total Rp '.number_format((float) $sale->total, 0, ',', '.').'.');
        session()->flash('sale_id', $sale->id);

        return redirect()->route('sales.index');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $products = Product::where('is_active', true)->where('stock_kg', '>', 0)
            ->where(fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('code', 'like', '%'.$this->search.'%'))
            ->when($this->category, fn ($q) => $q->where('type', $this->category))->orderBy('name')->paginate(12);
        $customers = Customer::orderBy('name')->limit(50)->get();

        if ($this->customer_id && ! $customers->contains('id', $this->customer_id) && ($selected = Customer::find($this->customer_id))) {
            $customers->push($selected);
        }

        return view('livewire.sales.pos', compact('products', 'customers'));
    }
}
