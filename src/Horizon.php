<?php

namespace Laravel\Horizon;

use Closure;
use Exception;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
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
     * @deprecated
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
     * Get the CSS for the Horizon dashboard.
     *
     * @return Illuminate\Contracts\Support\Htmlable
     */
    public static function css()
    {
        if (($light = @file_get_contents(__DIR__.'/../dist/styles.css')) === false) {
            throw new RuntimeException('Unable to load the Horizon dashboard light CSS.');
        }

        if (($dark = @file_get_contents(__DIR__.'/../dist/styles-dark.css')) === false) {
            throw new RuntimeException('Unable to load the Horizon dashboard dark CSS.');
        }

        if (($app = @file_get_contents(__DIR__.'/../dist/app.css')) === false) {
            throw new RuntimeException('Unable to load the Horizon dashboard CSS.');
        }

        return new HtmlString(<<<HTML
            <style data-scheme="light">{$light}</style>
            <style data-scheme="dark">{$dark}</style>
            <style>{$app}</style>
            HTML);
    }

    /**
     * Get the JS for the Horizon dashboard.
     *
     * @return \Illuminate\Contracts\Support\Htmlable
     */
    public static function js()
    {
        if (($js = @file_get_contents(__DIR__.'/../dist/app.js')) === false) {
            throw new RuntimeException('Unable to load the Horizon dashboard JavaScript.');
        }

        $horizon = Js::from(static::scriptVariables());

        return new HtmlString(<<<HTML
            <script type="module">
                window.Horizon = {$horizon};
                {$js}
            </script>
            HTML);
    }

    /**
     * Specifies that Horizon should use the dark theme.
     *
     * @deprecated
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
            'proxy_path' => config('horizon.proxy_path', ''),
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
}
