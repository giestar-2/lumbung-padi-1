<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_costs', function (Blueprint $table): void {
            $table->boolean('is_initial_purchase')->default(false);
        });
        DB::table('batch_costs')->where('name', 'Pembelian gabah')->where('category', 'Pembelian Bahan')
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('production_batches')
                ->whereColumn('production_batches.id', 'batch_costs.production_batch_id')->where('production_batches.type', 'Beras')
                ->whereColumn('production_batches.raw_material_cogs', 'batch_costs.amount'))
            ->update(['is_initial_purchase' => true]);
    }

    public function down(): void
    {
        Schema::table('batch_costs', function (Blueprint $table): void {
            $table->dropColumn('is_initial_purchase');
        });
    }
};
