<?php

namespace Laravel\Horizon;

use Route;
use Exception;

class Horizon
{
    /**
     * The Slack notifications webhook URL.
     *
     * @var string
     */
    public static $slackWebhookUrl;

    /**
     * The Slack notifications channel.
     *
     * @var string
     */
    public static $slackChannel;

    /**
     * The SMS notifications phone number.
     *
     * @var string
     */
    public static $smsNumber;

    /**
     * The email address for notifications.
     *
     * @var string
     */
    public static $email;

    /**
     * The database configuration methods.
     *
     * @var array
     */
    public static $databases = [
        'Jobs', 'Supervisors', 'CommandQueue', 'Tags',
        'Metrics', 'Locks', 'Processes',
    ];

    /**
     * package routes.
     *
     * @return static
     */
    public static function routes()
    {
        Route::group([
            'prefix'     => config('horizon.uri', 'horizon'),
            'middleware' => config('horizon.middleware', 'web'),
        ], function () {
            require __DIR__ . '/../routes/web.php';
        });
    }

    /**
     * Configure the Redis databases that will store Horizon data.
     *
     * @param string $connection
     *
     * @throws Exception
     */
    public static function use($connection)
    {
        if (is_null($config = config("database.redis.{$connection}"))) {
            throw new Exception("Redis connection [{$connection}] has not been configured.");
        }

        config(['database.redis.horizon' => array_merge($config, [
            'options' => ['prefix' => config('horizon.prefix') ?: 'horizon:'],
        ])]);
    }

    /**
     * Specify the email address to which email notifications should be routed.
     *
     * @param string $email
     *
     * @return static
     */
    public static function routeMailNotificationsTo($email)
    {
        static::$email = $email;

        return new static();
    }

    /**
     * Specify the webhook URL and channel to which Slack notifications should be routed.
     *
     * @param string $url
     * @param string $channel
     *
     * @return static
     */
    public static function routeSlackNotificationsTo($url, $channel = null)
    {
        static::$slackWebhookUrl = $url;
        static::$slackChannel    = $channel;

        return new static();
    }

    /**
     * Specify the phone number to which SMS notifications should be routed.
     *
     * @param string $number
     *
     * @return static
     */
    public static function routeSmsNotificationsTo($number)
    {
        static::$smsNumber = $number;

        return new static();
    }
}
