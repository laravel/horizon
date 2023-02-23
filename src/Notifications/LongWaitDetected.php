<?php

namespace Laravel\Horizon\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\NexmoMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Laravel\Horizon\Horizon;

class LongWaitDetected extends Notification
{
    use Queueable;

    /**
     * The queue connection name.
     *
     * @var string
     */
    public $longWaitConnection;

    /**
     * The queue name.
     *
     * @var string
     */
    public $longWaitQueue;

    /**
     * The wait time in seconds.
     *
     * @var int
     */
    public $seconds;

    /**
     * Create a new notification instance.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  int  $seconds
     * @return void
     */
    public function __construct($connection, $queue, $seconds)
    {
        $this->longWaitQueue = $queue;
        $this->seconds = $seconds;
        $this->longWaitConnection = $connection;
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
                 'The "%s" queue on the "%s" connection has a wait time of %s seconds.',
                $this->longWaitQueue, $this->longWaitConnection, $this->seconds
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
        return (new SlackMessage) // @phpstan-ignore-line
                    ->from('Laravel Horizon')
                    ->to(Horizon::$slackChannel)
                    ->image('https://laravel.com/assets/img/horizon-48px.png')
                    ->error()
                    ->content('Oh no! Something needs your attention.')
                    ->attachment(function ($attachment) {
                        $attachment->title('Long Wait Detected')
                                   ->content(sprintf(
                                        '[%s] The "%s" queue on the "%s" connection has a wait time of %s seconds.',
                                       config('app.name'), $this->longWaitQueue, $this->longWaitConnection, $this->seconds
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
        return (new NexmoMessage)->content(sprintf( // @phpstan-ignore-line
            '[%s] The "%s" queue on the "%s" connection has a wait time of %s seconds.',
            config('app.name'), $this->longWaitQueue, $this->longWaitConnection, $this->seconds
        ));
    }

    /**
     * The unique signature of the notification.
     *
     * @return string
     */
    public function signature()
    {
        return md5($this->longWaitConnection.$this->longWaitQueue);
    }
}
