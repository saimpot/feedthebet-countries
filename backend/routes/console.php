<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

if (config('countries.cache.enabled')) {
    Schedule::command('countries:warm')->daily();
}
