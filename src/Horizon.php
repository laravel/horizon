<?php

namespace Laravel\Horizon;

use Closure;
use Exception;
use Illuminate\Support\Facades\File;
use RuntimeException;

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
     * Indicates if Horizon should use the dark theme.
     *
     * @var bool
     */
    public static $useDarkTheme = false;

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
     * The translations that should be made available on the Horizon JavaScript object.
     *
     * @var array<string, string>
     */
    public static $translations = [];

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
     *
     * @throws \Exception
     */
    public static function use($connection)
    {
        if (! is_null($config = config("database.redis.clusters.{$connection}.0"))) {
            config(["database.redis.{$connection}" => $config]);
        } elseif (is_null($config) && is_null($config = config("database.redis.{$connection}"))) {
            throw new Exception("Redis connection [{$connection}] has not been configured.");
        }

        $config['options']['prefix'] = config('horizon.prefix') ?: 'horizon:';

        config(['database.redis.horizon' => $config]);
    }

    /**
     * Specifies that Horizon should use the dark theme.
     *
     * @return static
     */
    public static function night()
    {
        static::$useDarkTheme = true;

        return new static;
    }

    /**
     * Get the default JavaScript variables for Horizon.
     *
     * @return array
     */
    public static function scriptVariables()
    {
        return [
            'path' => config('horizon.path'),

            'translations' => static::allTranslations(),
        ];
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
     * Specify the webhook URL and channel to which Slack notifications should be routed.
     *
     * @param  string  $url
     * @param  string  $channel
     * @return static
     */
    public static function routeSlackNotificationsTo($url, $channel = null)
    {
        static::$slackWebhookUrl = $url;
        static::$slackChannel = $channel;

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
     * Determine if Horizon's published assets are up-to-date.
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public static function assetsAreCurrent()
    {
        $publishedPath = public_path('vendor/horizon/mix-manifest.json');

        if (! File::exists($publishedPath)) {
            throw new RuntimeException('Horizon assets are not published. Please run: php artisan horizon:publish');
        }

        return File::get($publishedPath) === File::get(__DIR__.'/../public/mix-manifest.json');
    }

    /**
     * Register the given translations with Horizon.
     *
     * @param  array<string, string>|string  $translations
     * @return static
     */
    public static function translations($translations)
    {
        if (is_string($translations)) {
            if (! is_readable($translations)) {
                return new static();
            }

            $translations = json_decode(file_get_contents($translations), true);
        }

        static::$translations = array_merge(static::$translations, $translations);

        return new static();
    }

    /**
     * Get all of the additional translations that should be loaded.
     *
     * @return array<string, string>
     */
    public static function allTranslations()
    {
        return static::$translations;
    }
}
