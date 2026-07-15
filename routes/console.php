<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 「直前空き」という性質上、時間単位よりも短い間隔でチェックする
Schedule::command('vacancy:check-watches')->everyTenMinutes();
