<?php

namespace Laravel\Horizon;

class SupervisorOptions
{
    /**
     * The name of the supervisor.
     *
     * @var string
     */
    public $name;

    /**
     * The name of the workers.
     *
     * @var string
     */
    public $workersName;

    /**
     * The queue connection that should be utilized.
     *
     * @var string
     */
    public $connection;

    /**
     * The queue that should be utilized.
     *
     * @var string
     */
    public $queue;

    /**
     * Indicates the balancing strategy the supervisor should use.
     *
     * @var bool
     */
    public $balance = 'off';

    /**
     * The maximum number of total processes to start when auto-scaling.
     *
     * @var int
     */
    public $maxProcesses = 1;

    /**
     * The minimum number of processes to assign per working when auto-scaling.
     *
     * @var int
     */
    public $minProcesses = 1;

    /**
     * The parent process identifier.
     *
     * @var int
     */
    public $parentId = 0;

    /**
     * The process priority.
     *
     * @var int
     */
    public $nice = 0;

    /**
     * The working directories that new workers should be started from.
     *
     * @var string
     */
    public $directory;

    /**
     * The number of seconds to wait in between auto-scaling attempts.
     *
     * @var int
     */
    public $balanceCooldown = 3;

    /**
     * The maximum number of processes to increase or decrease per one scaling.
     *
     * @var int
     */
    public $balanceMaxShift = 1;

    /**
     * The number of seconds to wait before retrying a job that encountered an uncaught exception.
     *
     * @var string
     */
    public $backoff;

    /**
     * The maximum number of jobs to run.
     *
     * @var int
     */
    public $maxJobs;

    /**
     * The maximum number of seconds a worker may live.
     *
     * @var int
     */
    public $maxTime;

    /**
     * The maximum amount of RAM the worker may consume.
     *
     * @var int
     */
    public $memory;

    /**
     * The maximum number of seconds a child worker may run.
     *
     * @var int
     */
    public $timeout;

    /**
     * The number of seconds to wait in between polling the queue.
     *
     * @var int
     */
    public $sleep;

    /**
     * The maximum amount of times a job may be attempted.
     *
     * @var int
     */
    public $maxTries;

    /**
     * Indicates if the worker should run in maintenance mode.
     *
     * @var bool
     */
    public $force;

    /**
     * Create a new worker options instance.
     *
     * @param  string  $name
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $workersName
     * @param  string  $balance
     * @param  int  $backoff
     * @param  int  $maxTime
     * @param  int  $maxJobs
     * @param  int  $maxProcesses
     * @param  int  $minProcesses
     * @param  int  $memory
     * @param  int  $timeout
     * @param  int  $sleep
     * @param  int  $maxTries
     * @param  bool  $force
     * @param  int  $nice
     * @param  int  $balanceCooldown
     * @param  int  $balanceMaxShift
     * @param  int  $parentId
     */
    public function __construct($name,
                                $connection,
                                $queue = null,
                                $workersName = 'default',
                                $balance = 'off',
                                $backoff = 0,
                                $maxTime = 0,
                                $maxJobs = 0,
                                $maxProcesses = 1,
                                $minProcesses = 1,
                                $memory = 128,
                                $timeout = 60,
                                $sleep = 3,
                                $maxTries = 0,
                                $force = false,
                                $nice = 0,
                                $balanceCooldown = 3,
                                $balanceMaxShift = 1,
                                $parentId = 0)
    {
        $this->name = $name;
        $this->connection = $connection;
        $this->queue = $queue ?: config('queue.connections.'.$connection.'.queue');
        $this->workersName = $workersName;
        $this->balance = $balance;
        $this->backoff = $backoff;
        $this->maxTime = $maxTime;
        $this->maxJobs = $maxJobs;
        $this->maxProcesses = $maxProcesses;
        $this->minProcesses = $minProcesses;
        $this->memory = $memory;
        $this->timeout = $timeout;
        $this->sleep = $sleep;
        $this->maxTries = $maxTries;
        $this->force = $force;
        $this->nice = $nice;
        $this->balanceCooldown = $balanceCooldown;
        $this->balanceMaxShift = $balanceMaxShift;
        $this->parentId = 0;
    }

    /**
     * Create a fresh options instance with the given queue.
     *
     * @param  string  $queue
     * @return static
     */
    public function withQueue($queue)
    {
        return tap(clone $this, function ($options) use ($queue) {
            $options->queue = $queue;
        });
    }

    /**
     * Determine if a balancing strategy should be used.
     *
     * @return bool
     */
    public function balancing()
    {
        return in_array($this->balance, ['simple', 'auto']);
    }

    /**
     * Determine if auto-scaling should be applied.
     *
     * @return bool
     */
    public function autoScaling()
    {
        return $this->balance === 'auto';
    }

    /**
     * Get the command-line representation of the options for a supervisor.
     *
     * @return string
     */
    public function toSupervisorCommand()
    {
        return SupervisorCommandString::fromOptions($this);
    }

    /**
     * Get the command-line representation of the options for a worker.
     *
     * @return string
     */
    public function toWorkerCommand()
    {
        return WorkerCommandString::fromOptions($this);
    }

    /**
     * Convert the options to a JSON string.
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Convert the options to a raw array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'balance' => $this->balance,
            'connection' => $this->connection,
            'queue' => $this->queue,
            'backoff' => $this->backoff,
            'force' => $this->force,
            'maxProcesses' => $this->maxProcesses,
            'minProcesses' => $this->minProcesses,
            'maxTries' => $this->maxTries,
            'maxTime' => $this->maxTime,
            'maxJobs' => $this->maxJobs,
            'memory' => $this->memory,
            'nice' => $this->nice,
            'name' => $this->name,
            'workersName' => $this->workersName,
            'sleep' => $this->sleep,
            'timeout' => $this->timeout,
            'balanceCooldown' => $this->balanceCooldown,
            'balanceMaxShift' => $this->balanceMaxShift,
        ];
    }

    /**
     * Create a new options instance from the given array.
     *
     * @param  array  $array
     * @return static
     */
    public static function fromArray(array $array)
    {
        return tap(new static($array['name'], $array['connection']), function ($options) use ($array) {
            foreach ($array as $key => $value) {
                $options->{$key} = $value;
            }
        });
    }
}
