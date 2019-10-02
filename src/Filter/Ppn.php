<?php namespace Klb\Core\Filter;

use Phalcon\Filter\UserFilterInterface;

/**
 * Class Ppn
 *
 * @package Klb\Core\Filter
 */
class Ppn implements UserFilterInterface
{

    /**
     * @param $value
     *
     * @return int
     */
    public function filter( $value )
    {
        return $value / 100 * 10;
    }
}
