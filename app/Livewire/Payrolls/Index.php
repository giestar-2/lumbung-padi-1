<?php

namespace App\Livewire\Payrolls;

use App\Decimal;
use App\Models\CashEntry;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\ProductionBatch;
use App\Models\User;
use App\SummaryCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $employee_id = '';

    public $period_start = '';

    public $period_end = '';

    public $payroll_date = '';

    public $total_amount = '';

    public $notes = '';

    public $salary_type = 'Harian';

    public $daily_rate = '0';

    public $work_days = 1;

    public $classification = 'Operasional';

    public $production_batch_id = null;

    public $from = '';

    public $to = '';

    public $employeeFilter = '';

    public $salaryFilter = '';

    public $employeeSearch = '';

    /** @var array<int, array{days: int|string}> */
    public array $cart = [];

    #[Locked]
    public string $idempotencyKey;

    #[Locked]
    public ?int $payrollId = null;

    public bool $confirmSimilar = false;

    public function mount(): void
    {
        $this->period_start = Decimal::today();
        $this->period_end = Decimal::today();
        $this->payroll_date = Decimal::today();
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function updatedEmployeeId(): void
    {
        if ($employee = Employee::where('is_active', true)->find($this->employee_id)) {
            $this->salary_type = $employee->salary_type;
            $this->daily_rate = $employee->default_rate;
            $this->total_amount = $employee->default_rate;
        }
    }

    public function calculateAmount(): void
    {
        $rate = Decimal::normalize($this->daily_rate, 2, 'daily_rate');
        $this->validate(['work_days' => 'required|integer|min:1|max:366']);
        $this->total_amount = bcmul($rate, (string) $this->work_days, 2);
    }

    public function edit(int $id): void
    {
        $this->clearCart();
        $payroll = Payroll::findOrFail($id);
        $this->payrollId = $id;
        $this->fill($payroll->only('employee_id', 'period_start', 'period_end', 'payroll_date', 'total_amount', 'notes', 'salary_type', 'daily_rate', 'work_days', 'classification', 'production_batch_id'));
        $this->daily_rate = Decimal::input($this->daily_rate);
        $this->total_amount = Decimal::input($this->total_amount);
        $this->resetValidation();
    }

    public function resetForm(): void
    {
        $this->reset('payrollId', 'employee_id', 'total_amount', 'notes', 'classification', 'production_batch_id', 'confirmSimilar');
        $this->idempotencyKey = (string) Str::uuid();
        $this->period_start = $this->period_end = $this->payroll_date = Decimal::today();
        $this->resetValidation();
    }

    public function addToCart(int $id): void
    {
        $employee = Employee::where('is_active', true)->where('salary_type', 'Harian')->findOrFail($id);
        if (bccomp((string) $employee->default_rate, '0', 2) <= 0) {
            throw ValidationException::withMessages(['cart' => 'Isi gaji per hari karyawan terlebih dahulu.']);
        }
        if (! isset($this->cart[$id])) {
            $this->cart[$id] = ['days' => 1];
        }
        $this->confirmSimilar = false;
    }

    public function removeItem(int $id): void
    {
        unset($this->cart[$id]);
        $this->confirmSimilar = false;
        $this->resetValidation();
    }

    public function clearCart(): void
    {
        $this->reset('cart', 'confirmSimilar', 'notes');
        $this->idempotencyKey = (string) Str::uuid();
        $this->resetValidation();
    }

    public function checkout(): void
    {
        abort_if($this->payrollId !== null, 403);
        $data = $this->validate([
            'cart' => 'required|array|min:1|max:100', 'cart.*.days' => 'required|integer|min:1|max:366',
            'period_start' => 'required|date_format:Y-m-d', 'period_end' => 'required|date_format:Y-m-d|after_or_equal:period_start',
            'payroll_date' => 'required|date_format:Y-m-d', 'classification' => 'required|in:Operasional,Produksi',
            'production_batch_id' => 'nullable|exists:production_batches,id', 'notes' => 'nullable|string|max:2000',
            'confirmSimilar' => 'boolean',
        ]);
        $daysInPeriod = (int) Carbon::parse($data['period_start'])->diffInDays(Carbon::parse($data['period_end'])) + 1;
        foreach ($this->cart as $id => $item) {
            if (! ctype_digit((string) $id) || (int) $id <= 0 || (int) $item['days'] > $daysInPeriod) {
                throw ValidationException::withMessages(['cart' => 'Periksa karyawan dan jumlah hari kerja. Hari kerja tidak boleh melebihi periode yang dipilih.']);
            }
        }
        if ($data['classification'] === 'Produksi' && ! $data['production_batch_id']) {
            throw ValidationException::withMessages(['production_batch_id' => 'Pilih produksi tujuan untuk biaya gaji.']);
        }
        $batchId = $data['classification'] === 'Produksi' ? $data['production_batch_id'] : null;
        DB::transaction(function () use ($data, $batchId): void {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            $previous = Payroll::where('idempotency_key', 'like', $this->idempotencyKey.':%')->get();
            if ($previous->isNotEmpty()) {
                if ($previous->count() !== count($this->cart)) {
                    throw ValidationException::withMessages(['cart' => 'Keranjang ini sudah dipakai untuk pembayaran lain.']);
                }
                foreach ($previous as $payroll) {
                    if (! isset($this->cart[$payroll->employee_id]) || (int) $payroll->work_days !== (int) $this->cart[$payroll->employee_id]['days'] || $payroll->period_start !== $data['period_start'] || $payroll->period_end !== $data['period_end'] || $payroll->payroll_date !== $data['payroll_date'] || $payroll->classification !== $data['classification'] || (string) $payroll->production_batch_id !== (string) $batchId || (string) $payroll->notes !== (string) $data['notes']) {
                        throw ValidationException::withMessages(['cart' => 'Keranjang ini sudah dipakai untuk pembayaran lain.']);
                    }
                }

                return;
            }
            $employees = Employee::whereIn('id', array_keys($this->cart))->orderBy('id')->lockForUpdate()->get();
            if ($employees->count() !== count($this->cart)) {
                throw ValidationException::withMessages(['cart' => 'Karyawan tidak ditemukan. Periksa kembali keranjang.']);
            }
            if ($batchId) {
                $batch = ProductionBatch::lockForUpdate()->findOrFail($batchId);
                if ($batch->cost_finalized_at || $batch->outputs()->where('is_stocked', true)->exists() || $batch->children()->exists()) {
                    throw ValidationException::withMessages(['production_batch_id' => 'Biaya produksi sudah dikunci. Pilih produksi lain.']);
                }
            }
            foreach ($employees as $employee) {
                if (! $employee->is_active || $employee->salary_type !== 'Harian' || bccomp((string) $employee->default_rate, '0', 2) <= 0) {
                    throw ValidationException::withMessages(['cart' => 'Periksa status dan gaji per hari '.$employee->name.'.']);
                }
                if (! $this->confirmSimilar && $employee->payrolls()->where('period_start', '<=', $data['period_end'])->where('period_end', '>=', $data['period_start'])->exists()) {
                    throw ValidationException::withMessages(['confirmSimilar' => $employee->name.' sudah memiliki pembayaran pada periode ini. Periksa riwayat sebelum melanjutkan.']);
                }
                $days = (int) $this->cart[$employee->id]['days'];
                $payroll = Payroll::create([
                    'employee_id' => $employee->id, 'salary_type' => 'Harian', 'daily_rate' => $employee->default_rate,
                    'work_days' => $days, 'total_amount' => bcmul((string) $employee->default_rate, (string) $days, 2),
                    'period_start' => $data['period_start'], 'period_end' => $data['period_end'], 'payroll_date' => $data['payroll_date'],
                    'payment_status' => 'Sudah Dibayar', 'classification' => $data['classification'], 'production_batch_id' => $batchId,
                    'notes' => $data['notes'], 'idempotency_key' => $this->idempotencyKey.':'.$employee->id,
                ]);
                $this->syncCash($payroll, $employee->name);
            }
        }, 3);
        $count = count($this->cart);
        $this->clearCart();
        session()->flash('message', 'Gaji '.$count.' karyawan dibayar dan tercatat di Pengeluaran.');
    }

    public function save(): void
    {
        if (! $this->payrollId) {
            throw ValidationException::withMessages(['cart' => 'Pilih karyawan melalui kartu lalu gunakan tombol Bayar gaji.']);
        }
        $this->total_amount = Decimal::normalize($this->total_amount, 2, 'total_amount');
        $this->daily_rate = Decimal::normalize($this->daily_rate, 2, 'daily_rate');
        $data = $this->validate(['employee_id' => 'required|exists:employees,id', 'period_start' => 'required|date_format:Y-m-d',
            'period_end' => 'required|date_format:Y-m-d|after_or_equal:period_start', 'payroll_date' => 'required|date_format:Y-m-d',
            'total_amount' => 'required|numeric|gt:0', 'salary_type' => 'required|in:Harian,Bulanan', 'daily_rate' => 'numeric|min:0',
            'work_days' => 'required|integer|min:1|max:366', 'classification' => 'required|in:Operasional,Produksi',
            'production_batch_id' => 'nullable|exists:production_batches,id', 'notes' => 'nullable|string|max:2000']);
        if ($data['classification'] === 'Produksi' && ! $data['production_batch_id']) {
            $this->addError('production_batch_id', 'Pilih batch biaya produksi.');

            return;
        }
        if ($data['classification'] === 'Operasional') {
            $data['production_batch_id'] = null;
        }
        DB::transaction(function () use ($data): void {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            $employee = Employee::lockForUpdate()->findOrFail($data['employee_id']);
            $payroll = Payroll::lockForUpdate()->findOrFail($this->payrollId);
            foreach (array_unique(array_filter([$payroll?->production_batch_id, $data['production_batch_id']])) as $id) {
                $batch = ProductionBatch::lockForUpdate()->findOrFail($id);
                if ($batch->cost_finalized_at || $batch->outputs()->where('is_stocked', true)->exists() || $batch->children()->exists()) {
                    throw ValidationException::withMessages(['production_batch_id' => 'Biaya batch sudah dikunci.']);
                }
            }
            $data['payment_status'] = 'Sudah Dibayar';
            $payroll->update($data);
            $this->syncCash($payroll, $employee->name);
        }, 3);
        $this->resetForm();
        session()->flash('message', 'Pembayaran gaji dan pengeluaran terkait tersimpan.');
    }

    public function pay(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $payroll = Payroll::lockForUpdate()->findOrFail($id);
            if ($payroll->payment_status === 'Sudah Dibayar') {
                return;
            }
            $employee = Employee::lockForUpdate()->findOrFail($payroll->employee_id);
            if (! $employee->is_active) {
                throw ValidationException::withMessages(['employee_id' => 'Karyawan nonaktif tidak dapat menerima pembayaran baru.']);
            }
            if ($payroll->classification === 'Produksi') {
                $batch = ProductionBatch::lockForUpdate()->findOrFail($payroll->production_batch_id);
                if ($batch->cost_finalized_at) {
                    throw ValidationException::withMessages(['production_batch_id' => 'Biaya batch sudah dikunci.']);
                }
            }
            $payroll->update(['payment_status' => 'Sudah Dibayar', 'payroll_date' => Decimal::today()]);
            $this->syncCash($payroll, $employee->name);
        });
        session()->flash('message', 'Gaji dibayar dan dicatat ke pengeluaran.');
    }

    private function syncCash(Payroll $payroll, string $employeeName): void
    {
        $cash = CashEntry::updateOrCreate(['reference_type' => Payroll::class, 'reference_id' => $payroll->id], [
            'entry_date' => $payroll->payroll_date, 'type' => 'Pengeluaran', 'name' => 'Gaji '.$employeeName, 'category' => 'Gaji Pekerja',
            'amount' => $payroll->total_amount, 'description' => $payroll->notes, 'classification' => $payroll->classification,
            'production_batch_id' => $payroll->production_batch_id, 'idempotency_key' => 'payroll:'.$payroll->id]);
        DB::table('batch_costs')->where('cash_entry_id', $cash->id)->delete();
        if ($payroll->classification === 'Produksi') {
            DB::table('batch_costs')->insert(['production_batch_id' => $payroll->production_batch_id, 'name' => 'Gaji '.$employeeName,
                'category' => 'Gaji', 'amount' => $payroll->total_amount, 'cost_date' => $payroll->period_end, 'paid_at' => $payroll->payroll_date,
                'cash_entry_id' => $cash->id, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $payroll = Payroll::lockForUpdate()->findOrFail($id);
            if (CashEntry::where('reference_type', Payroll::class)->where('reference_id', $id)->exists()) {
                throw ValidationException::withMessages(['delete' => 'Gaji sudah terhubung dengan pengeluaran. Koreksi melalui Edit.']);
            }
            $payroll->delete();
        });
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['from', 'to', 'employeeFilter', 'salaryFilter'])) {
            $this->resetPage();
        }
        if ($property === 'employeeSearch') {
            $this->resetPage('employeesPage');
        }
        if (str_starts_with($property, 'cart.') || in_array($property, ['period_start', 'period_end'])) {
            $this->confirmSimilar = false;
        }
    }

    public function render(): View
    {
        $query = Payroll::query()->when($this->from, fn ($q) => $q->whereDate('payroll_date', '>=', $this->from))->when($this->to, fn ($q) => $q->whereDate('payroll_date', '<=', $this->to))
            ->when($this->employeeFilter, fn ($q) => $q->where('employee_id', $this->employeeFilter))->when($this->salaryFilter, fn ($q) => $q->where('salary_type', $this->salaryFilter));
        [$totalGajiDibayar, $totalGajiHarian, $totalGajiBulanan, $jumlahKaryawanDibayar] = SummaryCache::remember('payrolls', $this->only('from', 'to', 'employeeFilter', 'salaryFilter'), function () use ($query): array {
            $paid = (clone $query)->where('payment_status', 'Sudah Dibayar');
            $totalGajiDibayar = (clone $paid)->sum('total_amount');
            $totalGajiHarian = (clone $paid)->where('salary_type', 'Harian')->sum('total_amount');
            $totalGajiBulanan = (clone $paid)->where('salary_type', 'Bulanan')->sum('total_amount');
            $jumlahKaryawanDibayar = (clone $paid)->distinct()->count('employee_id');

            return [$totalGajiDibayar, $totalGajiHarian, $totalGajiBulanan, $jumlahKaryawanDibayar];
        }, 30);
        $payrolls = $query->with('employee')->orderByDesc('payroll_date')->orderByDesc('id')->paginate(25);
        $employees = Employee::where(fn ($q) => $q->where('name', 'like', '%'.$this->employeeSearch.'%')->orWhere('id', $this->employee_id)->orWhere('id', $this->employeeFilter))->orderBy('name')->limit(30)->get();
        foreach (array_filter([$this->employee_id, $this->employeeFilter]) as $selectedId) {
            if (! $employees->contains('id', $selectedId) && ($selected = Employee::find($selectedId))) {
                $employees->push($selected);
            }
        }
        $batches = ProductionBatch::whereNull('cost_finalized_at')->orderByDesc('id')->limit(50)->get();

        $availableEmployees = Employee::where('is_active', true)->where('salary_type', 'Harian')->where('default_rate', '>', 0)
            ->where(fn ($q) => $q->where('name', 'like', '%'.$this->employeeSearch.'%')->orWhere('role', 'like', '%'.$this->employeeSearch.'%'))
            ->orderBy('name')->paginate(12, ['*'], 'employeesPage');
        $cartEmployees = Employee::whereIn('id', array_keys($this->cart))->get()->keyBy('id');
        $cartTotal = '0.00';
        $cartLines = [];
        foreach ($this->cart as $id => $item) {
            $employee = $cartEmployees->get($id);
            if (! $employee) {
                continue;
            }
            $days = filter_var($item['days'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 366]]);
            $amount = $days !== false ? bcmul((string) $employee->default_rate, (string) $days, 2) : '0.00';
            $cartTotal = bcadd($cartTotal, $amount, 2);
            $cartLines[$id] = ['name' => $employee->name, 'rate' => $employee->default_rate, 'amount' => $amount];
        }

        return view('livewire.payrolls.index', compact('payrolls', 'employees', 'availableEmployees', 'cartLines', 'cartTotal', 'batches', 'totalGajiDibayar', 'totalGajiHarian', 'totalGajiBulanan', 'jumlahKaryawanDibayar'));
    }
}
