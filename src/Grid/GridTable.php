<?php

namespace Klb\Core\Grid;

use Exception;
use Phalcon\Mvc\User\Component;
use function array_key_exists;
use function call_user_func;
use function is_callable;
use function strpos;
use function strtolower;

/**
 * Class GridTable
 *
 * @package Klb\Core\Grid
 */
class GridTable extends Component
{

    /**
     * @var
     */
    public $name = 'KlbTableGrid0';

    /**
     * @var string
     */
    public $url;

    /**
     * @var array
     */
    public $columns = [];
    /**
     * @var array
     */
    public $bulkActions = [];
    /**
     * @var array
     */
    public $actions = [];
    /**
     * @var array
     */
    private $footer = [];
    /**
     * @var bool
     */
    private $isBulkAction = null;

    /**
     * @var bool
     */
    private $isUseAction = true;

    /**
     * @var null
     */
    private $typeOfCheckboxId = null;

    /**
     * @var array
     */
    private $navActions = [];

    /**
     * @var
     */
    private $detailOption;

    /**
     * @var bool
     */
    private $disabledDefaultOnSubmit = false;

    /**
     * @var string
     */
    private $sumColumnTitle = 'Total';

    /**
     * @var array
     */
    private $order = [
        [ 1, 'asc' ],
    ];

    /**
     * @var callable
     */
    private $callbackResponse;

    public function __construct()
    {
        $this->addCheckbox( 'id' );
    }

    /**
     * @param string $column Column name
     *
     * @return GridTable
     */
    public function addCheckbox( $column )
    {
        return $this->addColumn( $column, [
            'type' => 'checkbox',
        ] );
    }

