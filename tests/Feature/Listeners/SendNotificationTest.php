<?php

namespace Laravel\Horizon\Tests\Feature\Listeners;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Notification;
use Laravel\Horizon\Events\LongWaitDetected;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Lock;
use Laravel\Horizon\Notifications\LongWaitDetected as LongWaitDetectedNotification;
use Laravel\Horizon\Tests\IntegrationTest;
use Mockery as m;

class SendNotificationTest extends IntegrationTest
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('horizon.notification_lock_times', [
            'redis:foo' => 444,
            'redis:baz' => 555,
        ]);
    }

    public function test_send_notification_after_lock_time_as_default_when_config_value_not_found(): void
    {
        Notification::fake();
        Horizon::$email = 'foo@baz.bar';

        $lockMock = m::mock(Lock::class);
        $lockMock->shouldReceive('get')
            ->once()
            ->with(m::any(), 300)
            ->andReturn(true);

        $this->instance(Lock::class, $lockMock);

        $this->app->make(Dispatcher::class)->dispatch(new LongWaitDetected(
            'redis', 'default', 600
        ));

        Notification::assertSentTimes(LongWaitDetectedNotification::class, 1);
    }

    public function test_send_notification_after_lock_time_set_in_config(): void
    {
        Notification::fake();
        Horizon::$slackWebhookUrl = 'https://slack.com';

        $lockMock = m::mock(Lock::class);
        $lockMock->shouldReceive('get')
            ->once()
            ->with(m::any(), 444)
            ->andReturn(true);

        $this->instance(Lock::class, $lockMock);

        $this->app->make(Dispatcher::class)->dispatch(new LongWaitDetected(
            'redis', 'foo', 600
        ));

        Notification::assertSentTimes(LongWaitDetectedNotification::class, 1);
    }

    public function test_do_not_send_notification_if_lock_not_acquired(): void
    {
        Notification::fake();
        Horizon::$slackWebhookUrl = 'https://slack.com';

        $lockMock = m::mock(Lock::class);
        $lockMock->shouldReceive('get')
            ->once()
            ->with(m::any(), 555)
            ->andReturn(false);

        $this->instance(Lock::class, $lockMock);

        $this->app->make(Dispatcher::class)->dispatch(new LongWaitDetected(
            'redis', 'baz', 600
        ));

        Notification::assertSentTimes(LongWaitDetectedNotification::class, 0);
    }
}
