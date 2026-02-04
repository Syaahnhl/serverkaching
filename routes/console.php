<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // Import Schedule

// Command bawaan (Biarkan saja)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Memanggil file app/Console/Commands/ResetTables.php
Schedule::command('tables:reset')->dailyAt('00:00');