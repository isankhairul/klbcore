<?php namespace Klb\Core\Grid\Adapters;
use BadFunctionCallException;

/**
 * Class ArrayAdapter
 *
 * @package Klb\Core\Grid\Adapters
 */
class ArrayAdapter extends AdapterAbstract
{

    protected $array = [];
    protected $column = [];
    protected $global = [];
    protected $order = [];

    public function setArray( array $array )
    {
        $this->array = $array;
    }

    public function getResponse()
    {
        $limit = $this->parser->getLimit();
        $offset = $this->parser->getOffset();
        $total = count( $this->array );

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
            $items = array_filter( $this->array, function ( $item ) {
                $check = false;

                if ( count( $this->global ) ) {
                    foreach ( $this->global as $column => $filters ) {
                        foreach ( $filters as $search ) {
                            $check = ( strpos( $item[$column], $search ) !== false );
                            if ( $check ) break 2;
                        }
                    }
                } else {
                    $check = true;
                }

                if ( count( $this->column ) && $check ) {
                    foreach ( $this->column as $column => $filters ) {
                        foreach ( $filters as $search ) {
                            $value = $item[$column];
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
        } else {
            $items = $this->array;
        }

        $filtered = count( $items );

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
            $items = array_slice( $items, $offset );
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

}
