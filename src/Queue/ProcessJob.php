<?php namespace Klb\Core\Queue;

use Phalcon\Queue\Beanstalk\Job;

class ProcessJob
{

    /**
     * The job handler instance.
     *
     * @var Job
     */
    protected $job;

    /**
     * Indicates if the job has been deleted.
     *
     * @var bool
     */
    protected $deleted = false;

    /**
     * Indicates if the job has been released.
     *
     * @var bool
     */
    protected $released = false;

    /**
     * Indicates if the job has failed.
     *
     * @var bool
     */
    protected $failed = false;

    /**
     * The name of the queue the job belongs to.
     *
     * @var string
     */
    protected $queue;

    /**
     * ProcessJob constructor.
     *
     * @param Job    $job
     * @param string $queue
     */
    public function __construct( Job $job, $queue )
    {
        $this->job = $job;
        $this->queue = $queue;
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        $this->deleted = true;
        $this->job->delete();
    }

    /**
     * Release the job back into the queue.
     *
     * @param int $delay
     *
     * @return void
     */
    public function release( $delay = 0 )
    {
        $this->released = true;
    }

    /**
     * Determine if the job has been deleted or released.
     *
     * @return bool
     */
    public function isDeletedOrReleased()
    {
        return $this->isDeleted() || $this->isReleased();
    }

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Determine if the job was released back into the queue.
     *
     * @return bool
     */
    public function isReleased()
    {
        return $this->released;
    }

    /**
     * Determine if the job has been marked as a failure.
     *
     * @return bool
     */
    public function hasFailed()
    {
        return $this->failed;
    }

    /**
     * Mark the job as "failed".
     *
     * @return void
     */
    public function markAsFailed()
    {
        $this->failed = true;
    }

    /**
     * Get the number of times to attempt a job.
     *
     * @return int|null
     */
    public function maxTries()
    {
        return $this->get( 'maxTries' );
    }

    /**
     * @param      $key
     * @param null $default
     *
     * @return mixed|null
     */
    public function get( $key, $default = null )
    {
        $payload = $this->payload();
        return isset( $payload[$key] ) ? $payload[$key] : $default;
    }

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload()
    {
        return $this->job->getBody();
    }

    /**
     * Get the number of seconds the job can run.
     *
     * @return int|null
     */
    public function timeout()
    {
        return $this->get( 'timeout' );
    }

    /**
     * Get the timestamp indicating when the job should timeout.
     *
     * @return int|null
     */
    public function timeoutAt()
    {
        return $this->get( 'timeoutAt' );
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        $stats = $this->job->stats();

        if ( is_array( $stats ) && isset( $stats['reserves'] ) ) {
            return (int) $stats['reserves'];
        }
        return isset( $stats->reserves ) ? (int) $stats->reserves : 0;
    }

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }
}
