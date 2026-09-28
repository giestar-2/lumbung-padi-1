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
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['Proses', 'Selesai', 'Batal'])->default('Proses');
            $table->foreignId('raw_material_id')->constrained('products');
            $table->decimal('raw_material_weight', 18, 3);
            $table->decimal('raw_material_cogs', 20, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
