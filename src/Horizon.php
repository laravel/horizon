<?php

namespace Laravel\Horizon;

use Closure;
use Exception;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use RuntimeException;

class Horizon
{
    /**
     * The callback that should be used to authenticate Horizon users.
     */
    public static ?Closure $authUsing = null;

    /**
     * The Slack notifications webhook URL.
     */
    public static ?string $slackWebhookUrl = null;

    /**
     * The Slack notifications channel.
     */
    public static ?string $slackChannel = null;

    /**
     * The SMS notifications phone number.
     */
    public static ?string $smsNumber = null;

    /**
     * The email address for notifications.
     */
    public static ?string $email = null;

    /**
     * The database configuration methods.
     *
     * @var array<int, string>
     */
    public static array $databases = [
        'Jobs', 'Supervisors', 'CommandQueue', 'Tags',
        'Metrics', 'Locks', 'Processes',
    ];

    /**
     * Get the Horizon application name.
     */
    public static function title(): string
    {
        return with(config('app.name'), fn ($name) => 'Horizon'.($name ? ' - '.$name : ''));
    }

    /**
     * Determine if the given request can access the Horizon dashboard.
     */
    public static function check(Request $request): bool
    {
        return (static::$authUsing ?: function () {
            return app()->environment('local');
        })($request);
    }

    /**
     * Set the callback that should be used to authenticate Horizon users.
     *
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
     * @throws \Exception
     */
    public static function use(string $connection): void
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
     * @throws \RuntimeException
     */
    public static function css(): Htmlable
    {
        if (($css = @file_get_contents(__DIR__.'/../dist/app.css')) === false) {
            throw new RuntimeException('Unable to load the Horizon dashboard CSS.');
        }

        return new HtmlString(<<<HTML
            <style>{$css}</style>
            HTML);
    }

    /**
     * Get the JS for the Horizon dashboard.
     *
     * @throws \RuntimeException
     */
    public static function js(): Htmlable
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
     * Get the default JavaScript variables for Horizon.
     */
    public static function scriptVariables(): array
    {
        return [
            'appName' => static::title(),
            'isDownForMaintenance' => app()->isDownForMaintenance(),
            'path' => config('horizon.path'),
            'proxyPath' => config('horizon.proxy_path', ''),
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
     * @param  string|null  $channel
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
