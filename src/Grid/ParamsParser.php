<?php namespace Klb\Core\Grid;

use Phalcon\Mvc\User\Component;

/**
 * Class ParamsParser
 * @package Klb\Core\Grid
 */
class ParamsParser extends Component
{
    /**
     * @var array
     */
    protected $params = [];
    /**
     * @var int
     */
    protected $page = 1;

    protected $isFilter = false;

    protected $fetchAssoc = false;

    /**
     * ParamsParser constructor.
     * @param $limit
     */
    public function __construct($limit)
    {
        $params = [
            'draw' => null,
            'start' => 1,
            'length' => $limit,
            'columns' => [],
            'search' => [],
            'order' => [],
            'filter_fields' => []
        ];
        if($this->di->has('request')) {
            $request = $this->di->get('request');
            $requestParams = $request->isPost() ? $request->getPost() : $request->getQuery();
        } else {
            $requestParams = [];
        }
        $this->params = (array)$requestParams + $params;
        $this->setPage();
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    public function setPage()
    {
        $this->page = (int)(floor($this->params['start'] / $this->params['length']) + 1);
        $this->fetchAssoc = is_numeric($this->getColumnById(0)) ? false : true;
    }

    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return array
     */
    public function getColumnsSearch()
    {
        if(!empty($this->params['action']) && $this->params['action'] === 'filter') {
            $this->isFilter = true;

            return array_filter($this->params['filter_fields'], function ($v){
                if(is_array($v)){
                    unset($v['type']);
                    return array_filter($v, function ($v1){
                        return ''.$v1 !== '';
                    });
                }
                return ''.$v !== '';
            });
        }
        return array_filter(array_map(function ($item) {
            return (isset($item['search']['value']) && strlen($item['search']['value'])) ? $item : null;
        }, $this->params['columns']));
    }


    /**
     * @return array
     */
    public function getSearchableColumns()
    {
        return array_filter(array_map(function ($item) {
            return (isset($item['searchable']) && $item['searchable'] === "true") ? $item['data'] : null;
        }, $this->params['columns']));
    }

    public function getDraw()
    {
        return $this->params['draw'];
    }

    public function getLimit()
    {
        return $this->params['length'];
    }

    public function getOffset()
    {
        return $this->params['start'];
    }

    public function getColumns()
    {
        return $this->params['columns'];
    }

    public function getColumnById($id)
    {
        return isset($this->params['columns'][$id]['data']) ? $this->params['columns'][$id]['data'] : null;
    }

    public function getSearch()
    {
        return $this->params['search'];
    }

    public function getOrder()
    {
        return $this->params['order'];
    }

    /**
     * @return string
     */
    public function getSearchValue()
    {
        return isset($this->params['search']['value']) ? $this->params['search']['value'] : '';
    }

    /**
     * @return boolean
     */
    public function isFetchAssoc()
    {
        return $this->fetchAssoc;
    }

    /**
     * @return boolean
     */
    public function isFilter()
    {
        return $this->isFilter;
    }

}
