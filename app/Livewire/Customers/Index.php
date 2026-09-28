<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
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

    public $address = '';

    public $notes = '';

    public $search = '';

    #[Locked]
    public ?int $customerId = null;

    public ?int $detailId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $customer = Customer::findOrFail($id);
        $this->customerId = $id;
        $this->fill($customer->only('name', 'phone', 'address', 'notes'));
    }

    public function save(): void
    {
        $data = $this->validate(['name' => 'required|string|max:150', 'phone' => 'nullable|string|max:30', 'address' => 'nullable|string|max:1000', 'notes' => 'nullable|string|max:2000']);
        DB::transaction(fn () => Customer::updateOrCreate(['id' => $this->customerId], $data));
        $this->resetForm();
        session()->flash('message', 'Data pelanggan tersimpan.');
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $customer = Customer::lockForUpdate()->findOrFail($id);
            if ($customer->sales()->exists()) {
                throw ValidationException::withMessages(['delete' => 'Pelanggan memiliki riwayat penjualan dan tidak dapat dihapus.']);
            }
            $customer->delete();
        });
        session()->flash('message', 'Pelanggan dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset('name', 'phone', 'address', 'notes', 'customerId');
        $this->resetValidation();
    }

    public function render(): View
    {
        $query = Customer::where(fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('phone', 'like', '%'.$this->search.'%'));
        $customers = $query->withCount('sales')->withSum('sales', 'total')->withSum('sales', 'paid_amount')->orderBy('name')->paginate(25);
        $selectedCustomer = $this->detailId ? Customer::withCount('sales')->withSum('sales', 'total')->withSum('sales', 'paid_amount')->findOrFail($this->detailId) : null;

        return view('livewire.customers.index', compact('customers', 'selectedCustomer'));
    }
}
