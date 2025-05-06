<?php

namespace App\Services\App\System;

use Jenssegers\Agent\Agent;

class DeviceDetectionService
{
    protected Agent $agent;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    public function detectDevice(): string
    {
        return $this->agent->device() ?? 'unknown';
    }

    public function detectBrowser(): string
    {
        return $this->agent->browser() . ' ' . $this->agent->version($this->agent->browser());
    }

    public function detectOS(): string
    {
        return $this->agent->platform() . ' ' . $this->agent->version($this->agent->platform());
    }

    public function isMobileDevice(): bool
    {
        return $this->agent->isMobile() || $this->agent->isTablet();
    }

    public function getDeviceInfo(): array
    {
        return [
            'device' => $this->detectDevice(),
            'browser' => $this->detectBrowser(),
            'os' => $this->detectOS(),
            'is_mobile' => $this->isMobileDevice()
        ];
    }
}
