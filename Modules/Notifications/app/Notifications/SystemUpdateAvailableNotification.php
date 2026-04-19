<?php

namespace Modules\Notifications\app\Notifications;

class SystemUpdateAvailableNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly string $version,
        public readonly ?string $releaseNotesUrl = null,
    ) {
    }

    protected function settingKey(): string
    {
        return 'system_update_available';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'        => "Jambo {$this->version} available",
            'message'      => 'A new Jambo release is ready to install.',
            'icon'         => 'ph-download-simple',
            'colour'       => 'info',
            'image'        => null,
            'action_url'   => $this->releaseNotesUrl ?? url('/admin/system-update'),
            'action_label' => 'View update',
            'version'      => $this->version,
        ];
    }
}
