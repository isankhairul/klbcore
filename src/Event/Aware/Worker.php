<?php namespace Klb\Core\Event\Aware;


use Klb\Core\Event\WorkerListener;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;

/**
 * Class Worker
 *
 * @package Klb\Core\Event\Aware
 */
class Worker implements WorkerInterface, EventsAwareInterface
{

    protected $_eventsManager;
    /**
     * @inheritDoc
     */
    public function setEventsManager(ManagerInterface $eventsManager)
    {
        $this->_eventsManager = $eventsManager;
    }

    /**
     * @inheritDoc
     */
    public function getEventsManager()
    {
        return $this->_eventsManager;
    }

    /**
     * @param $type
     * @param $job
     * @param $e
     */
    public function dispatch($type, $job, $e = null){
        $this->getEventsManager()->fire((new WorkerListener())->getName().':' . $type, $this, compact('job', 'e'));
    }
}
