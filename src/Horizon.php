<?php

namespace Laravel\Horizon;

use Closure;

class Horizon
{
    /**
     * The callback that should be used to authenticate Horizon users.
     *
     * @var \Closure
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
     */
    public static function check($request)
    {
        return (static::$authUsing ?: function () {
            return app()->environment('local');
        })($request);
    }

    /**
     * Set the callback that should be used to authenticate Horizon users.
     *
     * @param  \Closure  $callback
     * @return static
     */
    public static function auth(Closure $callback)
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

        foreach (static::$databases as $database) {
            static::{"configure{$database}Database"}($config);
        }
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
     * Configure the database that holds a copy of the queue jobs.
     *
     * @param  array  $config
     * @return void
     */
    protected static function configureJobsDatabase(array $config)
    {
        config(['database.redis.horizon-jobs' => array_merge($config, [
            'database' => 9,
        ])]);
    }

    /**
     * Configure the database that stores meta information on supervisors.
     *
     * @param  array  $config
     * @return void
     */
    protected static function configureSupervisorsDatabase(array $config)
    {
        config(['database.redis.horizon-supervisors' => array_merge($config, [
            'database' => 10,
        ])]);
    }

    /**
     * Configure the database for the supervisor command queues.
     *
     * @param  array  $config
     * @return void
     */
    protected static function configureCommandQueueDatabase(array $config)
    {
        config(['database.redis.horizon-command-queue' => array_merge($config, [
            'database' => 11,
        ])]);
    }

    /**
     * Configure the database that stores tag to job mappings.
     *
     * @param  array  $config
     * @return void
     */
    protected static function configureTagsDatabase(array $config)
    {
        config(['database.redis.horizon-tags' => array_merge($config, [
            'database' => 12,
        ])]);
    }

    /**
     * Configure the database that stores tag to job mappings.
     *
     * @param  array  $config
     * @return void
     */
    protected static function configureMetricsDatabase(array $config)
    {
        config(['database.redis.horizon-metrics' => array_merge($config, [
            'database' => 13,
        ])]);
    }

    /**
     * Configure the database that stores locks.
     *
     * @param  array  $config
     * @return void
     */
    protected static function configureLocksDatabase(array $config)
    {
        config(['database.redis.horizon-locks' => array_merge($config, [
            'database' => 14,
        ])]);
    }

    /**
     * Configure the database that stores locks.
     *
     * @param  array  $config
     * @return void
     */
    protected static function configureProcessesDatabase(array $config)
    {
        config(['database.redis.horizon-processes' => array_merge($config, [
            'database' => 15,
        ])]);
    }
}
