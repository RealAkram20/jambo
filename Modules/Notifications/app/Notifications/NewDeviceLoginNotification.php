<?php

namespace Modules\Notifications\app\Notifications;

class NewDeviceLoginNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
    ) {
    }

    protected function settingKey(): string
    {
        return 'new_device_login';
    }

    public function toDatabase($notifiable): array
    {
        $ua = $this->parseUa($this->userAgent ?? '');

        return [
            'title'        => 'New device signed in',
            'message'      => "A sign-in to your account was detected from {$ua}" . ($this->ipAddress ? " ({$this->ipAddress})" : '') . '.',
            'icon'         => 'ph-device-mobile',
            'colour'       => 'warning',
            'image'        => null,
            'action_url'   => route('profile.devices', ['username' => $notifiable->username]),
            'action_label' => 'Review devices',
            'ip'           => $this->ipAddress,
            'user_agent'   => $this->userAgent,
        ];
    }

    private function parseUa(string $ua): string
    {
        if ($ua === '') return 'an unknown device';
        // Lightweight readable guess — full parsing lives in ProfileHub.
        foreach (['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'] as $browser) {
            if (stripos($ua, $browser) !== false) return $browser;
        }
        return 'a new browser';
    }
}
