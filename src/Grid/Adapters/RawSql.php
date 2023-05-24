<?php namespace KlbV2\Core\Grid\Adapters;

use BadMethodCallException;
use KlbV2\Core\Grid\Builder\DbSelectException;
use KlbV2\Core\Grid\Builder\Expr;
use KlbV2\Core\Grid\Builder\Select;
use Phalcon\Db;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Paginator\Adapter\NativeArray;
use function strpos;

/**
 * Class RawSql
 *
 * @package KlbV2\Core\Grid\Adapters
 */
class RawSql extends AdapterAbstract
{
    private $colMaps = [];
    /**
     * @var Select
     */
    private $builder = null;

    /**
     * @return Select
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @param Select $builder
     *
     * @return $this
     * @throws DbSelectException
     */
    public function setBuilder( Select $builder )
    {
        $this->builder = $builder;

        $this->columnMap();

        return $this;
    }

    /**
     * @throws DbSelectException
     */
    private function columnMap()
    {
        $columns = $this->builder->getPart( Select::COLUMNS ) ?: $this->getColumns();

        if ( is_array( $columns ) ) {
            foreach ( $columns as $icol => $column ) {
                if ( is_array( $column ) ) {
                    $key = $column[2];
                    if ( $column[1] instanceof Expr ) {
                        $value = $column[1] . '';
                    } else {
                        $value = $column[0] . '.' . $column[1];
                    }
                } else {
                    $key = $this->removeDot( $column, $icol );
                    $value = $column;
                }
                $this->colMaps[$key] = $value;
            }
        } else if ( is_string( $columns ) ) {
            $split = explode( ', ', $columns );
            foreach ( $split as $item ) {
                if ( stripos( $item, ' as ' ) !== false ) {
                    $col = preg_split( '/\sas\s/i', $item );
                    $this->colMaps[$this->removeDot( $col[1] )] = $col[0];
                } else {
                    $this->colMaps[$this->removeDot( $item )] = $item;
                }
            }
        }
    }

    /**
     * @param $str
     *
     * @return mixed
     */
    private function removeDot( $str, $default = null )
    {
        if ( !preg_match( '/^[\w\d\.]+$/', $str ) ) {
            if ( null !== $default ) {
                return $default;
            }

            return $str;
        }
        if ( strpos( $str, '.' ) === false ) {
            return $default !== null ? $default : $str;
        }
        $column = explode( '.', $str );
        if ( $default !== null && $default !== $column[1] ) {
            return $default;
        }

        return $column[1];
    }

    /**
     * @param $column
     *
     * @return bool
     */
    public function columnExists( $column )
    {
        if ( count( $this->colMaps ) === 0 ) {
            return parent::columnExists( $column );
        }

        return array_key_exists( $column, $this->colMaps );
    }

