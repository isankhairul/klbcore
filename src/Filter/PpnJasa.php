<?php namespace Klb\Core\Filter;

use Phalcon\Filter\UserFilterInterface;

/**
 * Class PpnJasa
 *
 * @package Klb\Core\Filter
 */
class PpnJasa implements UserFilterInterface
{

    /**
     * @param $value
     *
     * @return int
     */
    public function filter( $value )
    {
        return $value / 100 * 1;
    }
}
