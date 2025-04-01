<?php

namespace App\Console\Commands;

use App\Jobs\FetchSingleStockDataJob;
use App\Models\Stock;
use Illuminate\Console\Command;

class DispatchStockFetchCommand extends Command
{
    protected $signature = 'stocks:dispatch-fetch';
    protected $description = 'Dispatch job to fetch stock prices';

    public function handle(): void
    {
        $stocks = Stock::all();

        foreach ($stocks as $stock) {
            FetchSingleStockDataJob::dispatch($stock);
        }
    }
}
