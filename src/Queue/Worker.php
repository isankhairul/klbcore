<?php namespace Klb\Core\Queue;

use Klb\Core\Event\FailingJob;
use Klb\Core\Queue\Traits\DetectsLostConnections;
use Exception;
use Klb\Core\Event\Aware\WorkerInterface;
use Klb\Core\Event\WorkerListener;
use Phalcon\Di\InjectionAwareInterface;
use Throwable;

/**
 * Class Worker
 * @package Klb\Core\Queue
 */
class Worker implements InjectionAwareInterface
{
    use DetectsLostConnections;
    /**
     * The cache repository implementation.
     *
     * @var \Phalcon\Cache\Backend
     */
    protected $cache;
    /**
     * Indicates if the worker should exit.
     *
     * @var bool
     */
    public $shouldQuit = false;
    /**
     * @var \Phalcon\DiInterface
     */
    protected $di;
    /**
     * @var \Phalcon\Queue\Beanstalk
     */
    protected $instanceQueue;
    /**
     * @var WorkerInterface
     */
    protected $workerEvent;
    /**
     * Indicates if the worker is paused.
     *
     * @var bool
     */
    public $paused = false;
    /**
     * @var \Klb\Core\Task
     */
    protected $task;

    /**
     * Worker constructor.
     *
     * @param \Klb\Core\Task $task
     */
    public function __construct(\Klb\Core\Task $task)
    {
        $this->task = $task;
    }

    /**
     * Start the worker with type:
     * - daemon
     * - ...
     *
     * @param WorkerOptions $options
     * @return void
     */
    public function start(WorkerOptions $options){
        $method = $options->getType();
        $tube = $options::getTubeName();
        $this->$method($tube, $options);
    }

    /**
     * @inheritDoc
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->di = $dependencyInjector;
        $this->workerEvent = $this->di->get('workerQueueEvent');
    }

    /**
     * @inheritDoc
     */
    public function getDI()
    {
        return $this->di;
    }
    /**
     * Listen to the given queue in a loop.
     *
     * @param $queue
     * @param WorkerOptions $options
     * @return void
     */
    protected function daemon($queue, WorkerOptions $options)
    {
        $this->listenForSignals();

        $lastRestart = $this->getTimestampOfLastQueueRestart();

        while (true) {
            // Before reserving any jobs, we will make sure this queue is not paused and
            // if it is we will just pause this worker for a given amount of time and
            // make sure we do not need to kill this worker process off completely.
            if (! $this->daemonShouldRun($options)) {
                $this->pauseWorker($options, $lastRestart);
                continue;
            }

            // First, we will attempt to get the next job off of the queue. We will also
            // register the timeout handler and reset the alarm for this job so it is
            // not stuck in a frozen state forever. Then, we can fire off this job.
            $job = $this->getNextJob($queue);

            $this->registerTimeoutHandler($job, $options);

            // If the daemon should run (not in maintenance mode, etc.), then we can run
            // fire off this job for processing. Otherwise, we will need to sleep the
            // worker so no more jobs are processed until they should be processed.
            if ($job) {
                $this->runJob($job, $options);
            } else {
                $this->sleep($options->sleep);
            }

            // Finally, we will check to see if we have exceeded our memory limits or if
            // the queue should restart based on other indications. If so, we'll stop
            // this worker and let whatever is "monitoring" it restart the process.
            $this->stopIfNecessary($options, $lastRestart);
        }
    }

    /**
     * @return \Phalcon\Queue\Beanstalk
     */
    protected function getQueue(){
        if(!is_null($this->instanceQueue)){
            return $this->instanceQueue;
        }
        return $this->instanceQueue = $this->di->get('queue');
    }
    /**
     *
     * Register the worker timeout handler (PHP 7.1+).
     *
     * @param ProcessJob $job
     * @param WorkerOptions $options
     * @return void
     *
     */
    protected function registerTimeoutHandler($job, WorkerOptions $options)
    {
        if ($this->supportsAsyncSignals()) {
            // We will register a signal handler for the alarm signal so that we can kill this
            // process if it is running too long because it has frozen. This uses the async
            // signals supported in recent versions of PHP to accomplish it conveniently.
            pcntl_signal(SIGALRM, function () {
                $this->kill(1);
            });

            pcntl_alarm(
                max($this->timeoutForJob($job, $options), 0)
            );
        }
    }

    /**
     * Get the appropriate timeout for the given job.
     * @param ProcessJob $job
     * @param WorkerOptions $options
     * @return int
     */
    protected function timeoutForJob($job, WorkerOptions $options)
    {
        return $job && ! is_null($job->timeout()) ? $job->timeout() : $options->timeout;
    }

    /**
     * Determine if the daemon should process on this iteration.
     * @param WorkerOptions $options
     * @return bool
     */
    protected function daemonShouldRun(WorkerOptions $options)
    {
        if($options->force){
            return true;
        }
        return  !$this->paused;
    }

