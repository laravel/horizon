<?php

namespace Laravel\Horizon\Factories;

use Laravel\Horizon\BackgroundProcess;
use Symfony\Component\Process\Process;

class ProcessFactory
{
    /**
     * Create a standard Process
     *
     * @param array          $command The command to run and its arguments listed as separate entries
     * @param string|null    $cwd     The working directory or null to use the working dir of the current PHP process
     * @param array|null     $env     The environment variables or null to use the same environment as the current PHP process
     * @param mixed|null     $input   The input as stream resource, scalar or \Traversable, or null for no input
     * @param int|float|null $timeout The timeout in seconds or null to disable
     *
     * @return Process
     */
    public static function createProcess(
        $command,
        string $cwd = null,
        array $env = null,
        $input = null,
        ?float $timeout = 60
    ): Process {
        return static::make(Process::class, $command, $cwd, $env, $input, $timeout);
    }

    /**
     * Create a Background Process
     *
     * @param array          $command The command to run and its arguments listed as separate entries
     * @param string|null    $cwd     The working directory or null to use the working dir of the current PHP process
     * @param array|null     $env     The environment variables or null to use the same environment as the current PHP process
     * @param mixed|null     $input   The input as stream resource, scalar or \Traversable, or null for no input
     * @param int|float|null $timeout The timeout in seconds or null to disable
     *
     * @return Process
     */
    public static function createBackgroundProcess(
        $command,
        string $cwd = null,
        array $env = null,
        $input = null,
        ?float $timeout = 60
    ): Process {
        return static::make(BackgroundProcess::class, $command, $cwd, $env, $input, $timeout);
    }

    /**
     * Create a process depending on the type.
     *
     * @param string         $class   The process class to be created.
     * @param string         $command The command line to pass to the shell of the OS
     * @param string|null    $cwd     The working directory or null to use the working dir of the current PHP process
     * @param array|null     $env     The environment variables or null to use the same environment as the current PHP process
     * @param mixed|null     $input   The input as stream resource, scalar or \Traversable, or null for no input
     * @param int|float|null $timeout The timeout in seconds or null to disable
     *
     * @return Process
     */
    protected static function make(
        $class,
        $command,
        string $cwd = null,
        array $env = null,
        $input = null,
        ?float $timeout = 60
    ): Process {
        if (method_exists($class, 'fromShellCommandline')) {
            return $class::fromShellCommandline($command, $cwd, $env, $input, $timeout);
        }

        return new $class($command, $cwd, $env, $input, $timeout);
    }
}
