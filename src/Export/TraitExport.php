<?php namespace Klb\Core\Export;

use Exception;
use Klb\Core\Ftp\Ftp;
use Klb\Core\Task;
use PDO;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Logger\AdapterInterface;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\ManagerInterface;
use Swift_Attachment;
use function count;
use function di;
use function file_exists;
use function file_get_contents;
use function json_encode;

/**
 * Trait TraitExport
 *
 * @package Klb\Core\Export
 */
trait TraitExport
{
    /**
     * @var Task
     */
    private $task;
    /**
     * @var Model
     */
    private $queueExport;

    /**
     * @param Model $queueExport
     *
     * @return TraitExport
     */
    public function setModel( Model $queueExport )
    {
        $this->queueExport = $queueExport;

        return $this;
    }

    /**
     * @return ManagerInterface
     */
    public function getModelManager()
    {
        return di( 'modelsManager' );
    }

    /**
     * @return Mysql
     */
    public function getDb()
    {
        return di( 'db' );
    }

    /**
     * @return AdapterInterface
     */
    public function getLog()
    {
        return di( 'logger' );
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function sendMail( array $data = [] )
    {
        /** @var string $body Prepare Body */
        $body = 'This is your export file';
        $file = $this->getModel()->download_file;
        if ( file_exists( $file ) ) {
            $attachment = Swift_Attachment::newInstance()
                ->setFilename( $this->getModel()->filename )
                ->setContentType( 'application/csv' )
                ->setBody( file_get_contents( $this->getModel()->download_file ) );
        } else {
            $attachment = null;
        }

        return ( new Mail() )->sesEmailSend( 'Generate Export File Mocha: ' . $this->getModel()->created_at, [ $data['email'] ], $body, $attachment );
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->queueExport;
    }

    /**
     * @param array $ftpParams
     *
     * @return bool
     * @throws Exception
     */
    public function sendFtp( array $ftpParams = [] )
    {
        /** @var string $body Prepare Body */
        $file = $this->getModel()->download_file;
        $fileTo = $this->getModel()->filename;
        if ( !file_exists( $file ) ) {
            return false;
        }
        $this->getTask()->comment( "Send file to ftp: " . $file );
        $ftp = new Ftp( $ftpParams );
        $send = $ftp->uploadFile( $file, $fileTo );
        $ftp->disconnect();
        unset( $ftp );
        return $send;
    }

    /**
     * @return Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @param Task $task
     *
     * @return TraitExport
     */
    public function setTask( Task $task )
    {
        $this->task = $task;

        return $this;
    }

    /**
     * @param $i
     */
    protected function tick( $i )
    {
        di( 'cache' )->save( 'queue_export_' . $this->getModel()->id, $i );
    }

    /**
     * @return array
     */
    protected function start()
    {

        $params = $this->getModel()->getParams();
        $this->getTask()->info( "PARAMS: " . $this->getModel()->params );
        $rows = $this->loadData( $params );
        $this->getModel()->total = count( $rows );
        $this->getModel()->status = Model::STATUS_ONPROGRESS;

        $this->getModel()->save();

        return $rows;
    }

    /**
     * @param       $pCount
     * @param array $message
     *
     * @return bool
     */
    protected function finish( $pCount, array $message )
    {
        $this->getModel()->status = Model::STATUS_FINISH;
        $this->getModel()->progress = $pCount;
        $this->getModel()->message = json_encode( $message );

        return $this->getModel()->save();
    }

    /**
     * @param       $pCount
     * @param array $message
     *
     * @return bool
     */
    protected function failed( $pCount, array $message )
    {
        $this->getModel()->status = Model::STATUS_FAILED;
        $this->getModel()->progress = $pCount;
        $this->getModel()->message = json_encode( $message );

        return $this->getModel()->save();
    }

    /**
     * @param      $sql
     * @param null $bindParams
     * @param int  $fetchMode
     *
     * @return array
     */
    protected function fetchRows( $sql, $bindParams = null, $fetchMode = PDO::FETCH_ASSOC )
    {
        /** @var Mysql $db */
        $db = di()->getDb();
        $rs = $db->query( $sql . '', $bindParams );
        if ( !$rs ) {
            return [];
        }
        $rs->setFetchMode( $fetchMode );

        return $rs->fetchAll();
    }

}
