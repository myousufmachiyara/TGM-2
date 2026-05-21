<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

// 1. Existing Inspire Command
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 2. Schedule your Shopify Sync
// Ensure you have a command named 'shopify:sync' registered
Schedule::command('shopify:sync')->everyMinute();

Schedule::command('queue:work --stop-when-empty --timeout=600 --memory=256')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();