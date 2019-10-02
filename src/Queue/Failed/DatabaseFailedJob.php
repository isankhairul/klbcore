<?php namespace Klb\Core\Queue\Failed;

use Klb\Core\Model\FailedJobs;

/**
 * Class DatabaseFailedJob
 *
 * @package Klb\Core\Queue\Failed
 */
class DatabaseFailedJob implements FailedJobInterface
{

    /**
     * @inheritDoc
     */
    public function log($connection, $queue, $payload, $exception)
    {
        $failed_at = date('Y-m-d H:i:s');

        $exception = (string) $exception;

        $connection = $connection ?: $this->getTable()->getConnectionId();

        $this->getTable()->insert('failed_jobs', compact('connection', 'queue', 'payload', 'exception', 'failed_at'));
        return $this->getTable()->lastInsertId();
    }

    /**
     * @inheritDoc
     */
    public function all()
    {
        return $this->getTable()->fetchAll('SELECT * FROM failed_jobs ORDER BY id DESC');
    }

    /**
     * @inheritDoc
     */
    public function find($id)
    {
        return $this->getTable()->fetchOne('SELECT * FROM failed_jobs WHERE id = ' . intval($id));
    }

    /**
     * @inheritDoc
     */
    public function forget($id)
    {
        return $this->getTable()->delete('failed_jobs', 'id = ' . intval($id));
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        return $this->getTable()->delete('failed_jobs');
    }

    /**
     * @return \Phalcon\Db\AdapterInterface
     */
    protected function getTable(){
        /**
         * @var \Phalcon\Mvc\Model\ManagerInterface $manager
         */
        $manager = di()->get('modelsManager');
        return $manager->getWriteConnection(new FailedJobs());
    }
}
