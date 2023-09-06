<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Facades\Notification;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Listeners\MonitorWaitTimes;
use Laravel\Horizon\Notifications\LongWaitDetected;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\WaitTimeCalculator;
use Mockery;

class WaitTimesNotificationTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_long_wait_notification_is_sent_via_sms()
    {
        Horizon::routeSmsNotificationsTo('999999999');

        $this->generateLongWaitNotification();

        Notification::assertCount(1);
        Notification::assertSentTimes(LongWaitDetected::class, 1);
    }

    public function test_long_wait_notification_is_sent_via_email()
    {
        Horizon::routeMailNotificationsTo('taylor@laravel.com');

        $this->generateLongWaitNotification();

        Notification::assertCount(1);
        Notification::assertSentTimes(LongWaitDetected::class, 1);
    }

    public function test_long_wait_notification_is_sent_via_slack()
    {
        Horizon::routeSlackNotificationsTo('https://blackhole.dev');

        $this->generateLongWaitNotification();

        Notification::assertCount(1);
        Notification::assertSentTimes(LongWaitDetected::class, 1);
    }

    public function test_long_wait_notification_is_sent_via_callback()
    {
        Horizon::routeGenericNotificationsTo(function ($notification, $message) {
            dd('Spicy! 🌶️');
        });

        $this->generateLongWaitNotification();

        Notification::assertCount(1);
        Notification::assertSentTimes(LongWaitDetected::class, 1);
    }

    protected function generateLongWaitNotification()
    {
        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->shouldReceive('calculate')->andReturn([
            'redis:test-queue' => 10,
            'redis:test-queue-2' => 80,
        ]);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $listener = new MonitorWaitTimes(app(MetricsRepository::class));

        $listener->handle();
    }
}
