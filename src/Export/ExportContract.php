<?php namespace KlbV2\Core\Export;

use Exception;
use KlbV2\Core\Task;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Logger\AdapterInterface;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\ManagerInterface;
use stdClass;

/**
 * Interface ExportContract
 *
 * @package KlbV2\Core\Export
 */
interface ExportContract
{
    /**
     * @return Task
     */
    public function getTask();

    /**
     * @param Task $task
     *
     * @return ExportContract
     */
    public function setTask( Task $task );

    /**
     * @param Model $queueExport
     *
     * @return ExportContract
     */
    public function setQueueExport( Model $queueExport );

    /**
     * @return ManagerInterface
     */
    public function getModelManager();

    /**
     * @return Mysql
     */
    public function getDb();

    /**
     * @return AdapterInterface
     */
    public function getLog();

    /**
     * Load the Data
     *
     * @param stdClass $params
     *
     * @return array|void
     * @throws Exception
     */
    public function loadData( $params );

    /**
     * Handle of import
     *
     * @return boolean
     * @throws Exception
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
    public function onError( Exception $e );

    /**
     * Get Name of importer
     *
     * @return string
     */
    public function getName();
}
