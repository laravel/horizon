<?php

namespace Laravel\Horizon\Listeners;

use Illuminate\Support\Facades\Notification;
use Laravel\Horizon\Events\LongWaitDetected;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Lock;

class SendNotification
{
    /**
     * Handle the event.
     *
     * @param  \Laravel\Horizon\Events\LongWaitDetected  $event
     * @return void
     */
    public function handle(LongWaitDetected $event)
    {
        $notification = $event->toNotification();
        $lockTime = config(sprintf('horizon.notification_lock_times.%s:%s', $event->connection, $event->queue), 300);

        if (! app(Lock::class)->get('notification:'.$notification->signature(), $lockTime)) {
            return;
        }

        Notification::route('slack', Horizon::$slackWebhookUrl)
                    ->route('nexmo', Horizon::$smsNumber)
                    ->route('mail', Horizon::$email)
                    ->notify($notification);
    }
}
