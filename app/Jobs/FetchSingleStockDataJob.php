<?php

namespace App\Jobs;

use App\Models\Stock;
use App\Models\StockPrice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchSingleStockDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Stock $stock
    ) {}

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $config = config('services.alpha_vantage');
        $this->fetchAndStoreData($this->stock, $config);
    }

    private function fetchAndStoreData(Stock $stock, array $config, string $interval = '1min', string $outputSize = 'compact'): void
    {
        $response = Http::timeout($config['timeout'])
            ->baseUrl($config['base_url'])
            ->get('/query', [
                'function' => 'TIME_SERIES_INTRADAY',
                'symbol' => $stock->symbol,
                'interval' => $interval,
                'apikey' => $config['key'],
                'outputsize' => $outputSize
            ]);

        if ($response->failed()) {
            $this->fail(new \Exception("API request failed: " . $response->status()));
        }

        $data = $response->json();

        $timeSeriesKey = "Time Series ($interval)";

        if (!isset($data[$timeSeriesKey])) {
            $this->fail(new \Exception("Invalid API response format - missing '$timeSeriesKey'"));
        }

        if (isset($data['Information'])) {
            if (str_contains($data['Information'], 'rate limit')) {
                Log::critical("API Rate Limit Reached for {$this->stock->symbol}");
                $this->fail(new \Exception("API Rate Limit: " . $data['Information']));
                return;
            }
        }

        $this->storeAllDataPoints($stock->id, $data[$timeSeriesKey]);
    }

    private function storeAllDataPoints(int $stockId, array $timeSeries): void
    {
        $dataToInsert = [];
        $now = now();

        foreach ($timeSeries as $timestamp => $values) {
            $dataToInsert[] = [
                'stock_id' => $stockId,
                'timestamp' => $timestamp,
                'open' => $values['1. open'],
                'high' => $values['2. high'],
                'low' => $values['3. low'],
                'close' => $values['4. close'],
                'volume' => $values['5. volume'],
                'created_at' => $now,
                'updated_at' => $now
            ];

            if (count($dataToInsert) >= 100) {
                StockPrice::insertOrIgnore($dataToInsert);
                $dataToInsert = [];
            }
        }

        if (!empty($dataToInsert)) {
            StockPrice::insertOrIgnore($dataToInsert);
        }

        Cache::forget("stock:{$stockId}:latest_price");
    }
}
