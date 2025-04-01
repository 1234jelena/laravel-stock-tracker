<?php

namespace App\Traits;

use App\Models\Stock;
use Illuminate\Support\Facades\Cache;

trait HasCachedLatestPrice
{
    public function getOrCacheLatestPrice(Stock $stock)
    {
        $cacheKey = "stock:{$stock->id}:latest_price";

        return Cache::remember($cacheKey, 60, function () use ($stock) {
            return $stock->latestPrice()->firstOrFail();
        });
    }
}
