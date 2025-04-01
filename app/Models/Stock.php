<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Stock extends Model
{
    protected $fillable = ['symbol'];
    public function prices(): HasMany
    {
        return $this->hasMany(StockPrice::class);
    }

    public function latestPrice(): HasOne
    {
        return $this->hasOne(StockPrice::class)->latestOfMany('timestamp');
    }
}