    /**
     * Pause the worker for the current loop.
     *
     * @param WorkerOptions $options
     * @param $lastRestart
     * @return void
     */
    protected function pauseWorker(WorkerOptions $options, $lastRestart)
    {
        $this->sleep($options->sleep > 0 ? $options->sleep : 1);

        $this->stopIfNecessary($options, $lastRestart);
    }

    /**
     * Stop the process if necessary.
     *
     * @param WorkerOptions $options
     * @param $lastRestart
     */
    protected function stopIfNecessary(WorkerOptions $options, $lastRestart)
    {
        if ($this->shouldQuit) {
            $this->kill();
        }

        if ($this->memoryExceeded($options->memory)) {
            $this->stop(12);
        } elseif ($this->queueShouldRestart($lastRestart)) {
            $this->stop();
        }
    }

    /**
     * Process the next job on the queue.
     *
     * @param $queue
     * @param WorkerOptions $options
     * @return void
     */
    public function runNextJob($queue, WorkerOptions $options)
    {
        $job = $this->getNextJob($queue);

        // If we're able to pull a job off of the stack, we will process it and then return
        // from this method. If there is no job on the queue, we will "sleep" the worker
        // for the specified number of seconds, then keep processing jobs after sleep.
        if ($job) {
            $this->runJob($job, $options);
            return;
        }

        $this->sleep($options->sleep);
    }

    /**
     * Get the next job from the queue connection.
     *
     * @param $queue
     * @return ProcessJob|null
     */
    protected function getNextJob($queue)
    {
        try {
            foreach (explode(',', $queue) as $queue) {
                $this->getQueue()->watch($queue);
                if (! is_null($job = $this->getQueue()->reserve())) {
                    return new ProcessJob($job, $queue);
                }
            }
        } catch (Exception $e) {
            $this->task->error($e);
            $this->stopWorkerIfLostConnection($e);
        } catch (Throwable $e) {
            $this->task->error($e);
            $this->stopWorkerIfLostConnection($e);
        }
    }

    /**
     * Process the given job.
     *
     * @param ProcessJob $job
     * @param WorkerOptions $options
     * @return void
     */
    protected function runJob($job, WorkerOptions $options)
    {
        try {
            $this->process($job, $options);
            return;
        } catch (Exception $e) {
            $this->task->error($e);
            $this->stopWorkerIfLostConnection($e);
        } catch (Throwable $e) {
            $this->task->error($e);
            $this->stopWorkerIfLostConnection($e);
        }
    }

    /**
     * Stop the worker if we have lost connection to a database.
     *
     * @param  \Exception  $e
     * @return void
     */
    protected function stopWorkerIfLostConnection($e)
    {
        if ($this->causedByLostConnection($e)) {
            $this->shouldQuit = true;
        }
    }

    /**
     * Process the given job from the queue.
     *
     * @param ProcessJob $job
     * @param WorkerOptions $options
     *
     * @throws \Throwable
     */
    public function process($job, WorkerOptions $options)
    {
        $options->task = $this->task;
        try {
            // First we will raise the before job event and determine if the job has already ran
            // over its maximum attempt limits, which could primarily happen when this job is
            // continually timing out and not actually throwing any exceptions from itself.
            $this->raiseBeforeJobEvent($job);

            $this->markJobAsFailedIfAlreadyExceedsMaxAttempts(
                $job, (int) $options->maxTries
            );

            // Here we will fire off the job and let it process. We will catch any exceptions so
            // they can be reported to the developers logs, etc. Once the job is finished the
            // proper events will be fired to let any listeners know this job has finished.
            $options->fire($job);
            $job->delete();
            $this->raiseAfterJobEvent($job);
        } catch (Exception $e) {
            $this->handleJobException($job, $options, $e);
        } catch (Throwable $e) {
            $this->handleJobException(
                $job, $options, $e
            );
        }
    }

