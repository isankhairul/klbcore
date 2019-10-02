<?php namespace Klb\Core\Grid;

use InvalidArgumentException;
use Klb\Core\Grid\Adapters\AdapterAbstract;
use Klb\Core\Grid\Adapters\ArrayAdapter;
use Klb\Core\Grid\Adapters\QueryBuilder;
use Klb\Core\Grid\Adapters\RawSql;
use Klb\Core\Grid\Adapters\ResultSet;
use Klb\Core\Grid\Builder\DbSelectException;
use Klb\Core\Grid\Builder\Select;
use Klb\Core\Model;
use Phalcon\Db\RawValue;
use Phalcon\Http\Response;
use Phalcon\Mvc\Model\Query\BuilderInterface;
use Phalcon\Mvc\User\Plugin;
use function str_replace;

/**
 * Class DataTable
 *
 * @package Klb\Core\Grid
 */
class DataTable extends Plugin
{
    const SEARCH_LIKE_FULL = '%{value}%';
    const SEARCH_LIKE_PREFIX = '{value}%';
    const SEARCH_LIKE_SUFFIX = '%{value}';
    public $parser;
    /**
     * @var array
     */
    protected $options;
    /**
     * @var array
     */
    protected $params;
    protected $response;
    /**
     * @var callable
     */
    protected $transformer = null;
    /**
     * @var null
     */
    protected $defaultOrderDirection = null;
    /**
     * @var AdapterAbstract
     */
    protected $adapter;

    /**
     * DataTable constructor.
     *
     * @param array $options
     */
    public function __construct( array $options = [] )
    {
        $default = [
            'limit'  => 20,
            'length' => 50,
        ];

        $this->options = array_merge( $default, $options );
        $this->parser = new ParamsParser( $this->options['limit'] );
    }

    /**
     * @param BuilderInterface|\Phalcon\Mvc\Model\Resultset|array|Model|string $builder
     * @param string|array                                                     $columns
     * @param callable                                                         $filter
     *
     * @return DataTable
     */
    public static function of( $builder, $columns = [], callable $filter = null )
    {
        if ( is_string( $columns ) ) {
            $columns = explode( ', ', $columns );
        }
        /** @var DataTable $dataTable */
        $dataTable = null;
        if ( $builder instanceof BuilderInterface ) {
            $dataTable = ( new static() )->fromBuilder( $builder, $columns );
        } else if ( $builder instanceof ResultSet ) {
            $dataTable = ( new static() )->fromResultSet( $builder, $columns );
        } else if ( is_string( $builder ) && class_exists( $builder ) ) {
            $builder = di()->get( 'modelsManager' )->createBuilder()
                ->columns( $columns ?: $builder::getMetaDataAttributes() )
                ->from( $builder );
            $dataTable = ( new static() )->fromBuilder( $builder, $columns );
        } else if ( is_array( $builder ) ) {
            $dataTable = ( new static() )->fromArray( $builder, $columns );
        } else if ( $builder instanceof Select ) {
            $dataTable = ( new static() )->fromSelect( $builder, $columns );
        }

        if ( null !== $dataTable ) {
            if ( null !== $filter ) {
                return $dataTable->filter( $filter );
            }

            return $dataTable;
        }
        throw new InvalidArgumentException( 'Invalid argument for builder' );
    }

    /**
     * @param       $builder
     * @param array $columns
     *
     * @return $this
     */
    public function fromBuilder( $builder, $columns = [] )
    {
        if ( empty( $columns ) ) {
            $columns = $builder->getColumns();
            $columns = ( is_array( $columns ) ) ? $columns : array_map( 'trim', explode( ',', $columns ) );
        }

        $this->adapter = new QueryBuilder( $this->options['length'] );
        $this->adapter->setBuilder( $builder );
        $this->adapter->setParser( $this->parser );
        $this->adapter->setColumns( $columns );

        return $this;
    }

    /**
     * @param ResultSet $resultSet
     * @param array     $columns
     *
     * @return $this
     */
    public function fromResultSet( $resultSet, $columns = [] )
    {
        if ( empty( $columns ) && $resultSet->count() > 0 ) {
            $columns = array_keys( $resultSet->getFirst()->toArray() );
            $resultSet->rewind();
        }

        $this->adapter = new ResultSet( $this->options['length'] );
        $this->adapter->setResultSet( $resultSet );
        $this->adapter->setParser( $this->parser );
        $this->adapter->setColumns( $columns );

        return $this;
    }

    /**
     * @param       $array
     * @param array $columns
     *
     * @return $this
     */
    public function fromArray( $array, $columns = [] )
    {
        if ( empty( $columns ) && count( $array ) > 0 ) {
            $columns = array_keys( current( $array ) );
        }

        $this->adapter = new ArrayAdapter( $this->options['length'] );
        $this->adapter->setArray( $array );
        $this->adapter->setParser( $this->parser );
        $this->adapter->setColumns( $columns );

        return $this;
    }

    /**
     * @param Select $builder
     * @param array  $columns
     *
     * @return $this
     * @throws DbSelectException
     */
    public function fromSelect( $builder, $columns = [] )
    {
        if ( empty( $columns ) ) {
            $columns = $builder->getPart( Select::COLUMNS );
        }

        $this->adapter = new RawSql( $this->options['length'] );
        $this->adapter->setBuilder( $builder );
        $this->adapter->setParser( $this->parser );
        $this->adapter->setColumns( $columns );

        return $this;
    }

