<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TerminalController extends Controller
{
    public string $forbiddenMessage = 'Bu əməliyyat üçün icazəniz yoxdur.';

    /**
     * Admin paneldən icra edilə biləcək təhlükəsiz əmrlər
     */
    private array $allowedCommands = [
        // Sənədləşmə
        'doc:generate' => [
            'name' => 'API sənədi yarat',
            'description' => 'Bu əmir api dokumentasiya üçün sənədləşmə yaradır.',
            'group' => 'Sənədləşmə'
        ],

        'app:seed-initial-data' => [
            'name' => 'Seederları işlət',
            'description' => 'Sistemdə lazım olan seederlarda dəyişiklik olunanda bu əmir işlədilir',
            'group' => 'Sənədləşmə'
        ],

        // Sistem Vəziyyəti
        'about' => [
            'name' => 'Sistem Haqqında',
            'description' => 'Sistemin əsas məlumatları və statusu',
            'usage_time' => 'Sistem yoxlanışı zamanı',
            'group' => 'Sistem Vəziyyəti'
        ],
        'env' => [
            'name' => 'Cari Mühit',
            'description' => 'Sistemin cari mühit parametrləri',
            'usage_time' => 'Mühit parametrlərini yoxlamaq üçün',
            'group' => 'Sistem Vəziyyəti'
        ],
        'event:list' => [
            'name' => 'Event Siyahısı',
            'description' => 'Sistemdəki bütün event və listener-lərin siyahısı',
            'usage_time' => 'Event sistemini yoxlamaq üçün',
            'group' => 'Sistem Vəziyyəti'
        ],
        'route:list' => [
            'name' => 'Route Siyahısı',
            'description' => 'Sistemdəki bütün route-ların siyahısı',
            'usage_time' => 'Route-ları yoxlamaq üçün',
            'group' => 'Sistem Vəziyyəti'
        ],

        // Keş İdarəetməsi
        'repo:clear' => [
            'name' => 'Modellərdəki Keşi Təmizlə',
            'description' => 'Bütün sistemdə olan model keşini təmizləyir',
            'usage_time' => 'Sistem yenilənməsindən sonra',
            'group' => 'Keş İdarəetməsi'
        ],
        'log:clear' => [
            'name' => 'Logları Təmizlə',
            'description' => 'Bütün sistemdə olan logları təmizləyir',
            'usage_time' => 'Sistem yenilənməsindən sonra',
            'group' => 'Keş İdarəetməsi'
        ],
        'cache:clear' => [
            'name' => 'Keşi Təmizlə',
            'description' => 'Bütün sistem keşini təmizləyir',
            'usage_time' => 'Sistem yenilənməsindən sonra',
            'group' => 'Keş İdarəetməsi'
        ],
        'view:clear' => [
            'name' => 'View Keşini Təmizlə',
            'description' => 'Kompilyasiya edilmiş view fayllarını təmizləyir',
            'usage_time' => 'View-larda dəyişiklik olduqda',
            'group' => 'Keş İdarəetməsi'
        ],
        'route:clear' => [
            'name' => 'Route Keşini Təmizlə',
            'description' => 'Route keşini təmizləyir',
            'usage_time' => 'Route-larda dəyişiklik olduqda',
            'group' => 'Keş İdarəetməsi'
        ],
        'config:clear' => [
            'name' => 'Config Keşini Təmizlə',
            'description' => 'Konfiqurasiya keşini təmizləyir',
            'usage_time' => 'Konfiqurasiyada dəyişiklik olduqda',
            'group' => 'Keş İdarəetməsi'
        ],
        'migrate:fresh --seed' => [
            'name' => 'DB yenilə',
            'description' => 'Database sıfırla və yenilə',
            'usage_time' => 'Database sıfırla və yenilə',
            'group' => 'Keş İdarəetməsi'
        ],

        // Queue Monitorinqi
        'queue:monitor' => [
            'name' => 'Queue Monitor',
            'description' => 'Queue-ların ölçüsünü monitor edir',
            'usage_time' => 'Queue sistemini izləmək üçün',
            'group' => 'Queue Monitorinqi'
        ],
        'queue:failed' => [
            'name' => 'Uğursuz İşlər',
            'description' => 'Uğursuz queue işlərinin siyahısı',
            'usage_time' => 'Problemli queue işlərini yoxlamaq üçün',
            'group' => 'Queue Monitorinqi'
        ],
        'queue:prune-batches' => [
            'name' => 'Köhnə Batch-ları Təmizlə',
            'description' => 'Köhnəlmiş batch-ları təmizləyir',
            'usage_time' => 'Müntəzəm təmizlik üçün',
            'group' => 'Queue Monitorinqi'
        ],
        'queue:prune-failed' => [
            'name' => 'Uğursuz İşləri Təmizlə',
            'description' => 'Köhnəlmiş uğursuz işləri təmizləyir',
            'usage_time' => 'Müntəzəm təmizlik üçün',
            'group' => 'Queue Monitorinqi'
        ],

        // Planlaşdırılmış Tapşırıqlar
        'schedule:list' => [
            'name' => 'Schedule Siyahısı',
            'description' => 'Bütün planlaşdırılmış tapşırıqların siyahısı',
            'usage_time' => 'Cron tapşırıqlarını yoxlamaq üçün',
            'group' => 'Planlaşdırılmış Tapşırıqlar'
        ],

        // Təhlükəsizlik
        'sanctum:prune-expired' => [
            'name' => 'Expired Tokenları Təmizlə',
            'description' => 'Vaxtı keçmiş sanctum tokenlarını təmizləyir',
            'usage_time' => 'Müntəzəm təmizlik üçün',
            'group' => 'Təhlükəsizlik'
        ],
        'auth:clear-resets' => [
            'name' => 'Reset Tokenları Təmizlə',
            'description' => 'Vaxtı keçmiş şifrə sıfırlama tokenlarını təmizləyir',
            'usage_time' => 'Müntəzəm təmizlik üçün',
            'group' => 'Təhlükəsizlik'
        ],

        // Baxım Rejimi
        'down' => [
            'name' => 'Baxım Rejimini Aktiv Et',
            'description' => 'Saytı baxım rejiminə keçirir',
            'usage_time' => 'Təmir/yenilənmə zamanı',
            'group' => 'Baxım Rejimi'
        ],
        'up' => [
            'name' => 'Baxım Rejimindən Çıx',
            'description' => 'Saytı normal rejimə qaytarır',
            'usage_time' => 'Təmir/yenilənmə bitdikdə',
            'group' => 'Baxım Rejimi'
        ]
    ];

    /**
     * Admin panel üçün komandaları qaytarır
     */
    public function getCommands()
    {
        if (!request()->user()->hasPermission('user_terminal')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        // Komandaları qruplara görə qruplaşdırırıq
        $groupedCommands = [];
        foreach ($this->allowedCommands as $command => $details) {
            $group = $details['group'];
            if (!isset($groupedCommands[$group])) {
                $groupedCommands[$group] = [];
            }

            $groupedCommands[$group][] = [
                'name' => $details['name'],
                'command' => $command,
                'description' => $details['description'],
                'usage_time' => $details['usage_time'] ?? null
            ];
        }

        // Qrupları və komandaları qaytarırıq
        return response()->json([
            'status' => 'success',
            'data' => $groupedCommands
        ]);
    }

    /**
     * Execute artisan command
     */
    public function execute()
    {
        // İcazə yoxlaması
        if (!request()->user()->hasPermission('user_terminal')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $command = request()->input('command');
        $baseCommand = explode(' ', $command)[0];

        // Əmrin icazəli olub-olmadığını yoxlayırıq
        if (!array_key_exists($baseCommand, $this->allowedCommands)) {
            return response()->json(['error' => 'Bu əmrin istifadəsinə icazə verilmir'], 403);
        }

        try {
            // Xüsusi hallar üçün parametrlər
            $parameters = $this->getCommandParameters($command);

            // Əmri icra edirik
            Artisan::call($command, $parameters, new \Symfony\Component\Console\Output\BufferedOutput);
            $output = Artisan::output();

            // Əmrin nəticəsini formatlaşdırırıq
            $formattedOutput = $this->formatCommandOutput($command, $output);

            // Əmrin detallarını çıxarırıq
            $commandDetails = $this->allowedCommands[$baseCommand];

            // Uğurlu əməliyyatı loglayırıq
            Log::info('Terminal əmri icra edildi', [
                'user' => request()->user()->name,
                'command' => $command,
                'group' => $commandDetails['group'],
                'output' => $output
            ]);

            return response()->json([
                'output' => $formattedOutput,
                'command' => [
                    'name' => $commandDetails['name'],
                    'description' => $commandDetails['description'],
                    'usage_time' => $commandDetails['usage_time'] ?? null,
                    'group' => $commandDetails['group']
                ]
            ]);

        } catch (\Exception $e) {
            // Xətanı loglayırıq
            Log::error('Terminal əmri xətası', [
                'user' => request()->user()->name,
                'command' => $command,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Əmr icra edilərkən xəta baş verdi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xüsusi əmrlər üçün parametrləri təyin edir
     */
    private function getCommandParameters(string $command): array
    {
        $parameters = [];
        $baseCommand = explode(' ', $command)[0];

        switch ($baseCommand) {
            case 'queue:retry':
                $parameters['--queue'] = 'default';
                break;

            case 'queue:prune-failed':
                $parameters['--hours'] = 24 * 7; // 1 həftədən köhnə
                break;

            case 'sanctum:prune-expired':
                $parameters['--hours'] = 24; // 24 saatdan köhnə
                break;

            case 'down':
                $parameters = [
                    '--refresh' => 15,
                    '--secret' => 'admin-' . time(),
                    '--render' => 'maintenance'
                ];
                break;
        }

        return $parameters;
    }

    /**
     * Əmr nəticəsini formatlaşdırır
     */
    private function formatCommandOutput(string $command, string $output): array|string
    {
        // Əgər çıxış boşdursa
        if (empty(trim($output))) {
            return [
                'type' => 'simple',
                'content' => "[✓] Əməliyyat uğurla tamamlandı"
            ];
        }

        // Əmrin əsas hissəsini alırıq
        $baseCommand = explode(' ', $command)[0];

        // List tipli əmrlər üçün
        if (in_array($baseCommand, ['route:list', 'event:list', 'queue:failed', 'schedule:list'])) {
            return [
                'type' => 'table',
                'data' => $this->parseListOutput($baseCommand, $output)
            ];
        }

        // Sadə əmrlər üçün
        return [
            'type' => 'simple',
            'content' => $output
        ];
    }

    /**
     * List tipli outputları parse edir
     */
    private function parseListOutput(string $command, string $output): array
    {
        return match($command) {
            'route:list' => $this->parseRouteList($output),
            'event:list' => $this->parseEventList($output),
            'queue:failed' => $this->parseQueueList($output),
            'schedule:list' => $this->parseScheduleList($output),
            default => []
        };
    }

    /**
     * Route listini parse edir
     */
    private function parseRouteList(string $output): array
    {
        $lines = explode("\n", $output);
        $routes = [];
        $headers = ['method', 'uri', 'name', 'action'];

        foreach ($lines as $index => $line) {
            if (empty(trim($line)) || $index === 0) continue;

            $parts = preg_split('/\s+/', trim($line), 4);
            if (count($parts) < 4) continue;

            [$method, $uri, $name, $action] = $parts;

            $routes[] = [
                'method' => $method,
                'uri' => $uri,
                'name' => $name ?: '-',
                'action' => str_replace('App\\Http\\Controllers\\', '', $action)
            ];
        }

        return [
            'headers' => $headers,
            'rows' => $routes
        ];
    }

    /**
     * Event listini parse edir
     */
    private function parseEventList(string $output): array
    {
        $lines = explode("\n", $output);
        $events = [];
        $currentEvent = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (str_contains($line, 'Event:')) {
                $currentEvent = str_replace('Event:', '', $line);
                $events[] = [
                    'event' => trim($currentEvent),
                    'listeners' => []
                ];
            } elseif (str_contains($line, 'Listener:') && $currentEvent !== null) {
                $events[count($events) - 1]['listeners'][] = trim(str_replace('Listener:', '', $line));
            }
        }

        return [
            'headers' => ['event', 'listeners'],
            'rows' => $events
        ];
    }

    /**
     * Queue failed listini parse edir
     */
    private function parseQueueList(string $output): array
    {
        $lines = explode("\n", $output);
        $jobs = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            // Queue failed formatını parse et
            // Bu hissəni queue failed outputuna görə uyğunlaşdırmaq lazımdır
            $jobs[] = [
                'id' => '',  // parse from line
                'class' => '', // parse from line
                'failed_at' => '', // parse from line
                'error' => '' // parse from line
            ];
        }

        return [
            'headers' => ['id', 'class', 'failed_at', 'error'],
            'rows' => $jobs
        ];
    }

    /**
     * Schedule listini parse edir
     */
    private function parseScheduleList(string $output): array
    {
        $lines = explode("\n", $output);
        $tasks = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            if (preg_match('/^\s*\[([^\]]+)\](.*)/', $line, $matches)) {
                $tasks[] = [
                    'schedule' => trim($matches[1]),
                    'command' => trim($matches[2])
                ];
            }
        }

        return [
            'headers' => ['schedule', 'command'],
            'rows' => $tasks
        ];
    }
}
