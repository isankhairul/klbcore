<?php namespace KlbV2\Core\Model;

use Phalcon\Db;
use Phalcon\Db\AdapterInterface;
use function array_key_exists;
use function md5;

/**
 * Class SelectOption
 *
 * @package KlbV2\Core\Model
 */
trait SelectOption
{
    /**
     * @param null   $defineKeys
     * @param null   $conditions
     * @param bool   $cache
     * @param string $prefixKey
     *
     * @return array|mixed
     */
    public static function getSelectOption( $defineKeys = null, $conditions = null, $cache = false, $prefixKey = '' )
    {
        $me = new static;
        $table = $me->getSource();
        if ( is_array( $defineKeys ) ) {
            $keys = $defineKeys;
        } else {
            $keys = static::selectOptionUsing();
        }
        if ( empty( $keys ) ) {
            return [];
        }
        $orderBy = static::defaultOptionSort();
        if ( array_key_exists( 'order', $keys ) ) {
            $orderBy = $keys['order'];
            unset( $keys['order'] );
        }
        $select = join( ', ', $keys );
        $where = [];
        if ( null !== $conditions ) {
            $where[] = $conditions;
        }
        if ( $defaultCondition = static::defaultCondition() ) {
            $where[] = $defaultCondition;
        }
        if ( count( $where ) > 0 ) {
            $conditions = 'WHERE ' . join( ' AND ', $where );
        }
        $sql = <<<SQL
SELECT $select FROM $table $conditions
SQL;
        if ( !empty( $orderBy ) ) {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        if ( isset( $keys[2] ) ) {
            $sql .= ' GROUP BY ' . $keys[2];
        }
        if ( ( $pos = stripos( $keys[0], ' as ' ) ) !== false ) {
            $keys[0] = substr( $keys[0], $pos + 4 );
        }
        if ( ( $pos = stripos( $keys[1], ' as ' ) ) !== false ) {
            $keys[1] = substr( $keys[1], $pos + 4 );
        }
        $key = md5( $sql );
        if ( $cache === true && ( $options = di( 'cache' )->get( $key ) ) ) {
            return $options;
        }
        /** @var AdapterInterface $conn */
        $conn = $me->getReadConnection();
        /** @var array $products */
        $me->getDI()->get( 'logger' )->info( $sql );
        $rows = $conn->fetchAll( $sql, Db::FETCH_ASSOC );
        $options = [];
        foreach ( $rows as $row ) {
            $optionKey = $prefixKey . $row[$keys[0]];
            $optionVal = $row[$keys[1]];
            $options[$optionKey] = $optionVal;
        }
        if ( $cache === true ) {
            di( 'cache' )->save( $key, $options );
        }
        return $options;
    }

    /**
     * @return array
     */
    protected static function selectOptionUsing()
    {
        return [];
    }

    protected static function defaultOptionSort()
    {
        return null;
    }

    /**
     * @return string
     */
    protected static function defaultCondition()
    {
        return '';
    }

    /**
     * @param null $defineKeys
     *
     * @return array
     */
    public function getSelectOptionValue( $defineKeys = null )
    {
        if ( is_array( $defineKeys ) ) {
            $keys = $defineKeys;
        } else {
            $keys = static::selectOptionUsing();
        }
        if ( empty( $keys ) ) {
            return [];
        }
        $options = [];
        foreach ( $this->toArray() as $row ) {
            $options[$row[$keys[0]]] = $row[$keys[1]];
        }
        return $options;
    }
}