    /**
     * @return array|mixed
     */
    public function getResponse()
    {
        $colMaps = $this->colMaps ?: $this->getColumns();
//\pre($this->colMaps, $this->getColumns(), $this->builder->getPart(Select::COLUMNS));
        $this->bind( 'global_search', function ( $column, $search ) use ( $colMaps ) {
            if ( isset( $colMaps[$column] ) ) {
                $sqlColumn = $colMaps[$column];
            } else {
                $sqlColumn = $column;
            }
            if ( is_array( $search ) ) {
                $comboCols = [];
                foreach ( $search as $item ) {
                    if ( strpos( $item, ':' ) === false ) {
                        $c = $column;
                        $v = $item;
                    } else {
                        list( $c, $v ) = explode( ':', $item );
                    }
                    if ( isset( $colMaps[$c] ) ) {
                        $sqlColumn = $colMaps[$c];
                    } else {
                        $sqlColumn = $c;
                    }
                    $value = "%{$v}%";
                    $operator = 'LIKE';
                    if ( isset( $this->valueFormat[$c] ) && is_callable( $this->valueFormat[$c] ) ) {
                        $func = $this->valueFormat[$c];
                        $value = $func( $v );
                    } else if ( is_numeric( $v ) ) {
                        $value = $v;
                        $operator = '=';
                    }
                    if ( isset( $this->operator[$c] ) ) {
                        $operator = $this->operator[$c];
                    }
                    if ( !isset( $comboCols[$c] ) ) {
                        $comboCols[$c] = [];
                    }
                    $comboCols[$c][] = compact( 'value', 'operator' );
                }
                foreach ( $comboCols as $c => $cols ) {
                    $wheres = [];
                    $condition = $this->_condition( $c );
                    foreach ( $cols as $i => $col ) {
                        $operator = $col['operator'];
                        $value = $col['value'];
                        if ( $condition === 'having' ) {
                            $wheres[] = "{$sqlColumn} {$operator} '$value'";
                        } else {
                            $wheres[] = "{$sqlColumn} {$operator} '$value'";
                        }
                    }

                    if ( $condition === 'having' ) {
                        $this->builder->having( join( ' OR ', $wheres ) );
                    } else {
                        $this->builder->$condition( join( ' OR ', $wheres ) );
                    }
                }
            } else {
                $value = "%{$search}%";
                $operator = 'LIKE';

                if ( isset( $this->valueFormat[$column] ) && is_callable( $this->valueFormat[$column] ) ) {
                    $func = $this->valueFormat[$column];
                    $value = $func( $search );
                } else if ( is_numeric( $search ) ) {
                    $value = $search;
                    $operator = '=';
                }

                if ( isset( $this->operator[$column] ) ) {
                    $operator = $this->operator[$column];
                }
                $condition = $this->_condition( $column );
                if ( $condition === 'having' ) {
                    $this->builder->$condition( "{$sqlColumn} {$operator} '$value'" );
                } else {
                    $this->builder->$condition( "{$sqlColumn} {$operator} ?", $value );
                }
            }
        } );

        $this->bind( 'column_search', function ( $column, $search, $type = null ) use ( $colMaps ) {
            if ( isset( $colMaps[$column] ) ) {
                $sqlColumn = $colMaps[$column];
            } else {
                $sqlColumn = $column;
            }

            switch ( $type ) {
                case 'range':
                case 'date':
                case 'datetime':
                    if ( $type === 'date' ) {
                        if ( !empty( $search['to'] ) ) {
                            $search['to'] = date( 'Y-m-d', strtotime( $search['to'] ) );
                        }
                        if ( !empty( $search['from'] ) ) {
                            $search['from'] = date( 'Y-m-d', strtotime( $search['from'] ) );
                        }
                    }
                    if ( $type === 'datetime' ) {
                        if ( !empty( $search['to'] ) ) {
                            $search['to'] = date( 'Y-m-d 23:59:59', strtotime( $search['to'] ) );
                        }
                        if ( !empty( $search['from'] ) ) {
                            $search['from'] = date( 'Y-m-d H:i:s', strtotime( $search['from'] ) );
                        }
                    }
                    $condition = $this->_condition( $column );
                    if ( $condition === 'having' ) {
                        if ( $type === 'date' ) {
                            $sqlColumn = 'DATE(' . $column . ')';
                        } else {
                            $sqlColumn = $column;
                        }
                    } else {
                        if ( $type === 'date' ) {
                            $sqlColumn = 'DATE(' . $sqlColumn . ')';
                        }
                    }
                    if ( !empty( $search['to'] ) && empty( $search['from'] ) ) {
                        $this->builder->$condition( "{$sqlColumn} <= '{$search['to']}'" );
                    } else if ( !empty( $search['from'] ) && empty( $search['to'] ) ) {
                        $this->builder->$condition( "{$sqlColumn} >= '{$search['from']}'" );
                    } else if ( !empty( $search['from'] ) && !empty( $search['to'] ) ) {
                        $this->builder->$condition( "{$sqlColumn} >= '{$search['from']}' AND {$sqlColumn} <= '{$search['to']}'" );
                    }
                    break;
                default:
                    if ( is_array( $search ) ) {
                        $comboCols = [];
                        foreach ( $search as $item ) {
                            if ( strpos( $item, ':' ) === false ) {
                                $c = $column;
                                $v = $item;
                            } else {
                                list( $c, $v ) = explode( ':', $item );
                            }
                            if ( isset( $colMaps[$c] ) ) {
                                $sqlColumn = $colMaps[$c];
                            } else {
                                $sqlColumn = $c;
                            }
                            $value = "%{$v}%";
                            $operator = 'LIKE';
                            if ( isset( $this->valueFormat[$c] ) && is_callable( $this->valueFormat[$c] ) ) {
                                $func = $this->valueFormat[$c];
                                $value = $func( $v );
                            } else if ( is_numeric( $v ) ) {
                                $value = $v;
                                $operator = '=';
                            }
                            if ( isset( $this->operator[$c] ) ) {
                                $operator = $this->operator[$c];
                            }
                            if ( !isset( $comboCols[$c] ) ) {
                                $comboCols[$c] = [];
                            }
                            $comboCols[$c][] = compact( 'value', 'operator' );
                        }
                        foreach ( $comboCols as $c => $cols ) {
                            $wheres = [];
                            $condition = $this->_condition( $c );
                            foreach ( $cols as $i => $col ) {
                                $operator = $col['operator'];
                                $value = $col['value'];
                                if ( $condition === 'having' ) {
                                    $wheres[] = "{$sqlColumn} {$operator} '$value'";
                                } else {
                                    $wheres[] = "{$sqlColumn} {$operator} '$value'";
                                }
                            }

                            if ( $condition === 'having' ) {
                                $this->builder->having( join( ' OR ', $wheres ) );
                            } else {
                                $this->builder->$condition( join( ' OR ', $wheres ) );
                            }
                        }
                    } else {
                        $value = "%{$search}%";
                        $operator = 'LIKE';

                        if ( isset( $this->valueFormat[$column] ) && is_callable( $this->valueFormat[$column] ) ) {
                            $func = $this->valueFormat[$column];
                            $value = $func( $search );
                        } else if ( is_numeric( $search ) ) {
                            $value = $search;
                            $operator = '=';
                        }

                        if ( isset( $this->operator[$column] ) ) {
                            if ( is_array( $this->operator[$column] ) && isset( $this->operator[$column][$search] ) ) {
                                $operator = $this->operator[$column][$search];
                            } else {
                                $operator = $this->operator[$column];
                            }
                        }
                        $condition = $this->_condition( $column );
                        if ( $condition === 'having' ) {
                            $this->builder->having( "{$sqlColumn} {$operator} '$value'" );
                        } else {
                            $this->builder->$condition( "{$sqlColumn} {$operator} ?", $value );
                        }
                    }
                    break;
            }
        } );

        $this->bind( 'order', function ( $order, $orderBy ) {
            if ( !empty( $order ) ) {
                foreach ( $orderBy as $orderById => $orderByCol ) {
                    $cOrderBy = ( isset( $this->colMaps[$orderById] ) && strpos( $this->colMaps[$orderById], $orderById ) !== false ? $this->colMaps[$orderById] : $orderById ) . ' ' . $orderByCol['dir'];
                    $this->builder->order( new Expr( $cOrderBy ) );
                }

            }
        } );

        if ( count( $this->filters ) ) {
            foreach ( $this->filters as $filter ) {
                if ( is_callable( $filter ) ) {
                    $filter( $this->builder );
                }
            }
        }

//        \pre($this->builder.'');
        /** @var Mysql $db */
        $db = di( 'db' );
//        pre($this->parser->getLimit(),/* $this->parser->getParams(), */$this->parser->getColumnsSearch(), $this->columns, $this->colMaps, $this->builder.'');
        $builderOptions = [
            'data'  => $db->fetchAll( $this->builder->__toString(), $this->getParser()->isFetchAssoc() ? Db::FETCH_ASSOC : Db::FETCH_OBJ ),
            'limit' => $this->parser->getLimit(),
            'page'  => $this->parser->getPage(),
        ];
        $paginator = new NativeArray( $builderOptions );
        $this->builder
            ->reset( Select::ORDER )
            ->reset( Select::LIMIT_COUNT )
            ->reset( Select::LIMIT_OFFSET );
        $sql = "SELECT COUNT(*) AS rowcount FROM (" . $this->builder->__toString() . ") AS t";
        $total = $db->fetchOne( $sql );
        $paginate = $paginator->getPaginate();
        $total = $total['rowcount'];
        $filtered = $paginate->total_items;
        $data = $paginate->items;

        if ( null !== $this->getSum() ) {
            $callback = $this->getSum();
            $sum = $callback( $this->builder, $data );
        }

        return $this->formResponse( compact( 'total', 'filtered', 'data', 'sum' ) );
    }

    /**
     * @param        $c
     * @param string $condition
     * @param string $op
     *
     * @return string
     */
    private function _condition( $c, $condition = 'where' )
    {
        if ( !in_array( $condition, [ 'where', 'having', 'orWhere', 'orHaving' ] ) ) {
            throw new BadMethodCallException( 'Condition not allowed: ' . $condition );
        }
        if ( isset( $this->conditions[$c] ) ) {
            if ( !method_exists( $this->builder, $this->conditions[$c] ) ) {
                throw new BadMethodCallException( 'Unable to find method: ' . $this->conditions[$c] );
            }
            $condition = $this->conditions[$c];
        }

        return $condition;
    }
}
