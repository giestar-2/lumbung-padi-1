<?php

namespace App;

use App\Models\Customer;
use App\Models\Product;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class SalesExport
{
    /** @param array<string,mixed> $filters */
    public function download(array $filters): BinaryFileResponse
    {
        Gate::authorize('owner');
        $path = $this->write($filters);

        return response()->download($path, 'penjualan-'.now('Asia/Jakarta')->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    /** @param array<string,mixed> $filters */
    public function write(array $filters): string
    {
        Gate::authorize('owner');
        $path = tempnam(storage_path('app'), 'sales-');
        $writer = new Writer;
        try {
            $writer->openToFile($path);
            DB::transaction(function () use ($filters, $writer): void {
                $query = (new SalesHistoryQuery)->build($filters);
                if (! (clone $query)->exists()) {
                    throw ValidationException::withMessages(['export' => 'Tidak ada transaksi yang sesuai filter untuk diekspor.']);
                }
                $snapshot = now('Asia/Jakarta');
                $summary = $writer->getCurrentSheet();
                $summary->setName('Ringkasan');
                $transactions = $writer->addNewSheetAndMakeItCurrent();
                $transactions->setName('Transaksi');
                $transactions->setSheetView(new SheetView(freezeRow: 2));
                $this->row($writer, ['Nomor', 'Tanggal penjualan', 'Pelanggan', 'Total setelah diskon', 'Sudah dibayar', 'Sisa piutang', 'Status', 'Jatuh tempo'], true);
                $items = $writer->addNewSheetAndMakeItCurrent();
                $items->setName('Item Produk');
                $items->setSheetView(new SheetView(freezeRow: 2));
                $this->row($writer, ['Nomor', 'Produk', 'Kategori', 'Kg', 'Harga/kg', 'Subtotal', 'Diskon item', 'Nilai item yang cocok', 'HPP', 'Laba kotor'], true);
                $payments = $writer->addNewSheetAndMakeItCurrent();
                $payments->setName('Pembayaran');
                $payments->setSheetView(new SheetView(freezeRow: 2));
                $this->row($writer, ['Nomor', 'Tanggal pembayaran', 'Jenis', 'Nominal', 'Keterangan'], true);
                $total = $paid = $balance = '0.00';
                $count = 0;
                foreach ($query->with(['customer', 'items' => fn ($q) => $q->orderBy('id'), 'payments' => fn ($q) => $q->orderBy('payment_date')->orderBy('id')])
                    ->orderByDesc('sale_date')->orderByDesc('id')->lazy(250) as $sale) {
                    $count++;
                    $total = bcadd($total, (string) $sale->total, 2);
                    $paid = bcadd($paid, (string) $sale->paid_amount, 2);
                    $balance = bcadd($balance, $sale->balance, 2);
                    $writer->setCurrentSheet($transactions);
                    $this->row($writer, [$sale->invoice_number, new DateTimeImmutable($sale->sale_date), $sale->customer?->name ?? 'Umum',
                        (float) $sale->total, (float) $sale->paid_amount, (float) $sale->balance, $sale->status_label, $sale->due_date ? new DateTimeImmutable($sale->due_date) : '']);
                    $writer->setCurrentSheet($items);
                    foreach ($sale->items as $item) {
                        if (($filters['product'] ?? '') !== '' && (int) $filters['product'] !== $item->product_id) {
                            continue;
                        }
                        $net = (string) ($item->net_amount ?? bcsub((string) $item->subtotal, (string) $item->discount_amount, 2));
                        $hpp = (string) ($item->hpp ?? Decimal::money(bcmul((string) $item->quantity, (string) $item->cogs, 8)));
                        $this->row($writer, [$sale->invoice_number, $item->product_name ?? 'Produk #'.$item->product_id, $item->product_category ?? '',
                            (float) $item->quantity, (float) $item->price, (float) $item->subtotal, (float) $item->discount_amount, (float) $net, (float) $hpp, (float) bcsub($net, $hpp, 2)]);
                    }
                    $writer->setCurrentSheet($payments);
                    foreach ($sale->payments as $payment) {
                        $this->row($writer, [$sale->invoice_number, new DateTimeImmutable($payment->payment_date), $payment->is_initial ? 'Awal / POS' : 'Lanjutan',
                            (float) $payment->amount, $payment->notes ?? '']);
                    }
                }
                $writer->setCurrentSheet($summary);
                $this->row($writer, ['Ringkasan penjualan', auth()->user()->store_name], true);
                $this->row($writer, ['Waktu snapshot WIB', $snapshot->format('Y-m-d H:i:s')]);
                $labels = ['search' => 'Nomor nota', 'customer' => 'Pelanggan', 'product' => 'Produk', 'from' => 'Penjualan dari', 'to' => 'Penjualan sampai', 'status' => 'Status'];
                foreach ($labels as $key => $label) {
                    $value = (string) ($filters[$key] ?? '');
                    if ($key === 'customer' && $value !== '') {
                        $value = Customer::find($value)?->name ?? $value;
                    }
                    if ($key === 'product' && $value !== '') {
                        $value = Product::find($value)?->name ?? $value;
                    }
                    $this->row($writer, [$label, $value ?: 'Semua']);
                }
                $this->row($writer, ['Total Penjualan Setelah Diskon', (float) $total]);
                $this->row($writer, ['Jumlah Transaksi', $count]);
                $this->row($writer, ['Total Sudah Dibayar', (float) $paid]);
                $this->row($writer, ['Sisa Piutang', (float) $balance]);
                $this->row($writer, ['Cakupan', 'Filter produk memilih keseluruhan nota. Total nota dihitung sekali. Sheet Item Produk hanya memuat item yang cocok.']);
                $this->row($writer, ['Pembayaran', 'Seluruh pembayaran nota terpilih, termasuk yang dibayar di luar rentang tanggal penjualan.']);
                $writer->setCurrentSheet($transactions);
                $this->row($writer, ['TOTAL', '', '', (float) $total, (float) $paid, (float) $balance], true);
            });
            $writer->close();

            return $path;
        } catch (\Throwable $exception) {
            $writer->close();
            if (is_file($path)) {
                unlink($path);
            }
            throw $exception;
        }
    }

    /** @param list<string|int|float|DateTimeImmutable> $values */
    private function row(Writer $writer, array $values, bool $heading = false): void
    {
        $cells = [];
        foreach ($values as $value) {
            $style = $heading ? new Style(fontBold: true, backgroundColor: 'EAF1E3') :
                ($value instanceof DateTimeImmutable ? new Style(format: 'dd/mm/yyyy') : (is_float($value) ? new Style(format: '#,##0.00#') : null));
            $cells[] = is_string($value) ? new StringCell($value, $style) : Cell::fromValue($value, $style);
        }
        $writer->addRow(new Row($cells));
    }
}
