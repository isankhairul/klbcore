<?php


namespace Klb\Core\Queue;

use Klb\Core\Mail\Queue;

/**
 * Class QueueMailWorker
 *
 * @package Kalbe\Worker\Order
 */
class QueueMailWorker extends WorkerOptions
{
    /**
     * The maximum amount of times a job may be attempted.
     *
     * @var int
     */
    public $maxTries = 5;

    /**
     * @inheritDoc
     */
    public static function getTubeName()
    {
        return 'queue_mails';
    }

    /**
     * @inheritDoc
     */
    public function fire( ProcessJob $job )
    {

        $data = $job->payload();

        if ( empty( $data ) || !is_array( $data ) ) {
            throw new InvalidPayloadException( 'Skip by empty source or empty data' );
        }

        Queue::of( !empty( $data['code'] ) ? $data['code'] : null )
            ->send( $data );

    }
}

