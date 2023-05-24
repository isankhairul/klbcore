<?php namespace KlbV2\Core\Grid;

/**
 * Class GridDetailOption
 *
 * @package KlbV2\Core\Grid
 */
class GridDetailOption
{
    private $url;

    private $params;

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'url'    => $this->getUrl(),
            'params' => $this->getParams(),
        ];
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     *
     * @return GridDetailOption
     */
    public function setUrl( $url )
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param mixed $params
     *
     * @return GridDetailOption
     */
    public function setParams( $params )
    {
        $this->params = $params;

        return $this;
    }
}
