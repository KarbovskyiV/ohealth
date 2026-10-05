<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('first-run', function () {
    if (app()->isLocal() && (blank(config('ehealth.test.client_id')) || blank(config('ehealth.test.client_secret')))) {
        throw new RuntimeException('Test eHealth client ID and secret must be configured before first-run.');
    }

    if ($this->call('install', ['--clear' => true, '--wipe' => true, '--key' => true]) !== 0) {
        throw new RuntimeException('Installation failed; database seeding was skipped.');
    }

    if ($this->call('db:seed', ['--class' => 'DatabaseSeeder']) !== 0) {
        throw new RuntimeException('Database seeding failed.');
    }
})->purpose('Completes the first run of the application');
