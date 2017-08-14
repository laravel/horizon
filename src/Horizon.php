<?php

namespace Laravel\Horizon;

use Closure;
use Laravel\Horizon\Contracts\InvalidAuthenticationMethod;
use Laravel\Horizon\Http\Middleware\Authenticate;

class Horizon
{
    /**
     * The callback or middleware that should be used to authenticate Horizon users.
     *
     * @var \Closure|string
     */
    public static $authUsing;

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
     * Determine if the given request can access the Horizon dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     * @throws InvalidAuthenticationMethod
     */
    public static function check($request)
    {
        if (static::usesMiddlewareAuth()) {
            throw new InvalidAuthenticationMethod;
        }

        return (static::$authUsing ?: function () {
            return app()->environment('local');
        })($request);
    }

    /**
     * Set the callback that should be used to authenticate Horizon users.
     *
     * @param  \Closure|string  $callback
     * @return static
     */
    public static function auth($callback)
    {
        static::$authUsing = $callback;

        return new static;
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

    /**
     * Retrieve the middleware to be used for authentication.
     *
     * @return string
     */
    public static function middleware()
    {
        return static::usesMiddlewareAuth() ? static::$authUsing : Authenticate::class;
    }

    /**
     * Determine whether the user has specified a middleware for authentication.
     *
     * @return bool
     */
    protected static function usesMiddlewareAuth()
    {
        return is_string(static::$authUsing);
    }
}
