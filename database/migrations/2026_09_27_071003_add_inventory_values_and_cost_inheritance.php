<?php

use App\Decimal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->decimal('inventory_value', 20, 2)->nullable());
        Schema::table('production_batches', fn (Blueprint $table) => $table->decimal('inherited_cost', 20, 2)->default(0));
        DB::table('products')->orderBy('id')->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('products')->where('id', $row->id)->update(['inventory_value' => Decimal::money(bcmul((string) $row->stock_kg, (string) $row->cogs_per_kg, 8))]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_batches', fn (Blueprint $table) => $table->dropColumn('inherited_cost'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('inventory_value'));
    }
};
