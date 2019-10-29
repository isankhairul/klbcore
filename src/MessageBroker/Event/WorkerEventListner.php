<?php namespace Klb\Core\Event;


use Klb\Core\Event\Contract\Listener;
use Klb\Core\Queue\InvalidAndDestroyException;
use Phalcon\Events\Event;

/**
 * Class WorkerEventListner
 *
 * @package Klb\Core\Event
 */
class WorkerEventListner implements Listener {

    const OCCURRED = 'occurred';
    const FAILED = 'failed';
    const PROCESS = 'process';
    const PAUSE = 'pause';
    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'worker';
    }

    public function occurred(Event $event){
        $data = $event->getData();
//        echo "OCCURRED: " . json_encode($data['job']->payload()) . "\n";
        /** @var ProcessJob $job */
        $job  = $data['job'];
        /** @var \Exception $e */
        $e    = $data['e'];
        $this->storeDb($job, $e);
    }

    public function failed(Event $event){
        $data = $event->getData();
//        echo "FAILED: " . json_encode($data['job']->payload()) . "\n";
        /** @var ProcessJob $job */
        $job  = $data['job'];
        /** @var \Exception $e */
        $e    = $data['e'];
        $this->storeDb($job, $e);
    }

    public function process(Event $event){
//        echo "PROCESS: " . json_encode($event->getData()['job']->payload()) . "\n";
    }
    public function stoping(Event $event){
//        echo "STOPING: " . json_encode($event->getData()['job']->payload()) . "\n";
    }
    /**
     * @param ProcessJob $job
     * @param \Exception $e
     */
    private function storeDb($job, $e){

        if(is_object($e) && $e instanceof InvalidAndDestroyException){
            if(!$job->isDeleted()){
                $job->delete();
            }
            return;
        }

        $failedJob = new FailedJobs();
        $failedJob->queue = $job->getQueue();
        $failedJob->payload = json_encode($job->payload());
        $failedJob->exception = json_encode([ 'message' => $e->getMessage(), 'class' => get_class($e), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        $failedJob->failed_at = date('Y-m-d H:i:s');
        if(false !== $failedJob->save()){
            if(!$job->isDeleted()){
                $job->delete();
            }
        }
        unset($failedJob);
    }

}
