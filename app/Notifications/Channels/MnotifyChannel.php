<?php

namespace App\Notifications\Channels;

use App\Support\MnotifyClient;
use Illuminate\Notifications\Notification;

class MnotifyChannel
{
    public function __construct(private MnotifyClient $mnotify) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toMnotify')) {
            return;
        }

        $message = $notification->toMnotify($notifiable);
        $recipient = $notifiable->routeNotificationFor(self::class, $notification);

        if (! filled($recipient) || ! filled($message)) {
            return;
        }

        $this->mnotify->send([(string) $recipient], (string) $message);
    }
}
