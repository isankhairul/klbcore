<?php namespace KlbV2\Core\Event;

use Exception;
use KlbV2\Core\Event\Contract\Listener;
use KlbV2\Core\Model\FailedJobs;
use KlbV2\Core\Queue\InvalidAndDestroyException;
use KlbV2\Core\Queue\ProcessJob;
use Phalcon\Events\Event;
use function is_object;

/**
 * Class WorkerListener
 *
 * @package KlbV2\Core\Event
 */
class WorkerListener implements Listener
{
    const OCCURRED = 'occurred';
    const FAILED = 'failed';
    const PROCESS = 'process';
    const STOPING = 'stoping';

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'worker';
    }

    public function occurred( Event $event )
    {
        $data = $event->getData();
//        echo "OCCURRED: " . json_encode($data['job']->payload()) . "\n";
        /** @var ProcessJob $job */
        $job = $data['job'];
        /** @var Exception $e */
        $e = $data['e'];
        $this->storeDb( $job, $e );
    }

    /**
     * @param ProcessJob $job
     * @param Exception $e
     */
    private function storeDb( $job, $e )
    {

        if ( is_object( $e ) && $e instanceof InvalidAndDestroyException ) {
            if ( !$job->isDeleted() ) {
                $job->delete();
            }
            return;
        }

        $failedJob = new FailedJobs();
        $failedJob->queue = $job->getQueue();
        $failedJob->payload = json_encode( $job->payload() );
        $failedJob->exception = json_encode( [ 'message' => $e->getMessage(), 'class' => get_class( $e ), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine() ] );
        $failedJob->failed_at = date( 'Y-m-d H:i:s' );
        if ( false !== $failedJob->save() ) {
            if ( !$job->isDeleted() ) {
                $job->delete();
            }
        }
        unset( $failedJob );
    }

    public function failed( Event $event )
    {
        $data = $event->getData();
//        echo "FAILED: " . json_encode($data['job']->payload()) . "\n";
        /** @var ProcessJob $job */
        $job = $data['job'];
        /** @var Exception $e */
        $e = $data['e'];
        $this->storeDb( $job, $e );
    }

    public function process( Event $event )
    {
//        echo "PROCESS: " . json_encode($event->getData()['job']->payload()) . "\n";
    }

    public function stoping( Event $event )
    {
//        echo "STOPING: " . json_encode($event->getData()['job']->payload()) . "\n";
    }

}
