<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockPriceChangeRequest;
use App\Http\Requests\StockPriceRequest;
use App\Http\Resources\PriceChangeResource;
use App\Http\Resources\StockPriceResource;
use App\Models\Stock;
use App\Traits\HasCachedLatestPrice;

class StockPriceController extends Controller
{
    use HasCachedLatestPrice;

    public function getLatestPrice(Stock $stock): StockPriceResource
    {
        $latestStockPrice = $this->getOrCacheLatestPrice($stock);

        return new StockPriceResource($latestStockPrice);
    }

    public function getLatestPrices(StockPriceRequest $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $query = Stock::with('latestPrice');

        $query->when($request->has('stock_ids'), function ($q) use ($request) {
            $q->whereIn('id', $request->validated('stock_ids'));
        });

        $latestPrices = $query->get()
            ->filter(function ($stock) {
                return $stock->latestPrice !== null;
            })
            ->map(function ($stock) {
                return $this->getOrCacheLatestPrice($stock);
            });

        return StockPriceResource::collection($latestPrices);
    }

    public function calculatePriceChange(StockPriceChangeRequest $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $validated = $request->validated();

        $results = Stock::whereIn('id', $validated['stock_ids'])
            ->get()
            ->map(function ($stock) use ($validated) {
                return $this->calculateStockPriceChange(
                    $stock,
                    $validated['start_date'],
                    $validated['end_date']
                );
            });

        return PriceChangeResource::collection($results);
    }

    private function calculateStockPriceChange($stock, $startDate, $endDate): array
    {
        $startPrice = $stock->prices()
            ->where('timestamp', $startDate)
            ->orderBy('timestamp')
            ->first();

        $endPrice = $stock->prices()
            ->where('timestamp', $endDate)
            ->orderByDesc('timestamp')
            ->first();

        $calculation = [
            'stock_id' => $stock->id,
            'symbol' => $stock->symbol,
            'start_price' => null,
            'end_price' => null,
            'price_change' => null,
            'error' => null
        ];

        if (!$startPrice) {
            $calculation['error'] = 'No price available at start date';
            return $calculation;
        }

        if (!$endPrice) {
            $calculation['error'] = 'No price available at end date';
            return $calculation;
        }

        $calculation['start_time'] = $startDate;
        $calculation['end_time'] = $endDate;
        $calculation['start_price'] = $startPrice->close;
        $calculation['end_price'] = $endPrice->close;
        $calculation['price_change'] =
            ($endPrice->close - $startPrice->close) / $startPrice->close;

        return $calculation;
    }
}
