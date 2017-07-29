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
        'Laravel\Horizon\Events\JobPushed' => [
            'Laravel\Horizon\Listeners\StoreJob',
            'Laravel\Horizon\Listeners\StoreMonitoredTags',
        ],

        'Laravel\Horizon\Events\JobReserved' => [
            'Laravel\Horizon\Listeners\MarkJobAsReserved',
            'Laravel\Horizon\Listeners\StartTimingJob',
        ],

        'Laravel\Horizon\Events\JobReleased' => [
            'Laravel\Horizon\Listeners\MarkJobAsReleased',
        ],

        'Laravel\Horizon\Events\JobDeleted' => [
            'Laravel\Horizon\Listeners\MarkJobAsComplete',
            'Laravel\Horizon\Listeners\UpdateJobMetrics',
        ],

        'Laravel\Horizon\Events\JobsMigrated' => [
            'Laravel\Horizon\Listeners\MarkJobsAsMigrated',
        ],

        'Illuminate\Queue\Events\JobFailed' => [
            'Laravel\Horizon\Listeners\MarshalFailedEvent',
        ],

        'Laravel\Horizon\Events\JobFailed' => [
            'Laravel\Horizon\Listeners\MarkJobAsFailed',
            'Laravel\Horizon\Listeners\StoreTagsForFailedJob',
        ],

        'Laravel\Horizon\Events\MasterSupervisorLooped' => [
            'Laravel\Horizon\Listeners\TrimRecentJobs',
            'Laravel\Horizon\Listeners\TrimFailedJobs',
            'Laravel\Horizon\Listeners\ExpireSupervisors',
            'Laravel\Horizon\Listeners\MonitorMasterSupervisorMemory',
        ],

        'Laravel\Horizon\Events\SupervisorLooped' => [
            'Laravel\Horizon\Listeners\PruneTerminatingProcesses',
            'Laravel\Horizon\Listeners\MonitorSupervisorMemory',
            'Laravel\Horizon\Listeners\MonitorWaitTimes',
        ],

        'Laravel\Horizon\Events\WorkerProcessRestarting' => [
            //
        ],

        'Laravel\Horizon\Events\SupervisorProcessRestarting' => [
            'Laravel\Horizon\Listeners\LogProcessRestart',
        ],

        'Laravel\Horizon\Events\LongWaitDetected' => [
            'Laravel\Horizon\Listeners\SendNotification',
        ],
    ];
}
