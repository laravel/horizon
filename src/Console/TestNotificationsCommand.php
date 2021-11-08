<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Notifications\Test;

class TestNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:test-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test notifications to all methods that have been setup in horizon provider';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        Notification::route('slack', Horizon::$slackWebhookUrl)
            ->route('nexmo', Horizon::$smsNumber)
            ->route('mail', Horizon::$email)
            ->notify(
                new Test()
            );
    }
}
