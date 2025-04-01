<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stock;

class StockSeeder extends Seeder
{
    public function run()
    {
        $stocks = [
            [
                'symbol' => 'AAPL',
                'name' => 'Apple Inc.',
                'exchange' => 'NASDAQ',
                'currency' => 'USD'
            ],
            [
                'symbol' => 'META',
                'name' => 'Meta Platforms Inc.',
                'exchange' => 'NASDAQ',
                'currency' => 'USD'
            ],
            [
                'symbol' => 'MSFT',
                'name' => 'Microsoft Corporation',
                'exchange' => 'NASDAQ',
                'currency' => 'USD'
            ],
            [
                'symbol' => 'AMZN',
                'name' => 'Amazon.com Inc.',
                'exchange' => 'NASDAQ',
                'currency' => 'USD'
            ],
            [
                'symbol' => 'GOOGL',
                'name' => 'Alphabet Inc.',
                'exchange' => 'NASDAQ',
                'currency' => 'USD'
            ],
        ];

        foreach ($stocks as $stockData) {
            Stock::firstOrCreate(
                ['symbol' => $stockData['symbol']],
                $stockData
            );
        }
    }
}
