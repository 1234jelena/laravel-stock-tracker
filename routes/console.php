<?php

use App\Console\Commands\DispatchStockFetchCommand;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(DispatchStockFetchCommand::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
