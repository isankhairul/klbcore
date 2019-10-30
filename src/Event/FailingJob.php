<?php namespace Klb\Core\Event;

use Exception;
use Klb\Core\Event\Aware\WorkerInterface;
use Klb\Core\Queue\ManuallyFailedException;
use Klb\Core\Queue\ProcessJob;


/**
 * Class FailingJob
 *
 * @package Klb\Core\Queue
 */
class FailingJob
{
    /**
     * Delete the job, call the "failed" method, and raise the failed job event.
     *
     * @param ProcessJob $job
     * @param Exception $e
     *
     * @return void
     */
    public static function handle( $job, $e = null )
    {

        $job->markAsFailed();

        if ( $job->isDeleted() ) {
            return;
        }

        try {
            // If the job has failed, we will delete it, call the "failed" method and then call
            // an event indicating the job has failed so it can be logged if needed. This is
            // to allow every developer to better keep monitor of their failed queue jobs.
            $job->delete();
        } finally {
            static::events()->dispatch( WorkerListener::FAILED,
                $job, $e ?: new ManuallyFailedException
            );
        }
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return WorkerInterface
     */
    protected static function events()
    {
        return di()->get( 'workerQueueEvent' );
    }
}
