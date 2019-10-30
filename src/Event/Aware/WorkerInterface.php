<?php


namespace Klb\Core\Event\Aware;


interface WorkerInterface
{
    /**
     * @param $type
     * @param $job
     * @param $e
     *
     * @return mixed
     */
    public function dispatch( $type, $job, $e = null );
}
