<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceChangeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'stock_id' => $this['stock_id'],
            'symbol' => $this['symbol'],
            'start_price' => $this['start_price'],
            'end_price' => $this['end_price'],
            'error' => $this['error'],
            'price_change' => $this['price_change'] ? round($this['price_change'], 4) : null,
            'timeframe' => [
                'start' => $this['start_time'] ?? null,
                'end' => $this['end_time'] ?? null
            ]
        ];
    }
}
