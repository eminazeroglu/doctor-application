<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ClearCacheCommand extends Command
{
    protected $signature = 'repo:clear';
    protected $description = 'Clear all application caches';

    public function handle(): void
    {
        try {
            // AppServiceProvider-in konfiq yeniləməsini müvəqqəti olaraq dayandırırıq
            Config::set('app.skip_settings_load', true);

            // Bütün keşləri təmizləyirik
            Cache::flush();

            $this->info('All caches have been cleared successfully!');

        } finally {
            // Konfiq yeniləməsini bərpa edirik
            Config::set('app.skip_settings_load', false);
        }
    }
}
