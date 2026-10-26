<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use App\Services\NotificationPreferences;

/**
 * Skips delivery to users who turned this notification type off in their Profile.
 */
trait RespectsNotificationPreferences
{
    public function shouldSend(object $notifiable, string $channel): bool
    {
        return ! $notifiable instanceof User || NotificationPreferences::wants($notifiable, static::class);
    }
}
