<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cache_versions', function (Blueprint $table) {
            $table->string('domain')->primary();
            $table->unsignedBigInteger('revision')->default(0);
        });
        DB::table('cache_versions')->insert(['domain' => 'business', 'revision' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache_versions');
    }
};
