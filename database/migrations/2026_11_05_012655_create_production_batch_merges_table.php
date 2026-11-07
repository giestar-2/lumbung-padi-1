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
        Schema::create('production_batch_merges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('target_batch_id')->constrained('production_batches');
            $table->foreignId('source_batch_id')->unique()->constrained('production_batches');
            $table->decimal('weight_kg', 18, 3);
            $table->decimal('transferred_cost', 20, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_batch_merges');
    }
};
