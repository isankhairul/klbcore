<?php namespace Klb\Core\Export;

use Klb\Core\Task;
use Phalcon\Mvc\Model;

/**
 * Interface ExportContract
 *
 * @package Klb\Core\Export
 */
interface ExportContract
{
    /**
     * @return Task
     */
    public function getTask();

    /**
     * @param Task $task
     * @return ExportContract
     */
    public function setTask(Task $task);

    /**
     * @param Model $queueExport
     * @return ExportContract
     */
    public function setQueueExport(Model $queueExport);

    /**
     * @return \Phalcon\Mvc\Model\ManagerInterface
     */
    public function getModelManager();

    /**
     * @return \Phalcon\Db\Adapter\Pdo\Mysql
     */
    public function getDb();

    /**
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function getLog();

    /**
     * Load the Data
     * @param \stdClass $params
     * @return array|void
     * @throws \Exception
     */
    public function loadData($params);
    /**
     * Handle of import
     *
     * @throws \Exception
     * @return boolean
     */
    public function handle();
    /**
     * Handle of success
     *
     * @return $this
     */
    public function onSuccess();
    /**
     * Handle of error
     *
     * @return $this
     */
    public function onError(\Exception $e);

    /**
     * Get Name of importer
     *
     * @return string
     */
    public function getName();
}
