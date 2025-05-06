<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    public function register(): void
    {
        Telescope::night();

        $this->hideSensitiveRequestDetails();

        // Filter funksiyasını yenidən yazırıq və model əməliyyatlarını əlavə edirik
        Telescope::filter(function (IncomingEntry $entry) {
            // Model əməliyyatlarını mütləq izləyirik
            if ($entry->type === 'model') {
                return true;
            }

            // Cache əməliyyatlarını izləyirik
            if ($entry->type === 'cache') {
                return true;
            }

            // Digər vacib əməliyyatları izləyirik
            return $entry->isRequest() ||                  // HTTP sorğuları
                $entry->isQuery() ||                    // Database sorğuları
                $entry->isReportableException() ||      // Xətalar
                $entry->isFailedRequest() ||            // Uğursuz sorğular
                $entry->isFailedJob() ||                // Uğursuz jobs
                $entry->isScheduledTask() ||            // Scheduled tasks
                $entry->type === 'gate' ||              // Authorization yoxlamaları
                $entry->hasMonitoredTag();              // Xüsusi tag-lənmiş əməliyyatlar
        });

        // Tag sistemini genişləndiririk
        Telescope::tag(function (IncomingEntry $entry) {
            $tags = [];

            // Model əməliyyatlarını tag-ləyirik
            if ($entry->type === 'model') {
                $tags[] = 'model';
                // Əməliyyat növünə görə əlavə tag-lər
                if (isset($entry->content['action'])) {
                    $tags[] = 'model-' . $entry->content['action'];
                }
            }

            // Query əməliyyatlarını tag-ləyirik
            if ($entry->type === 'query') {
                $tags[] = 'query';
                if ($entry->content['time'] >= 100) {
                    $tags[] = 'slow-query';
                }
            }

            // Request əməliyyatlarını tag-ləyirik
            if ($entry->type === 'request') {
                $tags[] = 'request';
                $status = $entry->content['response_status'] ?? 0;

                if ($status >= 500) {
                    $tags[] = 'error';
                } elseif ($status >= 400) {
                    $tags[] = 'warning';
                }
            }

            // Gate (authorization) əməliyyatlarını tag-ləyirik
            if ($entry->type === 'gate') {
                $tags[] = 'gate';
                $tags[] = $entry->content['ability'] ?? 'unknown-ability';
            }

            return $tags;
        });
    }

    protected function hideSensitiveRequestDetails(): void
    {
        // Həssas request parametrlərini gizlədirik
        Telescope::hideRequestParameters([
            '_token',
            'password',
            'password_confirmation',
        ]);

        // Həssas header-ləri gizlədirik
        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'authorization',
        ]);
    }

    protected function gate(): void
    {
        // Telescope-a giriş icazələrini tənzimləyirik
        Gate::define('viewTelescope', function ($user) {
            // Admin roluna sahib istifadəçilərə icazə veririk
            return $user->hasRole('admin');

            // və ya spesifik email-lərə icazə vermək üçün:
            // return in_array($user->email, [
            //     'admin@example.com',
            //     'developer@example.com'
            // ]);
        });
    }

    protected function authorization()
    {
        Telescope::auth(function ($request) {
            // UUID-ni request-dən alırıq
            $uuid = $request->query('uuid');

            // UUID ilə giriş etmə cəhdi
            if ($uuid) {
                $user = User::where('uuid', $uuid)->first();

                if (!$user) {
                    abort(403, 'İstifadəçi tapılmadı.');
                }

                if (!$user->hasPermission('config_monitoring')) {
                    abort(403, 'Bu panelə giriş üçün icazəniz yoxdur.');
                }

                if (Auth::guest()) {
                    Auth::login($user);
                    return true;
                }
            }

            // Artıq login olmuş istifadəçi yoxlanışı
            if (Auth::check() && auth()->user()->hasPermission('config_monitoring')) {
                return true;
            }

            // Heç bir şərt ödənmirsə
            abort(403, 'Bu panelə giriş üçün icazəniz yoxdur.');
        });
    }

}
