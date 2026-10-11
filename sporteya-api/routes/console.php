<?php

use App\Jobs\LobbyExpiryJob;
use App\Jobs\UpdatePlayerStatusJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Phase B9: Scheduled Background Jobs
Schedule::job(new UpdatePlayerStatusJob)->everyThirtyMinutes();
Schedule::job(new LobbyExpiryJob)->everyFifteenMinutes();
