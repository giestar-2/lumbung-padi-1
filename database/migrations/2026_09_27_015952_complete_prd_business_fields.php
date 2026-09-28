<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('store_name', 150)->default('Lumbung Beras');
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('stock_minimum', 18, 3)->default(0);
            $table->string('origin')->nullable();
            $table->text('notes')->nullable();
        });
        Schema::table('customers', fn (Blueprint $table) => $table->text('notes')->nullable());
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('salary_type')->default('Harian');
            $table->decimal('default_rate', 20, 2)->default(0);
            $table->text('notes')->nullable();
        });
        Schema::table('sales', function (Blueprint $table): void {
            $table->date('due_date')->nullable();
            $table->string('request_hash', 64)->nullable();
            $table->string('discount_type')->default('Rupiah');
            $table->index(['sale_date', 'id']);
        });
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->string('product_name')->nullable();
            $table->string('product_category')->nullable();
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('net_amount', 20, 2)->nullable();
            $table->decimal('hpp', 20, 2)->nullable();
            $table->index(['product_id', 'sale_id']);
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->boolean('is_initial')->default(false);
            $table->text('notes')->nullable();
            $table->string('request_hash', 64)->nullable();
        });
        Schema::table('cash_entries', function (Blueprint $table): void {
            $table->string('name')->nullable();
            $table->string('classification')->default('Operasional');
            $table->foreignId('production_batch_id')->nullable()->constrained();
            $table->index(['type', 'entry_date', 'id']);
        });
        Schema::table('payrolls', function (Blueprint $table): void {
            $table->string('salary_type')->nullable();
            $table->decimal('daily_rate', 20, 2)->default(0);
            $table->unsignedSmallInteger('work_days')->default(1);
            $table->string('classification')->default('Operasional');
            $table->foreignId('production_batch_id')->nullable()->constrained();
            $table->text('notes')->nullable();
        });
        Schema::table('production_batches', function (Blueprint $table): void {
            $table->foreignId('raw_material_id')->nullable()->change();
            $table->string('type')->default('Beras');
            $table->string('name')->nullable();
            $table->string('origin')->nullable();
            $table->string('current_stage')->default('Belum Diproses');
            $table->foreignId('source_batch_id')->nullable()->constrained('production_batches');
            $table->decimal('dry_weight', 18, 3)->nullable();
            $table->decimal('result_weight', 18, 3)->nullable();
            $table->decimal('dedek_weight', 18, 3)->default(0);
            $table->decimal('pupuk_weight', 18, 3)->default(0);
            $table->json('mixtures')->nullable();
            $table->json('cost_allocation')->nullable();
            $table->timestamp('cost_finalized_at')->nullable();
            $table->index(['type', 'start_date', 'id']);
        });
        Schema::create('batch_costs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('production_batch_id')->constrained();
            $table->string('name');
            $table->string('category');
            $table->decimal('amount', 20, 2);
            $table->date('cost_date');
            $table->date('paid_at')->nullable();
            $table->foreignId('cash_entry_id')->nullable()->unique()->constrained();
            $table->timestamps();
        });
        DB::table('payrolls')->orderBy('id')->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                $days = Carbon::parse($row->period_start)->diffInDays($row->period_end);
                DB::table('payrolls')->where('id', $row->id)->update(['salary_type' => $days <= 7 ? 'Harian' : 'Bulanan']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_costs');
        Schema::table('cash_entries', function (Blueprint $table): void {
            $table->dropForeign(['production_batch_id']);
            $table->dropIndex(['type', 'entry_date', 'id']);
            $table->dropColumn(['name', 'classification', 'production_batch_id']);
        });
        Schema::table('payrolls', function (Blueprint $table): void {
            $table->dropForeign(['production_batch_id']);
            $table->dropColumn(['salary_type', 'daily_rate', 'work_days', 'classification', 'production_batch_id', 'notes']);
        });
        Schema::table('production_batches', function (Blueprint $table): void {
            $table->dropForeign(['source_batch_id']);
            $table->dropIndex(['type', 'start_date', 'id']);
            $table->dropColumn(['type', 'name', 'origin', 'current_stage', 'source_batch_id', 'dry_weight', 'result_weight', 'dedek_weight', 'pupuk_weight', 'mixtures', 'cost_allocation', 'cost_finalized_at']);
        });
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn(['is_initial', 'notes', 'request_hash']));
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->dropIndex(['product_id', 'sale_id']);
            $table->dropColumn(['product_name', 'product_category', 'discount_amount', 'net_amount', 'hpp']);
        });
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropIndex(['sale_date', 'id']);
            $table->dropColumn(['due_date', 'request_hash', 'discount_type']);
        });
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn(['salary_type', 'default_rate', 'notes']));
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('notes'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['stock_minimum', 'origin', 'notes']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('store_name'));
    }
};
