<?php

use App\Console\Commands\SendUnsentTicketsToWebserviceCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(SendUnsentTicketsToWebserviceCommand::class)->hourly();
