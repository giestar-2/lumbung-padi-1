<?php

namespace Tests\Feature;

use App\Decimal;
use App\Livewire\Batches\Form as BatchForm;
use App\Livewire\Batches\Show as BatchShow;
use App\Livewire\Dashboard;
use App\Livewire\Products\Index;
use App\Livewire\Sales\Index as SalesIndex;
use App\Livewire\Sales\Pos;
use App\Livewire\Settings;
use App\Models\BatchCost;
use App\Models\CashEntry;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\User;
use App\ProductionWorkflow;
use App\SalesExport;
use App\SalesLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PrdWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    /** @return array<string,mixed> */
    private function saleData(Product $product, array $overrides = []): array
    {
        return array_replace(['cart' => [$product->id => ['qty' => '1.000']], 'customer_id' => null, 'discount' => '0', 'discount_type' => 'Rupiah',
            'paid_amount' => $product->selling_price, 'due_date' => '', 'notes' => ''], $overrides);
    }

    public function test_checkout_uses_server_prices_and_decimal_weight(): void
    {
        $this->owner();
        $product = Product::factory()->create(['selling_price' => '15000', 'stock_kg' => '10', 'cogs_per_kg' => '10000']);
        Livewire::test(Pos::class)->call('addToCart', $product->id)->set('cart.'.$product->id.'.price', 1)
            ->set('cart.'.$product->id.'.qty', '1,250')->set('paid_amount', '18750')->call('checkout')->assertHasNoErrors();
        $this->assertDatabaseHas('sales', ['total' => 18750, 'paid_amount' => 18750]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 8.750, 'inventory_value' => 87500]);
        $this->assertDatabaseHas('sale_items', ['quantity' => 1.250, 'price' => 15000, 'hpp' => 12500]);
    }

    public function test_checkout_rejects_insufficient_stock_without_side_effects(): void
    {
        $this->owner();
        $product = Product::factory()->create(['stock_kg' => '0.500']);
        Livewire::test(Pos::class)->call('addToCart', $product->id)->set('paid_amount', 15000)->call('checkout')->assertHasErrors('cart');
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('cash_entries', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 0.5]);
    }

    public function test_checkout_rejects_deactivated_products_and_overpayment(): void
    {
        $this->owner();
        $product = Product::factory()->create();
        $component = Livewire::test(Pos::class)->call('addToCart', $product->id)->set('paid_amount', 20000)->call('checkout')->assertHasErrors('paid_amount');
        $product->update(['is_active' => false]);
        $component->set('paid_amount', 15000)->call('checkout')->assertHasErrors('cart');
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_credit_requires_customer_and_full_discount_creates_no_cash(): void
    {
        $this->owner();
        $product = Product::factory()->create();
        Livewire::test(Pos::class)->call('addToCart', $product->id)->call('checkout')->assertHasErrors('customer_id')
            ->set('discount_type', 'Persen')->set('discount', '100')->call('checkout')->assertHasNoErrors();
        $this->assertDatabaseHas('sales', ['total' => 0, 'payment_status' => 'Lunas']);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('cash_entries', 0);
    }

    public function test_repeated_checkout_returns_same_sale_without_reducing_stock_again(): void
    {
        $this->owner();
        $product = Product::factory()->create();
        $ledger = new SalesLedger;
        $key = (string) Str::uuid();
        $data = $this->saleData($product);
        $first = $ledger->confirm($data, $key);
        $second = $ledger->confirm($data, $key);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('cash_entries', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 99]);
    }

    public function test_idempotency_key_rejects_changed_input(): void
    {
        $this->owner();
        $product = Product::factory()->create();
        $ledger = new SalesLedger;
        $key = (string) Str::uuid();
        $ledger->confirm($this->saleData($product), $key);
        $this->expectException(ValidationException::class);
        $ledger->confirm($this->saleData($product, ['notes' => 'Different request']), $key);
    }

    public function test_prd_partial_payment_and_settlement_preserve_sales_and_stock(): void
    {
        $this->owner();
        $product = Product::factory()->create(['selling_price' => '1000000']);
        $customer = Customer::factory()->create(['name' => 'Sari']);
        $ledger = new SalesLedger;
        $sale = $ledger->confirm($this->saleData($product, ['customer_id' => $customer->id, 'paid_amount' => '200000']), (string) Str::uuid());
        $this->assertSame('800000.00', $sale->balance);
        $key = (string) Str::uuid();
        $ledger->pay($sale->id, '800000', Decimal::today(), 'Pelunasan', $key);
        $ledger->pay($sale->id, '800000', Decimal::today(), 'Pelunasan', $key);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'total' => 1000000, 'paid_amount' => 1000000, 'payment_status' => 'Lunas']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 99]);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseCount('cash_entries', 2);
        $this->assertSame('1000000.00', number_format((float) CashEntry::sum('amount'), 2, '.', ''));
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'receivable_balance' => 0]);
    }

    public function test_payment_endpoint_rejects_amount_above_balance(): void
    {
        $this->owner();
        $product = Product::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Sari']);
        $sale = (new SalesLedger)->confirm($this->saleData($product, ['customer_id' => $customer->id, 'paid_amount' => '10000']), (string) Str::uuid());
        Livewire::test(SalesIndex::class)->call('openDetail', $sale->id)->set('payment_amount', '5001')->call('recordPayment')->assertHasErrors('payment_amount');
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_product_filter_counts_whole_invoice_and_allocates_discount(): void
    {
        $this->owner();
        $rice = Product::factory()->create(['selling_price' => 300000]);
        $bran = Product::factory()->create(['type' => 'Dedek', 'selling_price' => 200000]);
        $customer = Customer::factory()->create(['name' => 'Ratna']);
        $sale = (new SalesLedger)->confirm($this->saleData($rice, ['cart' => [$rice->id => ['qty' => 1], $bran->id => ['qty' => 1]],
            'customer_id' => $customer->id, 'discount' => '50000', 'paid_amount' => '100000']), (string) Str::uuid());
        Livewire::test(SalesIndex::class)->set('product', (string) $rice->id)->assertViewHas('jumlahTransaksi', 1)
            ->assertViewHas('totalPenjualan', 450000)->assertViewHas('totalDibayar', 100000)->assertViewHas('sisaPiutang', '350000.00');
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'product_id' => $rice->id, 'discount_amount' => 30000, 'net_amount' => 270000]);
    }

    public function test_discount_rounding_preserves_total_to_the_cent(): void
    {
        $this->owner();
        $products = Product::factory()->count(3)->create(['selling_price' => '0.05', 'cogs_per_kg' => 0]);
        $cart = [];
        foreach ($products as $product) {
            $cart[$product->id] = ['qty' => '1'];
        }
        $sale = (new SalesLedger)->confirm($this->saleData($products->first(), ['cart' => $cart, 'discount' => '0.01', 'paid_amount' => '0.14']), (string) Str::uuid());
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'product_id' => $products->first()->id, 'discount_amount' => 0.01]);
        $this->assertSame('0.01', number_format((float) $sale->items()->sum('discount_amount'), 2, '.', ''));
    }

    public function test_product_summary_uses_same_search_and_low_stock_filter(): void
    {
        $this->owner();
        Product::factory()->create(['name' => 'Beras A', 'stock_kg' => 5, 'stock_minimum' => 10, 'selling_price' => 15000, 'cogs_per_kg' => 10000]);
        Product::factory()->create(['name' => 'Beras B', 'stock_kg' => 100]);
        Livewire::test(Index::class)->set('search', 'Beras A')->set('lowStock', true)
            ->assertViewHas('jumlahProduk', 1)->assertViewHas('totalHargaProduk', 75000)->assertViewHas('totalHargaModal', 50000)->assertViewHas('produkStokMenipis', 1);
    }

    public function test_stock_adjustment_rejects_stale_preview(): void
    {
        $this->owner();
        $product = Product::factory()->create();
        $component = Livewire::test(Index::class)->call('adjust', $product->id)->set('actualStock', '20,500');
        $product->update(['stock_kg' => 99]);
        $component->call('saveAdjustment')->assertHasErrors('actualStock');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 99]);
    }

    public function test_related_products_customers_employees_cannot_be_deleted(): void
    {
        $this->owner();
        $product = Product::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Sari']);
        (new SalesLedger)->confirm($this->saleData($product, ['customer_id' => $customer->id]), (string) Str::uuid());
        Livewire::test(Index::class)->call('delete', $product->id)->assertHasErrors('delete');
        Livewire::test(\App\Livewire\Customers\Index::class)->call('delete', $customer->id)->assertHasErrors('delete');
        $this->assertModelExists($product);
        $this->assertModelExists($customer);
    }

    public function test_rice_receipt_does_not_reduce_saleable_stock(): void
    {
        $this->owner();
        $product = Product::factory()->create(['type' => 'Bahan Baku', 'stock_kg' => 100]);
        Livewire::test(BatchForm::class)->set('raw_material_id', $product->id)->set('raw_material_weight', '12,500')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 100]);
        $this->assertDatabaseHas('production_batches', ['raw_material_weight' => 12.5, 'current_stage' => 'Belum Diproses']);
    }

    public function test_rice_stages_validate_mass_balance_and_post_stock_once(): void
    {
        $this->owner();
        $batch = ProductionBatch::factory()->create();
        $product = Product::factory()->create(['stock_kg' => 10, 'cogs_per_kg' => 10000]);
        $component = Livewire::test(BatchShow::class, ['id' => $batch->id])->call('addStage')
            ->set('stage_weight_after', '90')->call('addStage')->set('stage_weight_after', '80')->set('dedek_weight', '20')
            ->call('addStage')->assertHasErrors('stage_weight_after');
        $component->set('stage_weight_after', '60')->set('dedek_weight', '20')->set('pupuk_weight', '5')->call('addStage')->assertHasNoErrors()
            ->set('output_product_id', $product->id)->call('addOutput')->call('addOutput')->assertHasNoErrors();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 70, 'inventory_value' => 100000]);
        $this->assertDatabaseCount('stock_receipts', 1);
        $this->assertDatabaseCount('production_outputs', 1);
    }

    public function test_cost_allocation_and_residue_inheritance_do_not_duplicate_cash(): void
    {
        $this->owner();
        $batch = ProductionBatch::factory()->create(['status' => 'Selesai', 'current_stage' => 'Selesai', 'result_weight' => 60, 'dedek_weight' => 20, 'pupuk_weight' => 10, 'raw_material_cogs' => 100000]);
        $workflow = new ProductionWorkflow;
        $workflow->finalizeCosts($batch->id, ['Beras' => '70000', 'Dedek' => '20000', 'Pupuk' => '10000']);
        Livewire::test(BatchForm::class, ['type' => 'Dedek'])->set('source_batch_id', $batch->id)->set('raw_material_weight', '5')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('production_batches', ['source_batch_id' => $batch->id, 'type' => 'Dedek', 'inherited_cost' => 5000, 'raw_material_weight' => 5]);
        $this->assertDatabaseCount('cash_entries', 0);
        Livewire::test(BatchForm::class, ['type' => 'Dedek'])->set('source_batch_id', $batch->id)->set('raw_material_weight', '16')->call('save')->assertHasErrors('raw_material_weight');
        $this->assertDatabaseCount('production_batches', 2);
    }

    public function test_finalization_rejects_missing_or_excess_cost_allocation(): void
    {
        $this->owner();
        $batch = ProductionBatch::factory()->create(['status' => 'Selesai', 'current_stage' => 'Selesai', 'result_weight' => 60, 'raw_material_cogs' => 100000]);
        Livewire::test(BatchShow::class, ['id' => $batch->id])->set('allocation.Beras', '99999')->call('finalizeCosts')->assertHasErrors('allocation');
        $this->assertNull($batch->fresh()->cost_finalized_at);
    }

    public function test_fertilizer_output_accepts_only_recorded_mixture_mass(): void
    {
        $this->owner();
        $batch = ProductionBatch::factory()->create(['type' => 'Pupuk', 'raw_material_weight' => 10, 'current_stage' => 'Pencampuran dengan Bahan Lain']);
        Livewire::test(BatchShow::class, ['id' => $batch->id])->set('mixtures', [['name' => 'Kompos', 'weight' => '5', 'cost' => '0', 'paid' => false]])
            ->set('stage_weight_after', '16')->call('addStage')->assertHasErrors('stage_weight_after')
            ->set('stage_weight_after', '14')->call('addStage')->assertHasNoErrors();
        $this->assertDatabaseHas('production_batches', ['id' => $batch->id, 'result_weight' => 14, 'status' => 'Selesai']);
    }

    public function test_payment_of_batch_cost_is_idempotent(): void
    {
        $this->owner();
        $batch = ProductionBatch::factory()->create();
        $cost = BatchCost::factory()->create(['production_batch_id' => $batch->id]);
        Livewire::test(BatchShow::class, ['id' => $batch->id])->call('payCost', $cost->id)->call('payCost', $cost->id)->assertHasNoErrors();
        $this->assertDatabaseCount('cash_entries', 1);
        $this->assertDatabaseHas('cash_entries', ['amount' => 75000, 'classification' => 'Produksi', 'reference_id' => $cost->id]);
    }

    public function test_payroll_saves_payment_and_updates_same_cash_entry_on_edit(): void
    {
        $this->owner();
        $employee = Employee::factory()->create(['name' => 'Budi', 'is_active' => true]);
        $component = Livewire::test(\App\Livewire\Payrolls\Index::class)->set('employee_id', $employee->id)->set('total_amount', '200000')->call('save')->assertHasNoErrors();
        $payroll = Payroll::firstOrFail();
        $this->assertSame('Sudah Dibayar', $payroll->payment_status);
        $component->call('edit', $payroll->id)->set('total_amount', '250000')->call('save')->assertHasNoErrors();
        $this->assertDatabaseCount('cash_entries', 1);
        $this->assertDatabaseHas('cash_entries', ['reference_type' => Payroll::class, 'reference_id' => $payroll->id, 'amount' => 250000]);
    }

    public function test_automatic_cash_cannot_be_edited_or_deleted(): void
    {
        $this->owner();
        $product = Product::factory()->create();
        (new SalesLedger)->confirm($this->saleData($product), (string) Str::uuid());
        $cash = CashEntry::firstOrFail();
        Livewire::test(\App\Livewire\Cash\Index::class)->call('edit', $cash->id)->assertForbidden();
        Livewire::test(\App\Livewire\Cash\Index::class)->call('delete', $cash->id)->assertForbidden();
        $this->assertModelExists($cash);
    }

    public function test_dashboard_uses_hpp_and_payroll_period_instead_of_cash_as_profit(): void
    {
        $this->owner();
        $this->travelTo(now()->setDate(2026, 9, 15));
        $product = Product::factory()->create(['selling_price' => 100000, 'cogs_per_kg' => 60000]);
        (new SalesLedger)->confirm($this->saleData($product), (string) Str::uuid());
        $employee = Employee::factory()->create(['name' => 'Budi']);
        Payroll::factory()->create(['employee_id' => $employee->id, 'payroll_date' => '2026-10-02', 'period_start' => '2026-09-01', 'period_end' => '2026-09-30', 'total_amount' => 10000, 'payment_status' => 'Sudah Dibayar']);
        CashEntry::factory()->create(['entry_date' => '2026-09-10', 'type' => 'Pengeluaran', 'category' => 'Pembelian Bahan', 'amount' => 90000, 'classification' => 'Produksi']);
        Livewire::test(Dashboard::class)->set('from', '2026-09-01')->set('to', '2026-09-30')->assertViewHas('labaUsaha', '30000.00')->assertViewHas('arusKasBersih', '10000.00');
    }

    public function test_excel_has_four_sheets_numeric_values_and_safe_text(): void
    {
        $this->owner();
        $rice = Product::factory()->create(['name' => '=HYPERLINK("bad")', 'selling_price' => 300000]);
        $bran = Product::factory()->create(['selling_price' => 200000]);
        $customer = Customer::factory()->create(['name' => '=1+1']);
        $sale = (new SalesLedger)->confirm($this->saleData($rice, ['cart' => [$rice->id => ['qty' => 1], $bran->id => ['qty' => 1]],
            'customer_id' => $customer->id, 'discount' => '50000', 'paid_amount' => '100000']), (string) Str::uuid());
        $path = (new SalesExport)->write(['product' => (string) $rice->id]);
        try {
            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($path));
            $workbook = $zip->getFromName('xl/workbook.xml');
            foreach (['Ringkasan', 'Transaksi', 'Item Produk', 'Pembayaran'] as $sheet) {
                $this->assertStringContainsString($sheet, $workbook);
            }
            $items = $zip->getFromName('xl/worksheets/sheet3.xml');
            $this->assertStringContainsString('270000', $items);
            $this->assertStringContainsString('HYPERLINK', $items);
            $this->assertStringNotContainsString('<f>', $items);
            $this->assertStringContainsString('450000', $zip->getFromName('xl/worksheets/sheet2.xml'));
            $zip->close();
        } finally {
            unlink($path);
        }
    }

    public function test_empty_export_shows_error_and_receipt_is_protected(): void
    {
        $this->get('/sales/1/receipt')->assertRedirect('/login');
        $this->owner();
        Livewire::test(SalesIndex::class)->call('export')->assertHasErrors('export');
    }

    public function test_password_change_revokes_sessions_and_requires_login(): void
    {
        $user = $this->owner();
        $user->update(['password' => Hash::make('old-password-123')]);
        DB::table('sessions')->insert(['id' => 'another-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        Livewire::test(Settings::class)->set('current_password', 'old-password-123')->set('new_password', 'new-password-123')
            ->set('new_password_confirmation', 'new-password-123')->call('updatePassword')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseMissing('sessions', ['id' => 'another-session']);
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_second_user_cannot_access_business_pages(): void
    {
        User::factory()->create();
        $this->actingAs(User::factory()->create())->get('/products')->assertForbidden();
    }

    public function test_store_name_is_used_in_navigation_and_receipt(): void
    {
        $user = $this->owner();
        $product = Product::factory()->create();
        $sale = (new SalesLedger)->confirm($this->saleData($product), (string) Str::uuid());
        Livewire::test(Settings::class)->set('store_name', 'Lumbung Sumber Rezeki')->call('updateStore')->assertHasNoErrors();
        $this->get('/')->assertSee('Lumbung Sumber Rezeki');
        $this->get(route('sales.receipt', $sale))->assertSee('Lumbung Sumber Rezeki')->assertSee($sale->invoice_number);
    }

    public function test_sales_list_query_count_stays_constant_as_rows_increase(): void
    {
        $this->owner();
        $product = Product::factory()->create(['stock_kg' => '100']);
        $customer = Customer::factory()->create(['name' => 'Toko Makmur']);
        $ledger = new SalesLedger;
        for ($i = 0; $i < 5; $i++) {
            $ledger->confirm($this->saleData($product, ['customer_id' => $customer->id]), (string) Str::uuid());
        }
        $countQueries = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            try {
                Livewire::test(SalesIndex::class)->assertStatus(200);

                return count(DB::getQueryLog());
            } finally {
                DB::disableQueryLog();
                DB::flushQueryLog();
            }
        };
        $fiveRows = $countQueries();
        for ($i = 0; $i < 20; $i++) {
            $ledger->confirm($this->saleData($product, ['customer_id' => $customer->id]), (string) Str::uuid());
        }
        $this->assertSame($fiveRows, $countQueries());
    }

    public function test_invalid_discount_input_does_not_crash_pos_preview(): void
    {
        $this->owner();
        Livewire::test(Pos::class)->set('discount', '1e10')->assertStatus(200)
            ->call('checkout')->assertHasErrors();
    }

    public function test_partial_payment_input_accepts_intermediate_typing_values(): void
    {
        $this->owner();
        Livewire::test(Pos::class)
            ->set('paid_amount', '')
            ->set('paid_amount', '1.')
            ->set('paid_amount', '1e3')
            ->assertHasNoErrors();
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_pos_quick_payment_amount_updates_payment_status(): void
    {
        $this->owner();
        $product = Product::factory()->create(['selling_price' => '15000']);

        Livewire::test(Pos::class)
            ->call('addToCart', $product->id)
            ->call('setPaymentAmount', '10000')
            ->assertSet('paid_amount', '10000.00')
            ->assertSet('payment_status', 'Belum Lunas')
            ->call('setPaymentAmount', '15000')
            ->assertSet('payment_status', 'Lunas');
    }

    public function test_many_small_residue_allocations_preserve_the_exact_cost_pool(): void
    {
        $this->owner();
        $source = ProductionBatch::factory()->create(['status' => 'Selesai', 'current_stage' => 'Selesai',
            'dedek_weight' => '0.006', 'raw_material_cogs' => '0.03', 'cost_finalized_at' => now(), 'cost_allocation' => ['Dedek' => '0.03']]);
        for ($i = 0; $i < 6; $i++) {
            Livewire::test(BatchForm::class, ['type' => 'Dedek'])->set('source_batch_id', $source->id)
                ->set('raw_material_weight', '0.001')->call('save')->assertHasNoErrors();
        }
        $this->assertEquals('0.03', $source->children()->sum('inherited_cost'));
        $this->assertFalse($source->children()->where('inherited_cost', '<', 0)->exists());
        $this->assertSame('0.000', ProductionWorkflow::available($source, 'Dedek'));
        $this->assertDatabaseCount('cash_entries', 0);
    }

    public function test_legacy_batch_retains_its_material_cost_when_processing_cost_is_added(): void
    {
        $this->owner();
        $batch = ProductionBatch::factory()->create(['raw_material_cogs' => '100000']);
        $workflow = new ProductionWorkflow;
        $workflow->addCost($batch, 'Pengeringan', 'Biaya Pengolahan', '25000', Decimal::today(), null);
        $this->assertSame('125000.00', $workflow->totalCost($batch));
        $this->assertDatabaseCount('cash_entries', 0);
    }

    public function test_populated_business_pages_render_with_related_records(): void
    {
        $user = $this->owner();
        $user->update(['name' => 'Aji Subekti', 'store_name' => 'Lumbung Beras']);
        $this->travelTo(now('Asia/Jakarta')->setDate(2026, 9, 27)->setTime(10, 0));
        $customer = Customer::factory()->create(['name' => 'Toko Sumber Makmur']);
        $rice = Product::factory()->create(['name' => 'Beras premium', 'stock_kg' => '1500', 'selling_price' => '14500', 'cogs_per_kg' => '9500']);
        $bran = Product::factory()->create(['name' => 'Dedek halus', 'type' => 'Dedek', 'stock_kg' => '850', 'selling_price' => '4500', 'cogs_per_kg' => '2500']);
        Product::factory()->create(['name' => 'Beras rojolele', 'stock_kg' => '18.500', 'stock_minimum' => '50']);
        Product::factory()->create(['name' => 'Pupuk organik', 'type' => 'Pupuk', 'stock_kg' => '25', 'stock_minimum' => '40', 'selling_price' => '3500']);
        for ($i = 0; $i < 7; $i++) {
            $this->travelTo(now('Asia/Jakarta')->setDate(2026, 9, 3 + $i * 3));
            $qty = (string) (40 + $i * 7);
            $sale = (new SalesLedger)->confirm($this->saleData($rice, ['cart' => [$rice->id => ['qty' => $qty], $bran->id => ['qty' => '20']],
                'customer_id' => $customer->id, 'paid_amount' => (string) (350000 + $i * 70000), 'due_date' => '2026-09-25']), (string) Str::uuid());
            CashEntry::factory()->create(['name' => 'Perawatan mesin', 'entry_date' => Decimal::today(), 'type' => 'Pengeluaran', 'category' => 'Operasional Lainnya', 'amount' => 110000 + $i * 8000]);
        }
        $this->travelTo(now('Asia/Jakarta')->setDate(2026, 9, 27));
        $batch = ProductionBatch::factory()->create(['name' => 'Panen September', 'origin' => 'Pak Haryanto', 'raw_material_weight' => 1200, 'current_stage' => 'Pengeringan']);
        $employee = Employee::factory()->create(['name' => 'Budi Santoso', 'role' => 'Operator produksi']);
        Payroll::factory()->create(['employee_id' => $employee->id, 'payroll_date' => Decimal::today(), 'period_start' => '2026-09-01', 'period_end' => '2026-09-27', 'total_amount' => 1800000, 'payment_status' => 'Sudah Dibayar', 'salary_type' => 'Bulanan']);
        Livewire::test(Dashboard::class)->assertViewHas('topProducts', fn ($products) => $products->first()->id === $rice->id);
        $pages = ['dashboard' => '/', 'products' => '/products', 'product-form' => '/products/form/'.$rice->id, 'sales' => '/sales?detail='.$sale->id, 'pos' => '/sales/pos',
            'cash' => '/cash', 'payrolls' => '/payrolls', 'customers' => '/customers', 'employees' => '/employees', 'batch' => '/batches/'.$batch->id, 'batches' => '/batches', 'batch-form' => '/batches/form', 'settings' => '/settings'];
        foreach ($pages as $name => $url) {
            Livewire::flushState();
            $response = $this->get($url)->assertOk();
            if (getenv('CODEX_VISUAL_REVIEW') === '1') {
                file_put_contents(storage_path('framework/testing/review-'.$name.'.html'), $response->getContent());
            }
        }
    }
}
