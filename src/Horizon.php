<?php

namespace Laravel\Horizon;

use Illuminate\Support\Facades\Route;

class Horizon
{
    /**
     * The Slack notifications webhook URL.
     *
     * @var string
     */
    public static $slackWebhookUrl;

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
     * Register the Horizon routes.
     *
     * @param  string|array  $middleware
     * @return void
     */
    public static function routes($middleware = [])
    {
        Route::group([
            'prefix' => 'horizon',
            'namespace' => 'Laravel\Horizon\Http\Controllers',
            'middleware' => array_merge(['web'], array_wrap($middleware)),
        ], function () {
            require __DIR__.'/../routes/web.php';
        });
    }

    /**
     * Configure the Redis databases that will store Horizon data.
     *
     * @param  string  $connection
     * @return void
     */
    public static function use($connection)
    {
        $config = config("database.redis.{$connection}");

        config(['database.redis.horizon' => array_merge($config, [
            'options' => ['prefix' => 'horizon:']
        ])]);
    }

    /**
     * Specify the email address to which email notifications should be routed.
     *
     * @param  string  $email
     * @return static
     */
    public static function routeMailNotificationsTo($email)
    {
        static::$email = $email;

        return new static;
    }

    /**
     * Specify the webhook URL to which Slack notifications should be routed.
     *
     * @param  string  $url
     * @return static
     */
    public static function routeSlackNotificationsTo($url)
    {
        static::$slackWebhookUrl = $url;

        return new static;
    }

    /**
     * Specify the phone number to which SMS notifications should be routed.
     *
     * @param  string  $number
     * @return static
     */
    public static function routeSmsNotificationsTo($number)
    {
        static::$smsNumber = $number;

        return new static;
    }
}
