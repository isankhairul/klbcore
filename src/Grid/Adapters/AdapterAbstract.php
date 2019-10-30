<?php namespace Klb\Core\Grid\Adapters;

use Klb\Core\Grid\GridTable;
use Klb\Core\Grid\ParamsParser;

/**
 * Class AdapterAbstract
 *
 * @package Klb\Core\Grid\Adapters
 */
abstract class AdapterAbstract
{

    /**
     * @var ParamsParser
     */
    protected $operator = [];
    protected $valueFormat = [];
    /** @var ParamsParser null */
    protected $parser = null;
    protected $defaultOrderByDirection = null;
    protected $filters = [];
    /**
     * @var callable
     */
    protected $sum = null;
    protected $columns = [];
    protected $conditions = [];
    protected $length = 30;
    /** @var GridTable */
    protected $grid;

    public function __construct( $length )
    {
        $this->length = $length;
    }

    /**
     * @return GridTable
     */
    public function getGrid()
    {
        return $this->grid;
    }

    /**
     * @param GridTable $grid
     */
    public function setGrid( GridTable $grid )
    {
        $this->grid = $grid;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     */
    public function setColumns( array $columns )
    {
        $this->columns = $columns;
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @param array $conditions
     *
     * @return $this
     */
    public function setConditions( array $conditions )
    {
        $this->conditions = $conditions;
        return $this;
    }

    /**
     * @return ParamsParser
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param array $operator
     *
     * @return $this
     */
    public function setOperator( array $operator )
    {
        $this->operator = $operator;
        return $this;
    }

    /**
     * @return callable
     */
    public function getSum()
    {
        return $this->sum;
    }

    /**
     * @param callable $sum
     *
     * @return AdapterAbstract
     */
    public function setSum( callable $sum )
    {
        $this->sum = $sum;
        return $this;
    }

    /**
     * @return array
     */
    public function getValueFormat()
    {
        return $this->valueFormat;
    }

    /**
     * @param array $valueFormat
     *
     * @return AdapterAbstract
     */
    public function setValueFormat( array $valueFormat )
    {
        $this->valueFormat = $valueFormat;
        return $this;
    }

    /**
     * @return ParamsParser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * @param ParamsParser $parser
     */
    public function setParser( ParamsParser $parser )
    {
        $this->parser = $parser;
    }

    /**
     * @param $options
     *
     * @return array
     */
    public function formResponse( $options )
    {
        $defaults = [
            'total'    => 0,
            'filtered' => 0,
            'data'     => [],
            'sum'      => []
        ];
        $options += $defaults;

        $response = [];
        $response['draw'] = $this->parser->getDraw();
        $response['recordsTotal'] = $options['total'];
        $response['recordsFiltered'] = $options['filtered'];
        $response['recordsSum'] = $options['sum'];

        if ( count( $options['data'] ) ) {
            foreach ( $options['data'] as $item ) {
                if ( isset( $item['id'] ) ) {
                    $item['DT_RowId'] = $item['id'];
                }

                $response['data'][] = $item;
            }
        } else {
            $response['data'] = [];
        }

        return $response;
    }

    /**
     * @param $case
     * @param $closure
     */
    public function bind( $case, $closure )
    {
        switch ( $case ) {

            case "global_search":
                $search = $this->parser->getSearchValue();
                if ( !mb_strlen( $search ) ) return;

                foreach ( $this->parser->getSearchableColumns() as $column ) {
                    if ( !$this->columnExists( $column ) ) continue;
                    $closure( $column, $this->sanitaze( $search ) );
                }
                break;
            case "column_search":
                $columnSearch = $this->parser->getColumnsSearch();

                if ( !$columnSearch ) return;

                if ( $this->parser->isFilter() ) {
                    foreach ( $columnSearch as $column => $value ) {
                        if ( !$this->columnExists( $column ) ) continue;
                        $type = null;
                        if ( isset( $value['type'] ) ) {
                            $type = $value['type'];
                        }
                        $closure( $column, $value, $type );
                    }
                } else {
                    foreach ( $columnSearch as $key => $column ) {
                        if ( !$this->columnExists( $column['data'] ) ) continue;
                        $closure( $column['data'], $this->sanitaze( $column['search']['value'] ) );
                    }
                }
                break;
            case "order":
                $order = $this->parser->getOrder();
                if ( !$order ) {
                    if ( !empty( $this->defaultOrderByDirection ) ) {
                        $order = $this->defaultOrderByDirection;
                    } else {
                        return;
                    }
                }

                $orderArray = [];
                $orderArrayString = [];

                foreach ( $order as $orderBy ) {
                    if ( !isset( $orderBy['dir'] ) || !isset( $orderBy['column'] ) ) continue;
                    $orderDir = $orderBy['dir'];

                    $column = $this->parser->getColumnById( $orderBy['column'] );
                    if ( is_null( $column ) || !$this->columnExists( $column ) ) continue;

                    $orderArrayString[$column] = "{$column} {$orderDir}";
                    $orderArray[$column] = [ 'dir' => $orderDir, 'colById' => $orderBy['column'] ];
                }

                $closure( $orderArrayString, $orderArray );
                break;

        }
    }

    /**
     * @param $column
     *
     * @return bool
     */
    public function columnExists( $column )
    {
        if ( array_key_exists( $column, $this->columns ) ) {
            return true;
        }
        return in_array( $column, $this->columns );
    }

    /**
     * @param $string
     *
     * @return string
     */
    public function sanitaze( $string )
    {
        return mb_substr( $string, 0, $this->length );
    }

    /**
     * @return mixed
     */
    abstract public function getResponse();

    /**
     * @return null
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param callable $filter
     *
     * @return AdapterAbstract
     */
    public function addFilters( callable $filter )
    {
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * @param array $orderBy
     *
     * @return $this
     */
    public function defaultOrderBy( array $orderBy )
    {
        $this->defaultOrderByDirection = $orderBy;
        return $this;
    }
}
