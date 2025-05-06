<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MessagingSystemReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messaging:reset {--seed : Reset edildikdən sonra test verilənləri yaradır}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mesajlaşma cədvəllərini təmizləyir və istəyə görə yenidən doldurur';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if ($this->confirm('Bu əməliyyat bütün söhbət və mesaj məlumatlarını siləcək. Davam etmək istəyirsiniz?')) {
            try {
                $this->info('Mesajlaşma cədvəlləri təmizlənir...');

                // Foreign key yoxlanmasını müvəqqəti olaraq söndürürük
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                // Mesajları və söhbətləri silirik
                DB::table('messages')->truncate();
                DB::table('conversations')->truncate();

                // Foreign key yoxlanmasını yenidən aktivləşdiririk
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                $this->info('Cədvəllər uğurla təmizləndi.');

                // Əgər seed parametri varsa, verilənləri yenidən doldururuq
                if ($this->option('seed')) {
                    $this->info('Yeni test verilənləri yaradılır...');
                    $this->call('db:seed', [
                        '--class' => 'Database\\Seeders\\MessagingSystemSeeder'
                    ]);
                }

                $this->info('Əməliyyat uğurla tamamlandı!');
                return 0;
            } catch (\Exception $e) {
                $this->error('Əməliyyat zamanı xəta baş verdi: ' . $e->getMessage());
                return 1;
            }
        } else {
            $this->info('Əməliyyat ləğv edildi.');
            return 0;
        }
    }
}