    /**
     * Handle an exception that occurred while the job was running.
     *
     * @param ProcessJob  $job
     * @param  WorkerOptions  $options
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    protected function handleJobException($job, WorkerOptions $options, $e)
    {

        try {
            // First, we will go ahead and mark the job as failed if it will exceed the maximum
            // attempts it is allowed to run the next time we process it. If so we will just
            // go ahead and mark it as failed now so we do not have to release this again.
            if (! $job->hasFailed()) {
                $this->markJobAsFailedIfWillExceedMaxAttempts(
                    $job, (int) $options->maxTries, $e
                );
            }

            $this->raiseExceptionOccurredJobEvent(
                $job, $e
            );
        } finally {
            // If we catch an exception, we will attempt to release the job back onto the queue
            // so it is not lost entirely. This'll let the job be retried at a later time by
            // another listener (or this same one). We will re-throw this exception after.
            if (! $job->isDeleted() && ! $job->isReleased() && ! $job->hasFailed()) {
                $job->release($options->delay);
            }
        }

        throw $e;
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     *
     * This will likely be because the job previously exceeded a timeout.
     *
     * @param ProcessJob $job
     * @param $maxTries
     * @return void
     *
     */
    protected function markJobAsFailedIfAlreadyExceedsMaxAttempts($job, $maxTries)
    {
        $maxTries = ! is_null($job->maxTries()) ? $job->maxTries() : $maxTries;

        $timeoutAt = $job->timeoutAt();

        if ($timeoutAt && time() <= $timeoutAt) {
            return;
        }

        if (! $timeoutAt && ($maxTries === 0 || $job->attempts() <= $maxTries)) {
            return;
        }

        $this->failJob($job, $e = new MaxAttemptsExceededException(
            $job->getQueue().'('.$job->attempts().') has been attempted too many times or run too long. The job may have previously timed out.'
        ));

        throw $e;
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     *
     * @param ProcessJob  $job
     * @param  int  $maxTries
     * @param  \Exception  $e
     * @return void
     */
    protected function markJobAsFailedIfWillExceedMaxAttempts($job, $maxTries, $e)
    {

        $maxTries = ! is_null($job->maxTries()) ? $job->maxTries() : $maxTries;

        if ($job->timeoutAt() && $job->timeoutAt() <= time()) {
            $this->failJob($job, $e);
        }

        if ($maxTries > 0 && $job->attempts() >= $maxTries) {
            $this->failJob($job, $e);
        }
    }

    /**
     * Mark the given job as failed and raise the relevant event.
     *
     */
    protected function failJob($job, $e)
    {
        FailingJob::handle($job, $e);
    }

    /**
     * Raise the before queue job event.
     *
     * @param ProcessJob  $job
     * @return void
     */
    protected function raiseBeforeJobEvent($job)
    {
        $this->workerEvent->dispatch(WorkerListener::PROCESS, $job);
    }

    /**
     * Raise the after queue job event.
     *
     * @param ProcessJob  $job
     * @return void
     */
    protected function raiseAfterJobEvent($job)
    {
        $this->workerEvent->dispatch(WorkerListener::PROCESS, $job);
    }

    /**
     * Raise the exception occurred queue job event.
     *
     * @param ProcessJob  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function raiseExceptionOccurredJobEvent($job, $e)
    {
        $this->workerEvent->dispatch(WorkerListener::OCCURRED, $job, $e);
    }

    /**
     * Raise the failed queue job event.
     *
     * @param ProcessJob  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function raiseFailedJobEvent($job, $e)
    {
        $this->workerEvent->dispatch(WorkerListener::FAILED, $job, $e);
    }

    /**
     * Determine if the queue worker should restart.
     *
     * @param  int|null  $lastRestart
     * @return bool
     */
    protected function queueShouldRestart($lastRestart)
    {
        return $this->getTimestampOfLastQueueRestart() != $lastRestart;
    }

    /**
     * Get the last queue restart timestamp, or null.
     *
     * @return int|null
     */
    protected function getTimestampOfLastQueueRestart()
    {
        if ($this->cache) {
            return $this->cache->get('kalbe:queue:restart');
        }
    }

    /**
     * Enable async signals for the process.
     *
     * @return void
     */
    protected function listenForSignals()
    {
        if ($this->supportsAsyncSignals()) {
            pcntl_async_signals(true);

            pcntl_signal(SIGTERM, function () {
                $this->shouldQuit = true;
            });

            pcntl_signal(SIGUSR2, function () {
                $this->paused = true;
            });

            pcntl_signal(SIGCONT, function () {
                $this->paused = false;
            });
        }
    }

    /**
     * Determine if "async" signals are supported.
     *
     * @return bool
     */
    protected function supportsAsyncSignals()
    {
        return version_compare(PHP_VERSION, '7.1.0') >= 0 &&
            extension_loaded('pcntl');
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * @param  int   $memoryLimit
     * @return bool
     */
    public function memoryExceeded($memoryLimit)
    {
        return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Stop listening and bail out of the script.
     *
     * @param  int  $status
     * @return void
     */
    public function stop($status = 0)
    {
        $this->workerEvent->dispatch(WorkerListener::STOPING, null);

        exit($status);
    }

    /**
     * Kill the process.
     *
     * @param  int  $status
     * @return void
     */
    public function kill($status = 0)
    {
        $this->workerEvent->dispatch(WorkerListener::STOPING, null);

        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }

        exit($status);
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param  int   $seconds
     * @return void
     */
    public function sleep($seconds)
    {
        sleep($seconds);
    }

    /**
     * Set the cache repository implementation.
     *
     * @param  \Phalcon\Cache\Backend  $cache
     * @return void
     */
    public function setCache(\Phalcon\Cache\Backend $cache)
    {
        $this->cache = $cache;
    }
}
