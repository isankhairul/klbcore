<?php namespace Klb\Core\Queue;

use Closure;
use Exception;
use Klb\Core\Task;
use Phalcon\Mvc\Model\Transaction\Failed;

/**
 * Class WorkerOptions
 *
 * @package Klb\Core\Queue
 */
abstract class WorkerOptions
{
    /**
     * METHOD
     */
    const TYPE_DAEMON = 'daemon';
    /**
     * The number of seconds before a released job will be available.
     *
     * @var int
     */
    public $delay;

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
    public $force = false;
    /**
     * @var callable
     */
    public $callback;
    /**
     * @var Task
     */
    public $task;
    /**
     * Indicates if the worker should stop when queue is empty.
     *
     * @var bool
     */
    public $stopWhenEmpty;

    /**
     * Create a new worker options instance.
     *
     * @param int  $delay
     * @param int  $memory
     * @param int  $timeout
     * @param int  $sleep
     * @param int  $maxTries
     * @param bool $force
     * @param bool $stopWhenEmpty
     *
     * @return void
     */
    public function __construct( $delay = 0, $memory = 128, $timeout = 60, $sleep = 3, $maxTries = 1, $force = false, $stopWhenEmpty = false )
    {
        $this->delay = $delay;
        $this->sleep = $sleep;
        $this->force = $force;
        $this->memory = $memory;
        $this->timeout = $timeout;
        $this->maxTries = $maxTries;
        $this->stopWhenEmpty = $stopWhenEmpty;
    }

    /**
     * Get tube name
     *
     * @return string
     */
    public static function getTubeName()
    {
        return 'default';
    }

    /**
     * @param ProcessJob $job
     *
     * @return Closure
     * @throws Exception
     * @throws Failed
     */
    abstract public function fire( ProcessJob $job );

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE_DAEMON;
    }
}
