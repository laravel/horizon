<?php

namespace Laravel\Horizon\Listeners;

use Illuminate\Support\Facades\Notification;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Lock;

class SendNotification
{
    /**
     * Handle the event.
     *
     * @param  mixed  $event
     * @return void
     */
    public function handle($event)
    {
        $notification = $event->toNotification();

        if (! app(Lock::class)->get('notification:'.$notification->signature(), 300)) {
            return;
        }

        Notification::route('slack', Horizon::$slackWebhookUrl)
                    ->route('nexmo', Horizon::$smsNumber)
                    ->route('mail', Horizon::$email)
                    ->notify($notification);
    }
}
