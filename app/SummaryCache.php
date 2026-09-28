<?php

namespace App;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class SummaryCache
{
    public static function changed(): void
    {
        DB::table('cache_versions')->where('domain', 'business')->increment('revision');
    }

    /**
     * @template T
     *
     * @param  array<string, mixed>  $filters
     * @param  Closure(): T  $read
     * @return T
     */
    public static function remember(string $name, array $filters, Closure $read, int $seconds = 60): mixed
    {
        if (DB::transactionLevel() > 0) {
            return $read();
        }

        ksort($filters);
        $revision = DB::table('cache_versions')->where('domain', 'business')->value('revision');
        $key = 'summary:'.auth()->id().':'.$name.':'.$revision.':'.hash('sha256', json_encode($filters, JSON_THROW_ON_ERROR));

        return Cache::remember($key, $seconds, $read);
    }
}
