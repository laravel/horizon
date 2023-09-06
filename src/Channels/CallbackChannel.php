<?php

namespace Laravel\Horizon\Channels;

use Illuminate\Notifications\Notification;
use Laravel\Horizon\Horizon;

class CallbackChannel
{
    /**
     * Send the notification via the callback.
     *
     * @param  $notifiable
     * @param  Notification  $notification
     * @return true
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toCallback($notifiable);

        (Horizon::$genericNotificationCallback)($notification, $message);

        return true;
    }
}
