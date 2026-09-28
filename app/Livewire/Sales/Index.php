<?php

namespace App\Livewire\Sales;

use App\Decimal;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\SalesExport;
use App\SalesHistoryQuery;
use App\SalesLedger;
use App\SummaryCache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $customer = '';

    #[Url]
    public string $product = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $status = '';

    #[Url]
    public ?int $detail = null;

    public int $perPage = 25;

    public string $customerSearch = '';

    public string $productSearch = '';

    public $payment_amount = '0';

    public $payment_date = '';

    public $payment_notes = '';

    #[Locked]
    public string $paymentKey;

    public bool $settle = false;

    public function mount(): void
    {
        $this->payment_date = Decimal::today();
        $this->paymentKey = (string) Str::uuid();
        if (! $this->detail && session('sale_id')) {
            $this->detail = (int) session('sale_id');
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'customer', 'product', 'from', 'to', 'status', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function openDetail(int $id): void
    {
        $sale = Sale::findOrFail($id);
        $this->detail = $id;
        $this->payment_amount = $sale->balance;
        $this->payment_date = Decimal::today();
        $this->payment_notes = '';
        $this->paymentKey = (string) Str::uuid();
        $this->settle = false;
        $this->resetValidation();
    }

    public function recordPayment(SalesLedger $ledger): void
    {
        $this->resetValidation();
        abort_unless($this->detail, 404);
        $ledger->pay($this->detail, $this->payment_amount, $this->payment_date, $this->payment_notes, $this->paymentKey, $this->settle);
        $this->paymentKey = (string) Str::uuid();
        $this->payment_amount = '0';
        session()->flash('message', 'Pembayaran tercatat. Piutang dan pemasukan kas sudah diperbarui.');
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'customer', 'product', 'from', 'to', 'status');
        $this->resetPage();
    }

    public function export(SalesExport $exporter): BinaryFileResponse
    {
        return $exporter->download($this->only('search', 'customer', 'product', 'from', 'to', 'status'));
    }

    public function render(): View
    {
        $query = (new SalesHistoryQuery)->build($this->only('search', 'customer', 'product', 'from', 'to', 'status'));
        [$totalPenjualan, $jumlahTransaksi, $totalDibayar, $sisaPiutang] = SummaryCache::remember('sales', $this->only('search', 'customer', 'product', 'from', 'to', 'status'), function () use ($query): array {
            $totalPenjualan = (clone $query)->sum('total');
            $jumlahTransaksi = (clone $query)->count();
            $totalDibayar = (clone $query)->sum('paid_amount');
            $sisaPiutang = bcsub((string) $totalPenjualan, (string) $totalDibayar, 2);

            return [$totalPenjualan, $jumlahTransaksi, $totalDibayar, $sisaPiutang];
        }, 30);
        $sales = $query->with('customer')->withCount('items')->orderByDesc('sale_date')->orderByDesc('id')->paginate(in_array($this->perPage, [10, 25, 50]) ? $this->perPage : 25);
        $selectedSale = $this->detail ? Sale::with('customer', 'items', 'payments')->findOrFail($this->detail) : null;
        $customers = Customer::where('name', 'like', '%'.$this->customerSearch.'%')->orderBy('name')->limit(30)->get(['id', 'name']);
        if ($this->customer && ! $customers->contains('id', $this->customer) && ($selected = Customer::find($this->customer))) {
            $customers->push($selected);
        }
        $products = Product::where('name', 'like', '%'.$this->productSearch.'%')->orderBy('name')->limit(30)->get(['id', 'name']);
        if ($this->product && ! $products->contains('id', $this->product) && ($selected = Product::find($this->product))) {
            $products->push($selected);
        }

        return view('livewire.sales.index', compact('sales', 'totalPenjualan', 'jumlahTransaksi', 'totalDibayar', 'sisaPiutang', 'selectedSale', 'customers', 'products'));
    }
}
