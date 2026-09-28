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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->date('payroll_date');
            $table->foreignId('employee_id')->constrained();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_amount', 20, 2);
            $table->enum('payment_status', ['Belum Dibayar', 'Sudah Dibayar'])->default('Belum Dibayar');
            $table->string('idempotency_key')->unique()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
