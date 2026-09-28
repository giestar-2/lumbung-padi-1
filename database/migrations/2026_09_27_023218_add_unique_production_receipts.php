<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('production_batch_id')->unique()->constrained();
            $table->foreignId('production_output_id')->unique()->constrained();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 18, 3);
            $table->decimal('cost', 20, 2);
            $table->string('idempotency_key')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_receipts');
    }
};
