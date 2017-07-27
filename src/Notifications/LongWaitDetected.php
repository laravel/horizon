<?php

namespace Laravel\Horizon\Notifications;

use Laravel\Horizon\Horizon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Messages\NexmoMessage;

class LongWaitDetected extends Notification
{
    use Queueable;

    /**
     * The queue connection name.
     *
     * @var string
     */
    public $connection;

    /**
     * The queue name.
     *
     * @var string
     */
    public $queue;

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
        $this->queue = $queue;
        $this->seconds = $seconds;
        $this->connection = $connection;
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
        ]);
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
                    ->image('https://laravel.com/assets/img/horizon-48px.png')
                    ->error()
                    ->content('Oh no! Something needs your attention.')
                    ->attachment(function ($attachment) {
                        $attachment->title('Long Wait Detected')
                                   ->content(sprintf(
                                        '[%s] The "%s" queue on the "%s" connection has a wait time of %s seconds.',
                                       config('app.name'), $this->queue, $this->connection, $this->seconds
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
            '[%s] The "%s" queue on the "%s" connection has a wait time of %s seconds.',
            config('app.name'), $this->queue, $this->connection, $this->seconds
        ));
    }

    /**
     * The unique signature of the notification.
     *
     * @return string
     */
    public function signature()
    {
        return md5($this->connection.$this->queue);
    }
}
