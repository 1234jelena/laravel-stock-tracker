<?php

namespace Tests\Feature;

use App\Models\Stock;
use App\Models\StockPrice;
use Database\Seeders\StockSeeder;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockPriceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(StockSeeder::class);

        $this->stock1 = Stock::where('symbol', 'AAPL')->first();
        $this->stock2 = Stock::where('symbol', 'META')->first();
        $this->stock3 = Stock::where('symbol', 'MSFT')->first();
        $this->stock4 = Stock::where('symbol', 'AMZN')->first();

        $now = Carbon::now();
        $this->fiveMinutesAgo = $now->copy()->subMinutes(5)->format('Y-m-d H:i:00');
        $this->currentTime = $now->format('Y-m-d H:i:00');

        StockPrice::factory()->create([
            'stock_id' => $this->stock1->id,
            'close' => 100,
            'timestamp' => $this->fiveMinutesAgo
        ]);
        $this->latestPrice1 = StockPrice::factory()->create([
            'stock_id' => $this->stock1->id,
            'close' => 110,
            'timestamp' => $this->currentTime
        ]);

        // META prices
        StockPrice::factory()->create([
            'stock_id' => $this->stock2->id,
            'close' => 50,
            'timestamp' => $this->fiveMinutesAgo
        ]);
        $this->latestPrice2 = StockPrice::factory()->create([
            'stock_id' => $this->stock2->id,
            'close' => 60,
            'timestamp' => $this->currentTime
        ]);

        StockPrice::factory()->create([
            'stock_id' => $this->stock3->id,
            'close' => 50,
            'timestamp' => $this->fiveMinutesAgo
        ]);

        StockPrice::factory()->create([
            'stock_id' => $this->stock4->id,
            'close' => 50,
            'timestamp' => $this->fiveMinutesAgo
        ]);
    }

    public function test_pass_return_latest_price_for_single_stock()
    {
        $response = $this->getJson("/api/stock-prices/{$this->stock1->id}/latest");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'stock' => [
                        'id',
                        'symbol',
                        'name',
                        'exchange',
                        'currency',
                        'created_at',
                        'updated_at'
                    ],
                    'timestamp',
                    'open',
                    'high',
                    'low',
                    'volume',
                    'close'
                ]
            ])
            ->assertJson([
                'data' => [
                    'stock' => [
                        'id' => $this->stock1->id,
                        'symbol' => $this->stock1->symbol
                    ],
                    'close' => number_format($this->latestPrice1->close, 4, '.', '')
                ]
            ]);

        $this->assertDatabaseHas('stock_prices', [
            'id' => $this->latestPrice1->id,
            'stock_id' => $this->stock1->id
        ]);
    }

    public function test_fail_return_latest_price_for_non_existent_stock()
    {
        $response = $this->getJson("/api/stock-prices/999999/latest");
        $response->assertStatus(404);
    }

    public function test_pass_return_latest_price_for_multiple_stocks()
    {
        $response = $this->getJson("/api/stock-prices/latest?stock_ids[]={$this->stock1->id}&stock_ids[]={$this->stock2->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'stock' => [
                            'id',
                            'symbol',
                            'name',
                            'exchange',
                            'currency',
                            'created_at',
                            'updated_at'
                        ],
                        'timestamp',
                        'open',
                        'high',
                        'low',
                        'volume',
                        'close'
                    ]
                ]
            ])
            ->assertJsonCount(2, 'data');

        $responseData = $response->json('data');

        $this->assertNotEmpty(
            collect($responseData)->firstWhere('stock.id', $this->stock1->id),
            'AAPL stock not found in response'
        );

        $this->assertNotEmpty(
            collect($responseData)->firstWhere('stock.id', $this->stock2->id),
            'META stock not found in response'
        );

        $aaplData = collect($responseData)->firstWhere('stock.id', $this->stock1->id);
        $this->assertEquals('AAPL', $aaplData['stock']['symbol']);
        $this->assertEquals('110.0000', $aaplData['close']);

        $metaData = collect($responseData)->firstWhere('stock.id', $this->stock2->id);
        $this->assertEquals('META', $metaData['stock']['symbol']);
        $this->assertEquals('60.0000', $metaData['close']);
    }

    public function test_pass_return_all_when_no_stock_ids_specified()
    {
        $response = $this->getJson("/api/stock-prices/latest");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'stock' => [
                            'id',
                            'symbol',
                            'name',
                            'exchange',
                            'currency',
                            'created_at',
                            'updated_at'
                        ],
                        'timestamp',
                        'open',
                        'high',
                        'low',
                        'volume',
                        'close'
                    ]
                ]
            ])
            ->assertJsonCount(4, 'data');

        $returnedStockIds = collect($response->json('data'))
            ->pluck('stock.id')
            ->toArray();

        $this->assertContains($this->stock1->id, $returnedStockIds);
        $this->assertContains($this->stock4->id, $returnedStockIds);
    }

    public function test_fail_calculate_price_change_wrong_time_format()
    {
        $response = $this->postJson("/api/stocks-prices/change", [
            'stock_ids' => [$this->stock1->id, $this->stock2->id],
            'start_date' => Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'),
            'end_date' => Carbon::now()->format('Y-m-d H:i:s')
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'start_date',
                'end_date'
            ])
            ->assertJsonFragment([
                'message' => 'The start date field must match the format Y-m-d H:i:00. (and 1 more error)'
            ]);
    }

    public function test_fail_calculate_price_change_missing_required_fields()
    {
        $response = $this->postJson("/api/stocks-prices/change", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'stock_ids',
                'start_date',
                'end_date'
            ]);
    }

    public function test_fail_calculate_price_change_invalid_stock_ids()
    {
        $response = $this->postJson("/api/stocks-prices/change", [
            'stock_ids' => [999],
            'start_date' => Carbon::now()->subDay()->format('Y-m-d H:i:00'),
            'end_date' => Carbon::now()->format('Y-m-d H:i:00')
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'stock_ids.0' => 'The selected stock_ids.0 is invalid.'
            ]);
    }

    public function test_fail_calculate_price_change_end_date_before_start_date()
    {
        $response = $this->postJson("/api/stocks-prices/change", [
            'stock_ids' => [$this->stock1->id],
            'start_date' => Carbon::now()->format('Y-m-d H:i:00'),
            'end_date' => Carbon::now()->subDay()->format('Y-m-d H:i:00')
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'end_date' => 'End date must be after start date'
            ]);
    }

    public function test_fail_calculate_price_change_non_array_stock_ids()
    {
        $response = $this->postJson("/api/stocks-prices/change", [
            'stock_ids' => 'not-an-array',
            'start_date' => Carbon::now()->subDay()->format('Y-m-d H:i:00'),
            'end_date' => Carbon::now()->format('Y-m-d H:i:00')
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'stock_ids' => 'The stock ids field must be an array.'
            ]);
    }

    public function test_fail_calculate_price_change_empty_stock_ids()
    {
        $response = $this->postJson("/api/stocks-prices/change", [
            'stock_ids' => [],
            'start_date' => Carbon::now()->subDay()->format('Y-m-d H:i:00'),
            'end_date' => Carbon::now()->format('Y-m-d H:i:00')
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'stock_ids' => 'The stock ids field is required.'
            ]);
    }

    public function test_pass_calculate_price_change_with_valid_data()
    {
        $response = $this->postJson("/api/stocks-prices/change", [
            'stock_ids' => [$this->stock1->id, $this->stock2->id],
            'start_date' => $this->fiveMinutesAgo,
            'end_date' => $this->currentTime
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'stock_id',
                        'symbol',
                        'start_price',
                        'end_price',
                        'price_change',
                        'timeframe' => [
                            'start',
                            'end'
                        ],
                        'error'
                    ]
                ]
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'stock_id' => $this->stock1->id,
                'symbol' => $this->stock1->symbol,
                'start_price' => "100.0000",
                'end_price' => "110.0000",
                'price_change' => 0.1, // (110-100)/100
                'error' => null,
            ])
            ->assertJsonFragment([
                'stock_id' => $this->stock2->id,
                'symbol' => $this->stock2->symbol,
                'start_price' => "50.0000",
                'end_price' => "60.0000",
                'price_change' => 0.2, // (60-50)/50
                'error' => null,
            ]);
    }
}