    /**
     * @param callable $filter
     *
     * @return $this
     */
    public function filter( callable $filter )
    {
        $this->filter = $filter;
        $this->adapter->addFilters( $filter );

        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->parser->getParams();
    }

    /**
     * @return Response
     */
    public function sendResponse()
    {
        if ( $this->di->has( 'view' ) ) {
            $this->di->get( 'view' )->disable();
        }

        $this->response = $this->getResponse();
        if ( $this->transformer !== null && isset( $this->response['data'] ) ) {
            $this->response['data'] = array_map( $this->transformer, $this->response['data'] );
        }
        $response = new Response();
        $response->setContentType( 'application/json', 'utf8' );
        $response->setJsonContent( $this->getResponse() );

        return $response->send();
    }

    /**
     * @return array
     */
    public function getResponse()
    {
        if ( !$this->response ) {
            $this->response = $this->adapter->getResponse();
        }

        return !empty( $this->response ) ? $this->response : [];
    }

    /**
     * @param callable $transformer
     *
     * @return $this
     */
    public function transform( callable $transformer )
    {
        $this->transformer = $transformer;

        return $this;
    }

    /**
     * @param GridTable $gridTable
     *
     * @return $this
     */
    public function grid( GridTable $gridTable )
    {
        $this->adapter->setGrid( $gridTable );

        return $this;
    }

    /**
     * @param callable $sum
     *
     * @return $this
     */
    public function sum( callable $sum )
    {
        $this->adapter->setSum( $sum );

        return $this;
    }

    /**
     * @param array $orderDireaction
     *
     * @return $this
     */
    public function defaultOrderBy( array $orderDireaction )
    {
        $this->defaultOrderDirection = $orderDireaction;
        $this->adapter->defaultOrderBy( $this->defaultOrderDirection );

        return $this;
    }

    /**
     * @param array  $cols
     * @param string $type
     *
     * @return $this
     */
    public function searchWithLike( array $cols, $type = self::SEARCH_LIKE_FULL )
    {
        $operators = [];
        $valueFormats = [];
        foreach ( $cols as $col ) {
            $operators[$col] = 'LIKE';
            $valueFormats[$col] = function ( $value ) use ( $type ) {
                return str_replace( '{value}', $value, $type );
            };
        }

        return $this
            ->operator( $operators )
            ->valueFormat( $valueFormats );
    }

    /**
     * @param array $valueFormat
     *
     * @return $this
     */
    public function valueFormat( array $valueFormat )
    {
        $this->adapter->setValueFormat( $valueFormat );

        return $this;
    }

    /**
     * @param array $operator
     *
     * @return $this
     */
    public function operator( array $operator )
    {
        $this->adapter->setOperator( $operator );

        return $this;
    }

    /**
     * @param array $conditions
     *
     * @return $this
     */
    public function conditions( array $conditions )
    {
        $this->adapter->setConditions( $conditions );

        return $this;
    }

    /**
     * @param array  $filters
     * @param string $operator
     *
     * @return DataTable
     */
    public function customsFilter( array $filters, $operator = 'AND' )
    {
        return $this->filter( function ( $builder ) use ( $filters, $operator ) {
            foreach ( $filters as $column => $value ) {
                $operatorInner = '=';
                $keyName = str_replace( '.', '_', $column );
                if ( is_array( $value ) && count( $value ) === 2 ) {
                    $operatorInner = $value[0];
                    $value = $value[1];
                    if ( $operator === 'AND' ) {
                        if ( $builder instanceof Select ) {
                            $builder->where( "{$column} {$operatorInner} ?", $value );
                        } else {
                            $builder->andWhere( "{$column} {$operatorInner} :key_{$keyName}:", [ "key_{$keyName}" => $value ] );
                        }
                    } else {
                        if ( $builder instanceof Select ) {
                            $builder->orWhere( "{$column} {$operatorInner} ?", $value );
                        } else {
                            $builder->orWhere( "{$column} {$operatorInner} :key_{$keyName}:", [ "key_{$keyName}" => $value ] );
                        }
                    }
                } else if ( $value instanceof RawValue ) {
                    if ( $builder instanceof Select ) {
                        if ( $operator === 'AND' ) {
                            $builder->where( $value->getValue() );
                        } else {
                            $builder->orWhere( $value->getValue() );
                        }
                    } else {
                        if ( $operator === 'AND' ) {
                            $builder->andWhere( $value->getValue() );
                        } else {
                            $builder->orWhere( $value->getValue() );
                        }
                    }
                } else {
                    if ( $builder instanceof Select ) {
                        if ( $operator === 'AND' ) {
                            $builder->where( "{$column} {$operatorInner} ?", $value );
                        } else {
                            $builder->orWhere( "{$column} {$operatorInner} ?", $value );
                        }
                    } else {
                        if ( $operator === 'AND' ) {
                            $builder->andWhere( "{$column} {$operatorInner} :key_{$keyName}:", [ "key_{$keyName}" => $value ] );
                        } else {
                            $builder->orWhere( "{$column} {$operatorInner} :key_{$keyName}:", [ "key_{$keyName}" => $value ] );
                        }
                    }
                }
            }
        } );
    }
}
