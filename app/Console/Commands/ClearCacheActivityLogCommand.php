<?php

namespace App\Console\Commands;

use App\Services\Module\ActivityLogService;
use Illuminate\Console\Command;

class ClearCacheActivityLogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app(ActivityLogService::class)->cleanup(0);
        $this->info('All caches have been cleared successfully!');
    }
}
