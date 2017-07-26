<?php

namespace Laravel\Horizon;

trait EventMap
{
    /**
     * All of the Horizon event / listener mappings.
     *
     * @var array
     */
    protected $events = [
        \Laravel\Horizon\Events\JobPushed::class => [
            \Laravel\Horizon\Listeners\StoreJob::class,
            \Laravel\Horizon\Listeners\StoreMonitoredTags::class,
        ],

        \Laravel\Horizon\Events\JobReserved::class => [
            \Laravel\Horizon\Listeners\MarkJobAsReserved::class,
            \Laravel\Horizon\Listeners\StartTimingJob::class,
        ],

        \Laravel\Horizon\Events\JobReleased::class => [
            \Laravel\Horizon\Listeners\MarkJobAsReleased::class,
        ],

        \Laravel\Horizon\Events\JobDeleted::class => [
            \Laravel\Horizon\Listeners\MarkJobAsComplete::class,
            \Laravel\Horizon\Listeners\UpdateJobMetrics::class,
        ],

        \Laravel\Horizon\Events\JobsMigrated::class => [
            \Laravel\Horizon\Listeners\MarkJobsAsMigrated::class,
        ],

        'Illuminate\Queue\Events\JobFailed' => [
            \Laravel\Horizon\Listeners\MarshalFailedEvent::class,
        ],

        \Laravel\Horizon\Events\JobFailed::class => [
            \Laravel\Horizon\Listeners\MarkJobAsFailed::class,
            \Laravel\Horizon\Listeners\StoreTagsForFailedJob::class,
        ],

        \Laravel\Horizon\Events\MasterSupervisorLooped::class => [
            \Laravel\Horizon\Listeners\TrimRecentJobs::class,
            \Laravel\Horizon\Listeners\TrimFailedJobs::class,
            \Laravel\Horizon\Listeners\MonitorMasterSupervisorMemory::class,
        ],

        \Laravel\Horizon\Events\SupervisorLooped::class => [
            \Laravel\Horizon\Listeners\PruneTerminatingProcesses::class,
            \Laravel\Horizon\Listeners\MonitorSupervisorMemory::class,
            \Laravel\Horizon\Listeners\MonitorWaitTimes::class,
        ],

        \Laravel\Horizon\Events\WorkerProcessRestarting::class => [
            //
        ],

        \Laravel\Horizon\Events\SupervisorProcessRestarting::class => [
            \Laravel\Horizon\Listeners\LogProcessRestart::class,
        ],

        \Laravel\Horizon\Events\LongWaitDetected::class => [
            \Laravel\Horizon\Listeners\SendNotification::class
        ],
    ];
}
