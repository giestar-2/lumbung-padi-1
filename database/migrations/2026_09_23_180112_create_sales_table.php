<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->date('sale_date');
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->decimal('subtotal', 20, 2);
            $table->decimal('discount', 20, 2)->default(0);
            $table->decimal('total', 20, 2);
            $table->decimal('paid_amount', 20, 2)->default(0);
            $table->enum('payment_status', ['Lunas', 'Belum Lunas'])->default('Belum Lunas');
            $table->string('idempotency_key')->unique()->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
