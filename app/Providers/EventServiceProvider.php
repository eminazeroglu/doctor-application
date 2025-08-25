<?php

namespace App\Providers;

use App\Listeners\SendWelcomeEmail;
use App\Events\Notification\NotificationCreated;
use App\Events\Notification\NotificationRead;
use App\Listeners\Notification\SendNotificationToChannels;
use App\Listeners\Notification\UpdateNotificationCache;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendWelcomeEmail::class,
        ],

        NotificationCreated::class => [
            SendNotificationToChannels::class,
        ],

        NotificationRead::class => [
            UpdateNotificationCache::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
