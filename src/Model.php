<?php namespace Klb\Core;

use PDO;
use Phalcon\Mvc\Model\Behavior\Timestampable;

/**
 * Class Model
 *
 * @package App
 */
class Model extends \Phalcon\Mvc\Model
{

    /**
     * @var
     */
    protected static $criteriaClass;

    public function initialize()
    {
        $this->keepSnapshots( true );
        $this->addBehavior(
            new Timestampable(
                [
                    "beforeCreate" => [
                        "field"  => "created_at",
                        "format" => "Y-m-d H:i:s",
                    ],
                    "beforeUpdate" => [
                        "field"  => "updated_at",
                        "format" => "Y-m-d H:i:s",
                    ],
                ]
            )
        );
//        $this->setReadConnectionService("dbSlave");
//
//        $this->setWriteConnectionService("db");
    }

    /**
     * Sync a many to many record to an entity
     *
     * Usage:
     * $entity->sync('alias_name', array(1, 2, 3), $transaction);
     *
     * @param string                              $relatedAlias string   Many to Many alias name
     * @param array                               $identifiers  array    array of ID's of the related records
     * @param \Phalcon\Mvc\Model\Transaction|null $transaction  object   Phalcon Trancation object if the entity is
     *                                                          part of a transaction
     *
     * @return bool
     * @throws \Exception
     */
    public function sync( $relatedAlias, array $identifiers, \Phalcon\Mvc\Model\Transaction $transaction = null )
    {
        $modelsmanager = $this->getDI()->getModelsManager();
        $relation = $modelsmanager->getRelationByAlias( get_class( $this ), $relatedAlias );
        if ( !$relation ) {
            $msg = sprintf( 'Relation alias "%s" does not exists', $relatedAlias );
            if ( $transaction ) {
                $transaction->rollback( $msg );
            } else {
                throw new \Exception( $msg );
            }
        }

        $relatedIds = [];
        $existingIds = [];
        $newIds = [];

        $related = $this->getRelated( $relatedAlias );
        foreach ( $related as $r ) {
            array_push( $relatedIds, $r->id );
        }

        foreach ( $identifiers as $i ) {
            if ( !in_array( $i, $relatedIds ) ) {
                array_push( $newIds, $i );
            } else {
                array_push( $existingIds, $i );
            }
        }

        $deleteIds = array_diff( $relatedIds, array_merge( $existingIds, $newIds ) );

        $intermediateModel = $relation->getIntermediateModel();
        foreach ( $newIds as $relatedId ) {
            $intermediate = new $intermediateModel;
            if ( $transaction ) {
                $intermediate->setTransaction( $transaction );
            }
            $intermediate->{$relation->getIntermediateFields()} = $this->id;
            $intermediate->{$relation->getIntermediateReferencedFields()} = $relatedId;
            if ( $intermediate->create() == false ) {
                $msg = 'Could not create intermediate record: ';
                foreach ( $intermediate->getMessages() as $m ) {
                    $msg .= $m;
                }
                if ( $transaction ) {
                    $transaction->rollback( $msg );
                } else {
                    throw new \Exception( $msg );
                }
            }
        }

        foreach ( $deleteIds as $relatedId ) {
            $intermediate = $intermediateModel::findFirst( [
                $relation->getIntermediateFields() . ' = ?0 AND ' . $relation->getIntermediateReferencedFields() . ' = ?1',
                'bind' => [
                    0 => $this->id,
                    1 => $relatedId,
                ],
            ] );
            if ( $intermediate ) {
                if ( $transaction ) {
                    $intermediate->setTransaction( $transaction );
                }
                if ( $intermediate->delete() == false ) {
                    $msg = 'Could not delete intermediate record: ';
                    foreach ( $intermediate->getMessages() as $m ) {
                        $msg .= $m;
                    }
                    if ( $transaction ) {
                        $transaction->rollback( $msg );
                    } else {
                        throw new \Exception( $msg );
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param null $parameters
     *
     * @return \Phalcon\Mvc\Model|static
     */
    public static function findFirstOrCreate( $parameters = null )
    {
        $row = static::findFirst( $parameters );
        if ( false === $row ) {
            $row = static::newInstance();
        }

        return $row;
    }

    /**
     * @param      $sql
     * @param null $bindParams
     * @param int  $fetchMode
     *
     * @return array
     */
    public function fetchRows( $sql, $bindParams = null, $fetchMode = PDO::FETCH_OBJ )
    {
        /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
        $db = $this->getDI()->getDb();
        $rs = $db->query( $sql, $bindParams );
        if ( !$rs ) {
            return [];
        }
        $rs->setFetchMode( $fetchMode );

        return $rs->fetchAll();
    }

    /**
     * @param      $sql
     * @param null $bindParams
     * @param int  $fetchMode
     *
     * @return mixed|null
     */
    public function fetch( $sql, $bindParams = null, $fetchMode = PDO::FETCH_OBJ )
    {
        /** @var \Phalcon\Db\Adapter\Pdo\Mysql $db */
        $db = $this->getDI()->getDb();
        $rs = $db->query( $sql, $bindParams );
        if ( !$rs ) {
            return null;
        }
        $rs->setFetchMode( $fetchMode );

        return $rs->fetch();
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        $sql = <<<SQL
SELECT column_name FROM information_schema.columns WHERE table_name = ?0
SQL;

        return $this->fetchRows( $sql, [ static::getTableName() ], PDO::FETCH_ASSOC );
    }

    /**
     *
     */
    public function notSaved()
    {
        // Obtain the flash service from the DI container
        if ( $this->getDI()->has( 'flash' ) ) {
            $flash = $this->getDI()->getFlash();

            $messages = $this->getMessages();

            // Show validation messages
            foreach ( $messages as $message ) {
                $flash->error( $message );
            }
        }
    }

    /**
     * @return string
     */
    public static function getTableName()
    {
        return static::newInstance()->getSource();
    }

    /**
     * @return static
     */
    public static function newInstance()
    {
        return new static();
    }

    /**
     * @return mixed
     */
    public static function getMetaDataAttributes()
    {
        $me = static::newInstance();
        $metadata = $me->getModelsMetaData();

        return $metadata->getAttributes( $me );
    }

    /**
     * @return mixed
     */
    public static function getMetaDataTypes()
    {
        $me = static::newInstance();
        $metadata = $me->getModelsMetaData();

        return $metadata->getDataTypes( $me );
    }

    /**
     * @return mixed
     */
    public static function clearMetaData()
    {
        return static::newInstance()->getModelsMetaData()->reset();
    }

    /**
     * @param bool $write
     *
     * @return \Phalcon\Db\AdapterInterface
     */
    public static function getConnection( $write = false )
    {
        return $write ? ( new self )->getWriteConnection() : ( new self )->getReadConnection();
    }

    /**
     * @return \Phalcon\Mvc\Model\Criteria
     */
    public static function query( \Phalcon\DiInterface $dependencyInjector = null )
    {
        /** @var \Phalcon\Mvc\Model\Criteria $class */
        if ( $class = static::$criteriaClass ) {
            $criteria = new $class;
            $criteria->setDI( $dependencyInjector ?: di() );
            $criteria->setModelName( get_called_class() );

            return $criteria;
        }

        return parent::query( $dependencyInjector );
    }

    /**
     * @return \ArrayObject
     */
    public function getPrimaryKey()
    {
        return new \ArrayObject( $this->getModelsMetaData()->getPrimaryKeyAttributes( $this ) );
    }

    /**
     *
     */
    public static function rmCacheModelsMetaData()
    {
        /** @var \Phalcon\Mvc\Model\MetaData\Redis $cache */
        $cache = di( 'modelsMetadata' );
        if ( $cache instanceof \Phalcon\Mvc\Model\MetaData\Redis ) {
            $cache->reset();
            /** @var \Phalcon\Cache\Backend\Redis $redisCache */
            $redisCache = di( 'cache' );
            $keys = $redisCache->queryKeys();
            if ( !empty( $keys ) ) {
                foreach ( $keys as $key ) {
                    if ( strpos( $key, 'map-kalbe' ) !== false || strpos( $key, 'meta-kalbe' ) !== false ) {
                        $success = $redisCache->delete( $key );
                        di( 'logger' )->log( 'info', 'DELETE METADATA: ' . $key . ' SUCCESS: ' . ( $success ? 'OK' : 'FAILED' ) );
                    }
                }
            }
        }
    }

    protected function arrayToObject( $d )
    {
        return is_array( $d ) ? (object) array_map( __METHOD__, $d ) : $d;
    }
}
