<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('orders:sync')->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
Schedule::command('orders:sync --full')->dailyAt('03:00')->withoutOverlapping()->onOneServer();
Schedule::command('orders:approve-due')->hourly()->withoutOverlapping()->onOneServer();
Schedule::command('fraud:scan')->dailyAt('04:00')->onOneServer();
