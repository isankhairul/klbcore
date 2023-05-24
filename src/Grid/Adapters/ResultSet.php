<?php namespace KlbV2\Core\Grid\Adapters;

use BadFunctionCallException;

/**
 * Class ResultSet
 *
 * @package KlbV2\Core\Grid\Adapters
 */
class ResultSet extends AdapterAbstract
{

    protected $resultSet;
    protected $column = [];
    protected $global = [];
    protected $order = [];

    public function getResponse()
    {
        $limit = $this->parser->getLimit();
        $offset = $this->parser->getOffset();
        $total = $this->resultSet->count();

        $this->bind( 'filter', function () {
            throw new BadFunctionCallException( 'Filter function only support for QueryBuilder' );
        } );

        $this->bind( 'global_search', function ( $column, $search ) {
            $this->global[$column][] = $search;
        } );

        $this->bind( 'column_search', function ( $column, $search, $type = null ) {
            $this->column[$column][] = [ $search, $type ];
        } );

        $this->bind( 'order', function ( $order ) {
            $this->order = $order;
        } );

        if ( count( $this->global ) || count( $this->column ) ) {
            $filter = $this->resultSet->filter( function ( $item ) {
                $check = false;

                if ( count( $this->global ) ) {
                    foreach ( $this->global as $column => $filters ) {
                        foreach ( $filters as $search ) {
                            $check = ( strpos( $item->$column, $search ) !== false );
                            if ( $check ) break 2;
                        }
                    }
                } else {
                    $check = true;
                }

                if ( count( $this->column ) && $check ) {
                    foreach ( $this->column as $column => $filters ) {
                        foreach ( $filters as $search ) {
                            $value = $item->$column;
                            if ( empty( $value ) ) {
                                $check = false;
                            } else {
                                if ( is_array( $search ) ) {
                                    switch ( $search[1] /** Type of search */ ) {
                                        case 'range':
                                            $check = $value >= $search[0]['from'] && $value <= $search[0]['to'];
                                            break;
                                        case 'date':
                                        case 'datetime':
                                            $itime = strtotime( $value );
                                            $starttime = strtotime( $search[0]['from'] );
                                            $endtime = strtotime( $search[0]['to'] );
                                            $check = $itime >= $starttime && $itime <= $endtime;
                                            break;
                                        default:
                                            $check = ( strpos( $value, $search ) !== false );
                                            break;
                                    }
                                } else {
                                    $check = ( strpos( $value, $search ) !== false );
                                }
                            }
                            if ( !$check ) break 2;
                        }
                    }
                }

                if ( $check ) {
                    return $item;
                }
            } );

            $filtered = count( $filter );
            $items = array_map( function ( $item ) {
                return $item->toArray();
            }, $filter );
        } else {
            $filtered = $total;
            $items = $this->resultSet->filter( function ( $item ) {
                return $item->toArray();
            } );
        }

        if ( $this->order ) {
            $args = [];

            foreach ( $this->order as $order ) {
                $tmp = [];
                list( $column, $dir ) = explode( ' ', $order );

                foreach ( $items as $key => $item ) {
                    $tmp[$key] = $item[$column];
                }

                $args[] = $tmp;
                $args[] = ( $dir == 'desc' ) ? SORT_DESC : SORT_ASC;
            }

            $args[] = &$items;
            call_user_func_array( 'array_multisort', $args );
        }

        if ( $offset > 1 ) {
            $items = array_slice( $items, ( $offset - 1 ) );
        }

        if ( $limit ) {
            $items = array_slice( $items, 0, $limit );
        }

        return $this->formResponse( [
            'total'    => (int) $total,
            'filtered' => (int) $filtered,
            'data'     => $items,
        ] );
    }

    public function setResultSet( $resultSet )
    {
        $this->resultSet = $resultSet;
    }
}