    /**
     * @param string $column
     * @param array  $data
     *
     * @return $this
     */
    public function addColumn( $column, array $data = [] )
    {
        $default = [
            'name'           => $column,
            'title'          => null,
            'width'          => null,
            'type'           => null,
            'from'           => null,
            'to'             => null,
            'options'        => [],
            'sum'            => null,
            /** Option for Datatable */
            'searchable'     => true,
            'orderable'      => true,
            'className'      => null,
            'defaultContent' => null,
            'index'          => 0,
            'visible'        => true,
            'order'          => null,
        ];
        $type = isset( $data['type'] ) ? $data['type'] : null;

        $className = "";
        if ( $type === 'daterange' ) {
            $className = 'text-center';
        }

        if ( $type === 'range' ) {
            $className = 'text-right';
        }

        if ( !array_key_exists( 'className', $data ) ) {
            $data['className'] = null;
        }

        if ( "" !== $className ) {
            $data['className'] .= " " . $className;
            $data['className'] = trim( $data['className'] );
        }

        if ( $type === 'checkbox' ) {
            $data = [
                'title'     => 'Record #',
                'type'      => 'checkbox',
                'className' => 'text-center',
                'width'     => '25',
                'fetch'     => false,
            ];
            if ( !empty( $this->typeOfCheckboxId ) && array_key_exists( $this->typeOfCheckboxId, $this->columns ) ) {
                unset( $this->columns[$this->typeOfCheckboxId] );
            }
            $this->typeOfCheckboxId = $column;
        }
        $data['index'] = count( $this->columns );
        $this->columns[$column] = array_merge( $default, $data );

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param $url
     *
     * @return $this
     */
    public function setUrl( $url )
    {

        if ( strpos( $url, '__grid' ) === false ) {
            if ( strpos( $url, '?' ) !== false ) {
                $url .= '&';
            } else {
                $url .= '?';
            }
            $url .= '__grid=1';
        }

        $this->url = $url;

        return $this;
    }

    /**
     * @return array
     */
    public function getFooter()
    {
        return $this->footer;
    }

    /**
     * @param array $footer
     *
     * @return GridTable
     */
    public function setFooter( array $footer )
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsUseAction()
    {
        return $this->isUseAction;
    }

    /**
     * @param boolean $isUseAction
     *
     * @deprecated will not use
     */
    public function setIsUseAction( $isUseAction )
    {
        $this->isUseAction = $isUseAction;
    }

    /**
     * @param string $sumColumnTitle
     *
     * @return GridTable
     */
    public function setSumColumnTitle( $sumColumnTitle )
    {
        $this->sumColumnTitle = $sumColumnTitle;

        return $this;
    }

    /**
     * @param $flag
     *
     * @return $this
     */
    public function setIsBulkAction( $flag )
    {
        $this->isBulkAction = $flag;

        return $this;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
//        $actions = $this->getActions();
//        if(count($actions) === 0 && !array_key_exists('actions', $this->columns)){
//            $this->setActions($actions);
//        }
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getNavActions()
    {
        return $this->navActions;
    }

    /**
     * @param array $navActions
     *
     * @return $this
     * @throws Exception
     */
    public function setNavActions( array $navActions )
    {
        $this->navActions = [];
        foreach ( $navActions as $nav ) {
            $this->addNavAction( $nav );
        }

        return $this;
    }

    /**
     * @param array $nav
     * @param null  $can
     *
     * @return $this
     * @throws Exception
     */
    public function addNavAction( array $nav, $can = null )
    {
        if ( $can !== null && $can === false ) {
            return $this;
        }
        if ( isset( $nav['allowed'] ) && $nav['allowed'] === false ) {
            return $this;
        }
        if ( !isset( $nav['label'] ) || !isset( $nav['url'] ) ) {
            throw new Exception( 'Nav actions label or url are not set' );
        }
        if ( !isset( $nav['attributes'] ) ) {
            $nav['attributes'] = [];
        }
        if ( !isset( $nav['attributes']['id'] ) ) {
            $nav['attributes']['id'] = slug( $nav['label'] );
        }
        $newAttributes = [];
        foreach ( $nav['attributes'] as $name => $value ) {
            if ( is_numeric( $name ) && !empty( $value ) ) {
                $newAttributes[] = $value;
            } else {
                $newAttributes[] = $name . '="' . addslashes( $value ) . '"';
            }
        }
        $nav['attributes'] = join( ' ', $newAttributes );
        $this->navActions[] = $nav;

        return $this;
    }

    /**
     * @return null
     */
    public function getTypeOfCheckboxId()
    {
        return $this->typeOfCheckboxId;
    }

    /**
     * @param bool $disabledDefaultOnSubmit
     *
     * @return $this
     */
    public function setDisabledDefaultOnSubmit( $disabledDefaultOnSubmit )
    {
        $this->disabledDefaultOnSubmit = $disabledDefaultOnSubmit;

        return $this;
    }

    /**
     * @return array
     */
    public function fetch()
    {
        $sumColumn = null;
        $columns = [];
        $actions = $this->getActions();
        $bulkActions = $this->getBulkActions();
        $isUseAction = count( $actions ) > 0; //$this->getIsUseAction();
        $isBulkAction = count( $bulkActions ) > 0;
        $detailIndexColumn = null;
        foreach ( $actions as &$action ) {
            if ( !isset( $action['attributes'] ) ) {
                continue;
            }
            $action['attributes'] = ' ' . print_attribute( $action['attributes'] );
        }
        if ( false === $isBulkAction ) {
            unset( $this->columns[$this->typeOfCheckboxId] );
            $i = 0;
            foreach ( $this->columns as $key => $column ) {
                $this->columns[$key]['index'] = $i;
                $i++;
            }
            $this->typeOfCheckboxId = null;
        }
        $footerIndex = null;
        $index = 0;
        $defaultOrder = null;
        $sorts = [];
        $sortSkipped = 0;
        $sortSkippedLabel = [];
        foreach ( $this->columns as $id => $column ) {

            if ( $isBulkAction === true && $id === $this->typeOfCheckboxId ) {
                $sortSkipped++;
                $sortSkippedLabel[] = $id;
//                continue;
            }

            if ( $id === '__detail__' ) {
                $sortSkipped++;
                $sortSkippedLabel[] = $id;
                $newDefined = [];
                $newDefined['className'] = 'details-control text-center sorting_disabled';
                $newDefined['orderable'] = false;
                $newDefined['searchable'] = false;
                $newDefined['data'] = null;
                $newDefined['defaultContent'] = '';
                $newDefined['width'] = '10';
                $newDefined['fetch'] = false;
                $newDefined['title'] = '#';
                $detailIndexColumn = $index;
            } else {
                if ( $column['order'] !== null ) {
                    $sorts[( $detailIndexColumn || $index === 0 ) ? $index : $index] = strtolower( $column['order'] );
                }

                if ( null === $defaultOrder ) {
                    $defaultOrder = [
                        ( ( $detailIndexColumn || $index === 0 ) ? $index : $index - 1 ) => 'desc',
                    ];
                }

                $newDefined = [
                    'data'       => $id,
                    'name'       => $id,
                    'searchable' => isset( $column['searchable'] ) ? $column['searchable'] : true,
                    'orderable'  => isset( $column['orderable'] ) ? $column['orderable'] : true,
                ];
                if ( array_key_exists( 'fetch', $column ) && $column['fetch'] === false ) {
                    $newDefined['searchable'] = false;
                    $newDefined['orderable'] = false;
                }
                if ( array_key_exists( 'className', $column ) ) {
                    $newDefined['className'] = $column['className'];
                }
                if ( array_key_exists( 'visible', $column ) ) {
                    $newDefined['visible'] = $column['visible'];
                }
                if ( array_key_exists( 'render', $column ) ) {
                    $newDefined['render'] = $column['render'];
                }
                if ( array_key_exists( 'defaultContent', $column ) ) {
                    $newDefined['defaultContent'] = $column['defaultContent'];
                }
                if ( array_key_exists( 'data', $column ) ) {
                    $newDefined['data'] = $column['data'];
                }

                if ( isset( $column['sumColumn'] ) ) {
                    if ( $footerIndex === null ) {
                        $footerIndex = $index;
                    }
                    $sumColumn[$index] = $column['sumColumn'];
                }
            }
            $columns[] = $newDefined;
            $index++;
        }

        $defaultSort = true;
        if ( count( $sorts ) === 0 && null !== $defaultOrder ) {
            if ( $sortSkipped > 0 ) {
                $this->setOrder( [ $sortSkipped => 'desc' ] );
            } else {
                $this->setOrder( $defaultOrder );
            }
//            $orders = $this->order;
        } else if ( count( $sorts ) > 0 ) {
            $defaultSort = false;
            $this->setOrder( $sorts );
        }
//        \dd(\compact('columns', 'isBulkAction', 'bulkActions', 'sortSkipped', 'sorts', 'orders', 'sortSkippedLabel'));
        $footer = [];
        if ( !empty( $sumColumn ) ) {
            $footer[] = [
                'style'   => 'text-align:right',
                'label'   => $this->sumColumnTitle . ': &nbsp;&nbsp;&nbsp;',
                'colspan' => $footerIndex,
            ];
            foreach ( $sumColumn as $item ) {
                $footer[] = [];
            }
            if ( $isUseAction ) {
                $footer[] = [];
            }
        }
        $this->setFooter( $footer );
        $order = $this->order;
        $typeOfCheckboxId = $this->typeOfCheckboxId;
        $detailOption = $this->detailOption ? $this->getDetailOption()->toArray() : null;
        $disabledDefaultOnSubmit = $this->disabledDefaultOnSubmit;

        return compact( 'columns', 'actions', 'isBulkAction', 'footer', 'sumColumn', 'isUseAction', 'order', 'typeOfCheckboxId', 'detailOption', 'disabledDefaultOnSubmit', 'detailIndexColumn', 'defaultSort' );
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param array $actions
     *
     * @return $this
     */
    public function setActions( $actions )
    {
        $this->actions = $actions;
        $this->addColumn( 'actions', [
            'title'     => 'Actions',
            'type'      => 'actions',
            'className' => 'all',
            'width'     => is_array( $actions ) ? count( $actions ) * 65 : 70,
            'fetch'     => false,
        ] );

        return $this;
    }

    /**
     * @return array
     */
    public function getBulkActions()
    {
        return $this->bulkActions;
    }

    /**
     * @param array $bulkActions
     *
     * @return $this
     */
    public function setBulkActions( array $bulkActions )
    {
        $this->bulkActions = $bulkActions;

        return $this;
    }

    /**
     * @param array $order
     *
     * @return $this
     */
    public function setOrder( array $order )
    {
        $temp = [];
        foreach ( $order as $key => $value ) {
            $temp[] = [ $key, $value ];
        }
        $this->order = $temp;

        return $this;
    }

    /**
     * @return GridDetailOption
     */
    public function getDetailOption()
    {
        if ( null === $this->detailOption ) {
            $this->addColumn( '__detail__' );
            $this->detailOption = new GridDetailOption();
        }

        return $this->detailOption;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function setCallbackResponse( callable $callback )
    {
        $this->callbackResponse = $callback;

        return $this;
    }

    /**
     * @param string $template
     *
     * @return mixed|string
     */
    public function render( $template = 'default/grid' )
    {
        if ( $this->di->get( 'request' )->get( '__grid' ) ) {
            if ( is_callable( $this->callbackResponse ) ) {
                return call_user_func( $this->callbackResponse, $this );
            } else {
                return '';
            }
        }
        $this->di->get( 'view' )->pick( $template );
    }

    /**
     * @param null $label
     * @param null $value
     *
     * @return array|mixed|null
     */
    public function getSelectYesNo( $label = null, $value = null )
    {
        $result = [
            1 => 'Yes',
            0 => 'No',
        ];

        if ( null !== $label && null !== $value ) {
            $result = isset( $result[$value] ) ? $result[$value] : null;
        }

        return $result;
    }

    /**
     * @param null $label
     * @param null $value
     *
     * @return array|mixed|null
     */
    public function getSelectEnabledDisabled( $label = null, $value = null )
    {
        $result = [
            1 => 'Enabled',
            0 => 'Disabled',
        ];

        if ( null !== $label && null !== $value ) {
            $result = isset( $result[$value] ) ? $result[$value] : null;
        }

        return $result;
    }

}
