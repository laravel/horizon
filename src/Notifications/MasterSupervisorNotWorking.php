<?php

namespace Laravel\Horizon\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Laravel\Horizon\Horizon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\NexmoMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Laravel\Horizon\MasterSupervisor;


class MasterSupervisorNotWorking extends Notification
{
    use Queueable;


    /**
     * @var MasterSupervisor
     */
    private $master;


    /**
     * Create a new SupervisorHeartBeat instance.
     * SupervisorHeartBeat constructor.
     * @param MasterSupervisor $master
     */
    public function __construct(MasterSupervisor $master)
    {
        $this->master = $master;
    }

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
            ->subject(config('app.name').': Long Queue Wait Detected')
            ->greeting('Oh no! Something needs your attention.')
            ->line(sprintf(
                 '[%s] The supervisor for horizon is actually not working',
                config('app.name')
            ));
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
                    ->content('Oh no! Something needs your attention.')
                    ->attachment(function ($attachment) {
                        $attachment->title('Supervisor inactive')
                                   ->content(sprintf(
                                        '[%s] The supervisor for horizon is actually not working',
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
            '[%s] The supervisor for horizon is actually not working',
            config('app.name')
        ));
    }
    /**
     * The unique signature of the notification.
     *
     * @return string
     */
    public function signature()
    {
        return md5($this->master);
    }
}
