<?php namespace Klb\Core\Grid\Adapters;

use BadMethodCallException;
use Phalcon\Db;
use Phalcon\Mvc\Model\Query\BuilderInterface;
use Phalcon\Paginator\Adapter\QueryBuilder as PQueryBuilder;
use stdClass;
use function is_array;
use function strpos;

/**
 * Class QueryBuilder
 *
 * @package Klb\Core\Grid\Adapters
 */
class QueryBuilder extends AdapterAbstract
{
    /**
     * @var BuilderInterface
     */
    protected $builder;
    private $colMaps = [];

    /**
     * @param BuilderInterface $builder
     */
    public function setBuilder( $builder )
    {
        $this->builder = $builder;
        $this->columnMap();
    }

    /**
     *
     */
    private function columnMap()
    {
        $columns = $this->builder->getColumns();
        if ( is_array( $columns ) ) {
            foreach ( $columns as $icol => $column ) {
                if ( is_array( $column ) ) {
                    $key = $this->removeDot( $column[1], $icol );
                    $value = $column[0];
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
     * @param      $str
     * @param null $default
     *
     * @return null
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

    public function getResponse()
    {

        /*$builder = new PQueryBuilder([
            'builder' => $this->builder,
            'limit'   => 1,
            'page'    => 1,
        ]);

        $total = $builder->getPaginate();*/
        $total = $this->getTotalItem();
        $sum = null;
        $colMaps = $this->colMaps;

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
                    $this->builder->$condition( "{$sqlColumn} {$operator} :key_{$column}:", [ "key_{$column}" => $value ] );
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
                            $this->builder->$condition( "{$sqlColumn} {$operator} :key_{$column}:", [ "key_{$column}" => $value ] );
                        }
                    }
                    break;
            }
        } );

        $this->bind( 'order', function ( $order, $orderBy ) {
//            var_dump($this->columns, $this->colMaps, $order, $orderBy);exit;
            if ( !empty( $order ) ) {
                $orders = [];
                foreach ( $orderBy as $orderById => $orderByCol ) {
                    $orders[] = ( isset( $this->colMaps[$orderById] ) && strpos( $this->colMaps[$orderById], $orderById ) !== false ? $this->colMaps[$orderById] : $orderById ) . ' ' . $orderByCol['dir'];
                }
                $this->builder->orderBy( implode( ', ', $orders ) );
            }
        } );

        if ( is_array( $this->filters ) && count( $this->filters ) ) {
            foreach ( $this->filters as $filter ) {
                if ( is_callable( $filter ) ) {
                    $filter( $this->builder );
                }
            }
        }

//        di()->get('logger')->debug('PHQL: ' . str_replace(['[',']'], '', $this->builder->getPhql()));
        if ( null !== $this->getSum() ) {
            $callback = $this->getSum();
            $sum = $callback( $this->builder );
        }
//        pre($this->parser->getLimit(),/* $this->parser->getParams(), */$this->parser->getColumnsSearch(), $this->columns, $this->colMaps, $this->builder->getPhql());
        $builderOptions = [
            'builder' => $this->builder,
            'limit'   => $this->parser->getLimit(),
            'page'    => $this->parser->getPage(),
        ];

        $builder = new PQueryBuilder( $builderOptions );

        $paginate = $builder->getPaginate();
//        pre($paginate);
        $total = $total->total_items;
        $filtered = $paginate->total_items;
        if ( $this->getParser()->isFetchAssoc() ) {
            $data = $paginate->items->toArray();
        } else {
            $data = [];
            foreach ( $paginate->items as $item ) {
                $new = [];
                foreach ( $this->getColumns() as $column ) {
                    $new[] = $item[$column];
                }
                $data[] = $new;
            }
        }

        return $this->formResponse( compact( 'total', 'filtered', 'data', 'sum' ) );
    }

    /**
     * @return stdClass
     */
    protected function getTotalItem()
    {


        /**
         * We make a copy of the original builder to count the total of records
         */
        $totalBuilder = clone $this->builder;

        $limit = 1;
        $numberPage = 1;


        $hasHaving = !empty( $totalBuilder->getHaving() );

        $groups = $totalBuilder->getGroupBy();

        $hasGroup = !empty( $groups );

        /**
         * Change the queried columns by a COUNT(*)
         */

        $totalBuilder->columns( "COUNT(*) [rowcount]" );

        /**
         * Change 'COUNT()' parameters, when the query contains 'GROUP BY'
         */
        if ( $hasGroup ) {
            if ( is_array( $groups ) ) {
                $groupColumn = implode( ", ", $groups );
            } else {
                $groupColumn = $groups;
            }

            if ( !$hasHaving ) {
                $totalBuilder->groupBy( null )->columns( [ "COUNT(DISTINCT " . $groupColumn . ") AS [rowcount]" ] );
            } else {
                $totalBuilder->columns( [ "DISTINCT " . $groupColumn ] );
            }
        }

        /**
         * Remove the 'ORDER BY' clause, PostgreSQL requires this
         */
        $totalBuilder->orderBy( null );

        /**
         * Obtain the PHQL for the total query
         */
        $totalQuery = $totalBuilder->getQuery();

        /**
         * Obtain the result of the total query
         * If we have having perform native count on temp table
         */
        if ( $hasHaving ) {
            $sql = $totalQuery->getSql();

            $row = di( 'db' )->fetchOne( "SELECT COUNT(*) as \"rowcount\" FROM (" . $sql["sql"] . ") as T1", Db::FETCH_ASSOC, $sql["bind"] );
            $rowcount = $row ? intval( $row["rowcount"] ) : 0;
            $totalPages = intval( ceil( $rowcount / $limit ) );
        } else {
            $result = $totalQuery->execute();
            $row = $result->getFirst();
            $rowcount = $row ? intval( $row->rowcount ) : 0;
            $totalPages = intval( ceil( $rowcount / $limit ) );
        }


        $page = new stdClass();
        $page->first = 1;
        $page->current = $numberPage;
        $page->last = $totalPages;
        $page->total_pages = $totalPages;
        $page->total_items = $rowcount;

        return $page;
    }

    /**
     * @param        $c
     * @param string $condition
     * @param string $op
     *
     * @return string
     */
    private function _condition( $c, $condition = 'andWhere' )
    {
        if ( !in_array( $condition, [ 'andWhere', 'andHaving', 'orWhere', 'orHaving' ] ) ) {
            throw new BadMethodCallException( 'Condition not allowed' );
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
