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
    public $delay = 0;

    /**
     * The maximum amount of RAM the worker may consume.
     *
     * @var int
     */
    public $memory = 128;

    /**
     * The maximum number of seconds a child worker may run.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * The number of seconds to wait in between polling the queue.
     *
     * @var int
     */
    public $sleep = 3;

    /**
     * The maximum amount of times a job may be attempted.
     *
     * @var int
     */
    public $maxTries = 0;

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
