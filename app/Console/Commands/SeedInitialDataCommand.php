<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SeedInitialDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-initial-data {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed permissions and translations data for the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {

        $this->info('Starting initial data seeding...');

        try {
            DB::beginTransaction();

            // Run Permission Seeder
            $this->info('Seeding permissions...');
            $this->runSeeder('PermissionSeeder');

            // Run Translation Seeder
            $this->info('Seeding translations...');
            $this->runSeeder('TranslationSeeder');

            DB::commit();

            $this->info('✅ Initial data seeding completed successfully!');
            return 0;
        } catch (Exception $e) {
            DB::rollBack();
            $this->error('❌ Error during seeding: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Run a specific seeder class
     *
     * @param string $seederClass
     * @return void
     * @throws Exception
     */
    private function runSeeder(string $seederClass): void
    {
        $fullSeederClass = "Database\\Seeders\\{$seederClass}";

        // Check if seeder class exists
        if (!class_exists($fullSeederClass)) {
            throw new Exception("Seeder class {$fullSeederClass} not found. Please create this seeder class first.");
        }

        // Run the seeder
        Artisan::call('db:seed', [
            '--class' => $fullSeederClass,
            '--force' => true
        ]);

        $this->info("  - {$seederClass} executed successfully");
    }
}
