<?php

namespace Laravel\Horizon;

use Illuminate\Queue\WorkerOptions;

class SupervisorOptions extends WorkerOptions
{
    /**
     * The name of the supervisor.
     *
     * @var string
     */
    public $name;

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
     * Create a new worker options instance.
     *
     * @param  string  $name
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $balance
     * @param  int  $delay
     * @param  int  $maxProcesses
     * @param  int  $minProcesses
     * @param  int  $memory
     * @param  int  $timeout
     * @param  int  $sleep
     * @param  int  $maxTries
     * @param  bool  $force
     * @param  int  $nice
     */
    public function __construct($name, $connection, $queue = null, $balance = 'off',
                                $delay = 0, $maxProcesses = 1, $minProcesses = 1, $memory = 128,
                                $timeout = 60, $sleep = 3, $maxTries = 0, $force = false, $nice = 0)
    {
        $this->name = $name;
        $this->nice = $nice;
        $this->balance = $balance;
        $this->connection = $connection;
        $this->maxProcesses = $maxProcesses;
        $this->minProcesses = $minProcesses;
        $this->queue = $queue ?: config('queue.connections.'.$connection.'.queue');

        parent::__construct($delay, $memory, $timeout, $sleep, $maxTries, $force);
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
            'delay' => $this->delay,
            'force' => $this->force,
            'maxProcesses' => $this->maxProcesses,
            'minProcesses' => $this->minProcesses,
            'maxTries' => $this->maxTries,
            'memory' => $this->memory,
            'nice' => $this->nice,
            'name' => $this->name,
            'sleep' => $this->sleep,
            'timeout' => $this->timeout,
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
