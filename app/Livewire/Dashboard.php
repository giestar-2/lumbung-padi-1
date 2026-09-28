<?php

namespace App\Livewire;

use App\Decimal;
use App\Models\CashEntry;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\SummaryCache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public string $period = 'month';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        $this->from = now('Asia/Jakarta')->startOfMonth()->toDateString();
        $this->to = Decimal::today();
    }

    public function updatedPeriod(): void
    {
        if ($this->period !== 'custom') {
            $this->from = $this->period === 'today' ? Decimal::today() : now('Asia/Jakarta')->startOfMonth()->toDateString();
            $this->to = Decimal::today();
        }
    }

    public function render(): View
    {
        $validRange = validator($this->only('from', 'to'), ['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from'])->passes();
        $from = $validRange ? $this->from : now('Asia/Jakarta')->startOfMonth()->toDateString();
        $to = $validRange ? $this->to : Decimal::today();
        $summary = SummaryCache::remember('dashboard-metrics', ['from' => $from, 'to' => $to, 'today' => Decimal::today()], fn (): array => $this->summary($from, $to));

        return view('livewire.dashboard', $summary + $this->lists($from, $to) + ['validRange' => $validRange]);
    }

    /** @return array<string, mixed> */
    private function summary(string $from, string $to): array
    {
        $sales = Sale::whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to);
        $cash = CashEntry::whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to);
        $penjualanBersih = (clone $sales)->sum('total');
        $pemasukanKas = (clone $cash)->where('type', 'Pemasukan')->sum('amount');
        $pengeluaranKas = (clone $cash)->where('type', 'Pengeluaran')->sum('amount');
        $hpp = SaleItem::whereIn('sale_id', (clone $sales)->select('id'))->sum(DB::raw('COALESCE(hpp, quantity * cogs)'));
        $operational = (clone $cash)->where('type', 'Pengeluaran')->where('classification', 'Operasional')
            ->where(fn ($q) => $q->whereNull('reference_type')->orWhere('reference_type', '!=', Payroll::class))->sum('amount');
        $salaryExpense = Payroll::where('classification', 'Operasional')->where('payment_status', 'Sudah Dibayar')
            ->whereDate('period_end', '>=', $from)->whereDate('period_end', '<=', $to)->sum('total_amount');
        $operational = bcadd((string) $operational, (string) $salaryExpense, 2);
        $labaUsaha = bcsub(bcsub((string) $penjualanBersih, (string) $hpp, 2), (string) $operational, 2);
        $sisaPiutang = bcsub((string) $penjualanBersih, (string) (clone $sales)->sum('paid_amount'), 2);
        $arusKasBersih = bcsub((string) $pemasukanKas, (string) $pengeluaranKas, 2);
        $cashByDate = (clone $cash)->select('entry_date', 'type')->selectRaw('SUM(amount) AS total')->groupBy('entry_date', 'type')->orderBy('entry_date')->get();
        $chart = [];
        foreach ($cashByDate as $row) {
            $chart[$row->entry_date] ??= ['date' => $row->entry_date, 'in' => 0, 'out' => 0];
            $chart[$row->entry_date][$row->type === 'Pemasukan' ? 'in' : 'out'] = (float) $row->total;
        }
        $chartMax = max(array_merge([1], array_column($chart, 'in'), array_column($chart, 'out')));
        $transactionCount = (clone $sales)->count();

        return compact('penjualanBersih', 'pemasukanKas', 'pengeluaranKas', 'labaUsaha', 'sisaPiutang', 'arusKasBersih', 'chart', 'chartMax', 'transactionCount');
    }

    /** @return array<string, mixed> */
    private function lists(string $from, string $to): array
    {
        $recentSales = Sale::whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)
            ->with('customer')->orderByDesc('sale_date')->orderByDesc('id')->limit(6)->get();
        $lowStockProducts = Product::where('is_active', true)->whereColumn('stock_kg', '<=', 'stock_minimum')->orderBy('stock_kg')->limit(5)->get();
        $topProducts = SaleItem::query()->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')->whereDate('sales.sale_date', '>=', $from)->whereDate('sales.sale_date', '<=', $to)
            ->select('products.id', 'products.name', 'products.type')
            ->selectRaw('SUM(COALESCE(sale_items.net_amount, sale_items.subtotal - sale_items.discount_amount) - COALESCE(sale_items.hpp, sale_items.quantity * sale_items.cogs)) AS profit')
            ->selectRaw('SUM(sale_items.quantity) AS quantity_sold')->groupBy('products.id', 'products.name', 'products.type')->orderByDesc('profit')->limit(4)->get();
        $activeBatches = ProductionBatch::whereDate('start_date', '>=', $from)->whereDate('start_date', '<=', $to)->where('status', '!=', 'Selesai')->orderByDesc('start_date')->limit(4)->get();
        $dueSales = Sale::with('customer')->whereNotNull('due_date')->whereColumn('paid_amount', '<', 'total')->orderBy('due_date')->limit(4)->get();

        return compact('recentSales', 'lowStockProducts', 'topProducts', 'activeBatches', 'dueSales');
    }
}
