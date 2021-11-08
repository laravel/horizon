<?php

namespace Laravel\Horizon\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\NexmoMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Laravel\Horizon\Horizon;

class Test extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return array_filter([
            Horizon::$slackWebhookUrl ? 'slack' : null,
            Horizon::$smsNumber ? 'nexmo' : null,
            Horizon::$email ? 'mail' : null,
        ]);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->error()
            ->subject(config('app.name').': Horizon test notification')
            ->greeting('This is a horizon test.')
            ->line('This is a test notification sent with php artisan horizon:test-notification command.');
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\SlackMessage
     */
    public function toSlack($notifiable)
    {
        return (new SlackMessage)
                    ->from('Laravel Horizon')
                    ->to(Horizon::$slackChannel)
                    ->image('https://laravel.com/assets/img/horizon-48px.png')
                    ->error()
                    ->content('This is a horizon test.')
                    ->attachment(function ($attachment) {
                        $attachment->title('Test')
                                   ->content(sprintf(
                                        '[%s] This is a test notification sent with php artisan horizon:test-notification command.',
                                       config('app.name')
                                   ));
                    });
    }

    /**
     * Get the Nexmo / SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\NexmoMessage
     */
    public function toNexmo($notifiable)
    {
        return (new NexmoMessage)->content(sprintf(
            '[%s] This is a test notification sent with php artisan horizon:test-notification command.',
            config('app.name')
        ));
    }
}
