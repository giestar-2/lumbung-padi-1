<?php

namespace Tests\Feature;

use App\Livewire\Products\Index;
use App\Models\Product;
use App\Models\User;
use App\SummaryCache;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SummaryCacheTest extends TestCase
{
    use DatabaseMigrations;

    public function test_summary_is_reused_and_refreshes_after_stock_purchase(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['stock_kg' => '10', 'selling_price' => '15000']);
        $reads = 0;
        $read = function () use (&$reads): int {
            $reads++;

            return Product::count();
        };
        $this->assertSame(1, SummaryCache::remember('count', [], $read));
        $this->assertSame(1, SummaryCache::remember('count', [], $read));
        $this->assertSame(1, $reads);
        Livewire::test(Index::class)->assertViewHas('totalHargaProduk', 150000)
            ->call('adjust', $product->id)->set('incomingStock', '5')->call('saveAdjustment')
            ->assertHasNoErrors()->assertViewHas('totalHargaProduk', 225000);
        SummaryCache::remember('count', [], $read);
        $this->assertSame(2, $reads);
    }

    public function test_rollback_cannot_publish_uncommitted_summary_or_revision(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['stock_kg' => '10']);
        $read = fn (): string => (string) Product::sum('stock_kg');
        $before = SummaryCache::remember('stock', [], $read);
        $revision = DB::table('cache_versions')->value('revision');
        DB::beginTransaction();
        try {
            $product->update(['stock_kg' => '99']);
            $this->assertEquals(99, SummaryCache::remember('stock', [], $read));
        } finally {
            DB::rollBack();
        }
        $this->assertSame($revision, DB::table('cache_versions')->value('revision'));
        $this->assertSame($before, SummaryCache::remember('stock', [], $read));
        DB::transaction(fn () => $product->fresh()->update(['stock_kg' => '7']));
        $this->assertEquals(7, SummaryCache::remember('stock', [], $read));
    }
}
