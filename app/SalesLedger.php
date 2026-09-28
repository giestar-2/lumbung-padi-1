<?php

namespace App;

use App\Models\CashEntry;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SalesLedger
{
    /** @param array{cart: array, customer_id: mixed, discount: mixed, discount_type: string, paid_amount: mixed, due_date: mixed, notes: string} $data */
    public function confirm(array $data, string $key): Sale
    {
        Gate::authorize('owner');
        Validator::make($data, [
            'cart' => 'required|array|min:1|max:100', 'customer_id' => 'nullable|exists:customers,id',
            'discount_type' => 'required|in:Tidak Ada,Persen,Rupiah', 'due_date' => 'nullable|date_format:Y-m-d',
            'notes' => 'nullable|string|max:2000',
        ])->validate();
        $quantities = [];
        foreach ($data['cart'] as $id => $item) {
            $quantities[(int) $id] = Decimal::normalize($item['qty'] ?? '', 3, 'cart');
        }
        ksort($quantities);
        $discountInput = Decimal::normalize($data['discount'], 2, 'discount');
        $paid = Decimal::normalize($data['paid_amount'], 2, 'paid_amount');
        $hash = hash('sha256', json_encode([$quantities, $data['customer_id'], $discountInput, $data['discount_type'], $paid, $data['due_date'], $data['notes']]));

        return DB::transaction(function () use ($data, $key, $quantities, $discountInput, $paid, $hash): Sale {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            if ($previous = Sale::where('idempotency_key', $key)->first()) {
                if ($previous->request_hash !== $hash) {
                    throw ValidationException::withMessages(['cart' => 'Konfirmasi ini sudah digunakan. Mulai transaksi baru.']);
                }

                return $previous;
            }
            $products = Product::whereIn('id', array_keys($quantities))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $subtotal = '0.00';
            $lines = [];
            foreach ($quantities as $id => $quantity) {
                $product = $products->get($id);
                if (! $product || ! $product->is_active || bccomp($quantity, '0', 3) <= 0 || bccomp((string) $product->stock_kg, $quantity, 3) < 0) {
                    throw ValidationException::withMessages(['cart' => 'Produk tidak aktif atau stok tidak cukup. Periksa jumlah kg.']);
                }
                $amount = Decimal::money(bcmul($quantity, (string) $product->selling_price, 8));
                $hpp = bccomp($quantity, (string) $product->stock_kg, 3) === 0 ? (string) $product->inventory_value : Decimal::money(bcmul($quantity, (string) $product->cogs_per_kg, 8));
                if (bccomp($hpp, (string) $product->inventory_value, 2) > 0) {
                    $hpp = (string) $product->inventory_value;
                }
                $lines[$id] = ['product_id' => $id, 'product_name' => $product->name, 'product_category' => $product->type,
                    'quantity' => $quantity, 'price' => $product->selling_price, 'cogs' => $product->cogs_per_kg,
                    'subtotal' => $amount, 'hpp' => $hpp];
                $subtotal = bcadd($subtotal, $amount, 2);
            }
            if ($data['discount_type'] === 'Persen' && bccomp($discountInput, '100', 2) > 0) {
                throw ValidationException::withMessages(['discount' => 'Diskon persen maksimal 100.']);
            }
            $discount = match ($data['discount_type']) {
                'Tidak Ada' => '0.00',
                'Persen' => Decimal::money(bcdiv(bcmul($subtotal, $discountInput, 8), '100', 8)),
                default => $discountInput,
            };
            if (bccomp($discount, $subtotal, 2) > 0) {
                throw ValidationException::withMessages(['discount' => 'Diskon tidak boleh melebihi subtotal.']);
            }
            $total = bcsub($subtotal, $discount, 2);
            if (bccomp($paid, $total, 2) > 0) {
                throw ValidationException::withMessages(['paid_amount' => 'Pembayaran tidak boleh melebihi total tagihan.']);
            }
            if (bccomp($paid, $total, 2) < 0 && empty($data['customer_id'])) {
                throw ValidationException::withMessages(['customer_id' => 'Pelanggan wajib untuk transaksi dengan sisa piutang.']);
            }
            $allocations = $this->allocateDiscount($lines, $subtotal, $discount);
            $sale = Sale::create([
                'invoice_number' => 'INV-'.now('Asia/Jakarta')->format('ymd').'-'.Str::upper(Str::random(8)),
                'sale_date' => Decimal::today(), 'customer_id' => $data['customer_id'] ?: null,
                'subtotal' => $subtotal, 'discount' => $discount, 'discount_type' => $data['discount_type'],
                'total' => $total, 'paid_amount' => $paid, 'payment_status' => bccomp($total, $paid, 2) === 0 ? 'Lunas' : 'Belum Lunas',
                'due_date' => $data['due_date'] ?: null, 'notes' => $data['notes'], 'idempotency_key' => $key, 'request_hash' => $hash,
            ]);
            foreach ($lines as $id => $line) {
                $sale->items()->create($line + ['discount_amount' => $allocations[$id], 'net_amount' => bcsub($line['subtotal'], $allocations[$id], 2)]);
                $products[$id]->update(['stock_kg' => bcsub((string) $products[$id]->stock_kg, $line['quantity'], 3), 'inventory_value' => bcsub((string) $products[$id]->inventory_value, $line['hpp'], 2)]);
            }
            if (bccomp($paid, '0', 2) > 0) {
                $this->createPayment($sale, $paid, Decimal::today(), $key.':initial', true, '');
            }
            $this->refreshCustomer($sale->customer_id);

            return $sale;
        }, 3);
    }

    /** @param array<int, array{subtotal: string}> $lines @return array<int, string> */
    private function allocateDiscount(array $lines, string $subtotal, string $discount): array
    {
        $allocated = '0.00';
        $values = $remainders = [];
        foreach ($lines as $id => $line) {
            $exact = bccomp($subtotal, '0', 2) === 0 ? '0' : bcdiv(bcmul($line['subtotal'], $discount, 12), $subtotal, 12);
            $values[$id] = bcadd($exact, '0', 2);
            $remainders[$id] = bcsub($exact, $values[$id], 12);
            $allocated = bcadd($allocated, $values[$id], 2);
        }
        uksort($remainders, fn (int $a, int $b): int => bccomp($remainders[$b], $remainders[$a], 12) ?: $a <=> $b);
        $remaining = bcsub($discount, $allocated, 2);
        foreach ($remainders as $id => $remainder) {
            if (bccomp($remaining, '0', 2) <= 0) {
                break;
            }
            $values[$id] = bcadd($values[$id], '0.01', 2);
            $remaining = bcsub($remaining, '0.01', 2);
        }

        return $values;
    }

    public function pay(int $saleId, mixed $amount, string $date, string $notes, string $key, bool $settle = false): Sale
    {
        Gate::authorize('owner');
        $amount = Decimal::normalize($amount, 2, 'payment_amount');
        Validator::make(['payment_date' => $date, 'payment_notes' => $notes], ['payment_date' => 'required|date_format:Y-m-d', 'payment_notes' => 'nullable|string|max:2000'])->validate();
        $hash = hash('sha256', json_encode([$saleId, $settle ? 'settle' : $amount, $date, $notes]));

        return DB::transaction(function () use ($saleId, $amount, $date, $notes, $key, $settle, $hash): Sale {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            $sale = Sale::lockForUpdate()->findOrFail($saleId);
            if ($previous = Payment::where('idempotency_key', $key)->first()) {
                if ($previous->sale_id !== $sale->id || $previous->request_hash !== $hash) {
                    throw ValidationException::withMessages(['payment_amount' => 'Kunci pembayaran sudah digunakan untuk input lain.']);
                }

                return $sale;
            }
            $amount = $settle ? $sale->balance : $amount;
            if (bccomp($amount, '0', 2) <= 0 || bccomp($amount, $sale->balance, 2) > 0) {
                throw ValidationException::withMessages(['payment_amount' => 'Nominal harus positif dan maksimal sebesar sisa tagihan.']);
            }
            $this->createPayment($sale, $amount, $date, $key, false, $notes, $hash);
            $paid = bcadd((string) $sale->paid_amount, $amount, 2);
            $sale->update(['paid_amount' => $paid, 'payment_status' => bccomp($paid, (string) $sale->total, 2) === 0 ? 'Lunas' : 'Belum Lunas']);
            $this->refreshCustomer($sale->customer_id);

            return $sale;
        }, 3);
    }

    private function createPayment(Sale $sale, string $amount, string $date, string $key, bool $initial, string $notes, ?string $hash = null): void
    {
        $payment = Payment::create(['sale_id' => $sale->id, 'payment_date' => $date, 'amount' => $amount, 'is_initial' => $initial,
            'notes' => $notes, 'idempotency_key' => $key, 'request_hash' => $hash]);
        CashEntry::create(['entry_date' => $date, 'type' => 'Pemasukan', 'name' => $sale->invoice_number,
            'category' => $initial ? 'Penjualan Langsung' : 'Penerimaan Piutang', 'amount' => $amount,
            'description' => 'Pembayaran '.$sale->invoice_number, 'reference_type' => Payment::class,
            'reference_id' => $payment->id, 'idempotency_key' => 'payment:'.$payment->id]);
    }

    private function refreshCustomer(?int $customerId): void
    {
        if ($customerId) {
            $customer = Customer::lockForUpdate()->findOrFail($customerId);
            $customer->update(['receivable_balance' => Sale::where('customer_id', $customerId)->sum(DB::raw('total - paid_amount'))]);
        }
    }
}
