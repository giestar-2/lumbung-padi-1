<?php

namespace App\Livewire\Employees;

use App\Decimal;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
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

    public $name = '';

    public $phone = '';

    public $role = '';

    public $salary_type = 'Harian';

    public $default_rate = '0';

    public $notes = '';

    public $is_active = true;

    public $search = '';

    public $status = '';

    public $salaryFilter = '';

    #[Locked]
    public ?int $employeeId = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'salaryFilter'])) {
            $this->resetPage();
        }
    }

    public function edit(int $id): void
    {
        $employee = Employee::findOrFail($id);
        $this->employeeId = $id;
        $this->fill($employee->only('name', 'phone', 'role', 'salary_type', 'default_rate', 'notes', 'is_active'));
        $this->default_rate = Decimal::input($this->default_rate);
        if ($employee->salary_type !== 'Harian') {
            $this->default_rate = '';
        }
    }

    public function save(): void
    {
        $this->default_rate = Decimal::normalize($this->default_rate, 2, 'default_rate');
        $data = $this->validate(['name' => 'required|string|max:150', 'phone' => 'nullable|string|max:30', 'role' => 'nullable|string|max:150',
            'default_rate' => 'required|numeric|gt:0', 'notes' => 'nullable|string|max:2000', 'is_active' => 'boolean']);
        $data['salary_type'] = 'Harian';
        Employee::updateOrCreate(['id' => $this->employeeId], $data);
        $this->resetForm();
        session()->flash('message', 'Data karyawan tersimpan.');
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $employee = Employee::lockForUpdate()->findOrFail($id);
            if ($employee->payrolls()->exists()) {
                throw ValidationException::withMessages(['delete' => 'Karyawan memiliki riwayat gaji. Nonaktifkan bila tidak bekerja lagi.']);
            }
            $employee->delete();
        });
        session()->flash('message', 'Karyawan dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset('name', 'phone', 'role', 'salary_type', 'default_rate', 'notes', 'is_active', 'employeeId');
        $this->resetValidation();
    }

    public function render(): View
    {
        $employees = Employee::where(fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('phone', 'like', '%'.$this->search.'%'))
            ->when($this->status !== '', fn ($q) => $q->where('is_active', $this->status === 'active'))
            ->when($this->salaryFilter, fn ($q) => $q->where('salary_type', $this->salaryFilter))
            ->withCount('payrolls')->orderBy('name')->paginate(25);

        return view('livewire.employees.index', compact('employees'));
    }
}
