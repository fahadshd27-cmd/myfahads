<?php

use App\Services\DepositService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('deposits:expire', function (DepositService $deposits) {
    $count = $deposits->markExpiredDeposits();
    $this->info("Expired {$count} deposits.");
})->purpose('Expire pending deposit orders');
