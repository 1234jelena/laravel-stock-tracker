<?php

namespace Database\Factories;

use App\Models\Stock;
use App\Models\StockPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockPriceFactory extends Factory
{
    protected $model = StockPrice::class;

    public function definition()
    {
        $price = $this->faker->randomFloat(2, 10, 1000);
        $change = $this->faker->randomFloat(2, -10, 10);

        return [
            'open' => $price,
            'high' => $price + abs($change),
            'low' => $price - abs($change),
            'close' => $price + $change,
            'volume' => $this->faker->numberBetween(1000, 1000000),
            'timestamp' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
