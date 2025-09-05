<?php

use Laravel\Telescope\Http\Middleware\Authorize;
use Laravel\Telescope\Watchers;

return [
    'enabled' => env('TELESCOPE_ENABLED', false),

    // Ayrı database connection
    'driver' => 'database',
    'storage' => [
        'database' => [
            'connection' => env('TELESCOPE_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),
            'chunk' => 1000,
        ],
    ],

    // Hansı dataları izləyəcəyik
    'watchers' => [
        Watchers\CacheWatcher::class => [
            'enabled' => env('TELESCOPE_CACHE_WATCHER', true),
            'hidden' => [],
        ],

        Watchers\CommandWatcher::class => [
            'enabled' => env('TELESCOPE_COMMAND_WATCHER', true),
            'ignore' => [],
        ],

        Watchers\DumpWatcher::class => [
            'enabled' => env('TELESCOPE_DUMP_WATCHER', true),
        ],

        Watchers\EventWatcher::class => [
            'enabled' => env('TELESCOPE_EVENT_WATCHER', true),
            'ignore' => [],
        ],

        Watchers\ExceptionWatcher::class => [
            'enabled' => env('TELESCOPE_EXCEPTION_WATCHER', true),
            'ignore' => [],
        ],

        Watchers\JobWatcher::class => [
            'enabled' => env('TELESCOPE_JOB_WATCHER', true),
        ],

        Watchers\LogWatcher::class => [
            'enabled' => env('TELESCOPE_LOG_WATCHER', true),
            'ignore' => [],
        ],

        Watchers\MailWatcher::class => [
            'enabled' => env('TELESCOPE_MAIL_WATCHER', true),
            'only' => [],
        ],

        Watchers\ModelWatcher::class => [
            'enabled' => env('TELESCOPE_MODEL_WATCHER', true),
            'events' => ['eloquent.*'],
            'hydrations' => true,
        ],

        Watchers\NotificationWatcher::class => [
            'enabled' => env('TELESCOPE_NOTIFICATION_WATCHER', true),
        ],

        Watchers\QueryWatcher::class => [
            'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
            'ignore_packages' => true,
            'slow' => 100,
        ],

        Watchers\RequestWatcher::class => [
            'enabled' => env('TELESCOPE_REQUEST_WATCHER', true),
            'size_limit' => env('TELESCOPE_RESPONSE_SIZE_LIMIT', 64),
            'ignore' => [],
        ],

        Watchers\ScheduleWatcher::class => [
            'enabled' => env('TELESCOPE_SCHEDULE_WATCHER', true),
        ],

        Watchers\ViewWatcher::class => [
            'enabled' => env('TELESCOPE_VIEW_WATCHER', true),
            'data' => true,
        ],
    ],

    // Köhnə dataların təmizlənməsi
    'prune' => [
        'hours' => 24 * 7,    // 1 həftəlik datanı saxla
        'chunk' => 1000,      // Hər dəfə 1000 sətir sil
    ],
];
